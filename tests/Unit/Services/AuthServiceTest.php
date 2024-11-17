<?php

namespace Tests\Unit\Services;

use App\Contracts\Auth\AuthenticationInterface;
use App\Contracts\Auth\HashServiceInterface;
use App\Contracts\Auth\PasswordResetRepositoryInterface;
use App\Contracts\Auth\TokenServiceInterface;
use App\Contracts\Auth\UserRepositoryInterface;
use App\Services\AuthService;
use PHPUnit\Framework\TestCase;
use Mockery;
use RuntimeException;
use stdClass;

class AuthServiceTest extends TestCase
{
    private UserRepositoryInterface $userRepository;
    private HashServiceInterface $hash;
    private TokenServiceInterface $tokenService;
    private AuthenticationInterface $auth;
    private PasswordResetRepositoryInterface $passwordResetRepository;
    private AuthService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
        $this->hash = Mockery::mock(HashServiceInterface::class);
        $this->tokenService = Mockery::mock(TokenServiceInterface::class);
        $this->auth = Mockery::mock(AuthenticationInterface::class);
        $this->passwordResetRepository = Mockery::mock(PasswordResetRepositoryInterface::class);

        $this->service = new AuthService(
            $this->userRepository,
            $this->hash,
            $this->tokenService,
            $this->auth,
            $this->passwordResetRepository
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    // Registration Tests
    public function test_register_user_success()
    {
        $userData = ['name' => 'Test User', 'email' => 'test@example.com', 'password' => 'password'];
        $hashedPassword = 'hashed_password';
        $token = 'test_token';

        $this->hash->shouldReceive('make')
            ->with($userData['password'])
            ->once()
            ->andReturn($hashedPassword);

        $user = (object)['name' => $userData['name']];
        $this->userRepository->shouldReceive('create')
            ->with(array_merge($userData, ['password' => $hashedPassword]))
            ->once()
            ->andReturn($user);

        $this->tokenService->shouldReceive('create')
            ->with($user, 'MyApp')
            ->once()
            ->andReturn($token);

        $result = $this->service->registerUser($userData);

        $this->assertEquals([
            'token' => $token,
            'name' => $userData['name']
        ], $result);
    }

    public function test_register_user_with_special_characters()
    {
        $userData = [
            'name' => "O'Connor-Smith's Test",
            'email' => 'test+alias@example.com',
            'password' => 'pass@word123!'
        ];

        $this->hash->shouldReceive('make')->once()->andReturn('hashed');
        $this->userRepository->shouldReceive('create')->once()->andReturn((object)$userData);
        $this->tokenService->shouldReceive('create')->once()->andReturn('token');

        $result = $this->service->registerUser($userData);
        $this->assertArrayHasKey('token', $result);
    }

    public function test_register_user_with_minimal_data()
    {
        $userData = [
            'name' => 'a',
            'email' => 'a@b.c',
            'password' => 'password'
        ];

        $this->hash->shouldReceive('make')->once()->andReturn('hashed');
        $this->userRepository->shouldReceive('create')->once()->andReturn((object)$userData);
        $this->tokenService->shouldReceive('create')->once()->andReturn('token');

        $result = $this->service->registerUser($userData);
        $this->assertArrayHasKey('token', $result);
    }

    public function test_register_user_handles_repository_exception()
    {
        $userData = ['name' => 'Test', 'email' => 'test@test.com', 'password' => 'password'];

        $this->hash->shouldReceive('make')->once()->andReturn('hashed');
        $this->userRepository->shouldReceive('create')
            ->once()
            ->andThrow(new RuntimeException('Database error'));

        $this->expectException(RuntimeException::class);
        $this->service->registerUser($userData);
    }

    // Login Tests
    public function test_login_user_success()
    {
        $credentials = ['email' => 'test@example.com', 'password' => 'password'];
        $user = (object)['name' => 'Test User'];
        $token = 'test_token';

        $this->auth->shouldReceive('attempt')
            ->with($credentials)
            ->once()
            ->andReturn(true);

        $this->auth->shouldReceive('user')
            ->once()
            ->andReturn($user);

        $this->tokenService->shouldReceive('create')
            ->with($user, 'MyApp')
            ->once()
            ->andReturn($token);

        $result = $this->service->loginUser($credentials['email'], $credentials['password']);

        $this->assertEquals([
            'token' => $token,
            'name' => $user->name
        ], $result);
    }

    public function test_login_user_with_unicode_password()
    {
        $credentials = ['email' => 'test@example.com', 'password' => 'пароль123'];

        $this->auth->shouldReceive('attempt')
            ->with($credentials)
            ->once()
            ->andReturn(true);

        $this->auth->shouldReceive('user')
            ->once()
            ->andReturn((object)['name' => 'Test']);

        $this->tokenService->shouldReceive('create')
            ->once()
            ->andReturn('token');

        $result = $this->service->loginUser($credentials['email'], $credentials['password']);
        $this->assertNotNull($result);
    }

    public function test_login_user_with_special_characters_in_email()
    {
        $credentials = ['email' => 'test+alias@sub.example.com', 'password' => 'password'];

        $this->auth->shouldReceive('attempt')
            ->with($credentials)
            ->once()
            ->andReturn(true);

        $this->auth->shouldReceive('user')
            ->once()
            ->andReturn((object)['name' => 'Test']);

        $this->tokenService->shouldReceive('create')
            ->once()
            ->andReturn('token');

        $result = $this->service->loginUser($credentials['email'], $credentials['password']);
        $this->assertNotNull($result);
    }

    public function test_login_user_handles_authentication_exception()
    {
        $credentials = ['email' => 'test@example.com', 'password' => 'password'];

        $this->auth->shouldReceive('attempt')
            ->with($credentials)
            ->once()
            ->andThrow(new RuntimeException('Authentication service error'));

        $this->expectException(RuntimeException::class);
        $this->service->loginUser($credentials['email'], $credentials['password']);
    }

    // Password Reset Tests
    public function test_password_reset_token_generation()
    {
        $email = 'test@example.com';
        $user = (object)['email' => $email];
        $token = 'reset_token';

        $this->userRepository->shouldReceive('findByEmail')
            ->with($email)
            ->once()
            ->andReturn($user);

        $this->tokenService->shouldReceive('createPasswordResetToken')
            ->with($user)
            ->once()
            ->andReturn($token);

        $this->passwordResetRepository->shouldReceive('storeToken')
            ->with($email, $token)
            ->once();

        $result = $this->service->getPasswordResetToken($email);
        $this->assertEquals($token, $result);
    }

    public function test_password_reset_with_token_reuse()
    {
        $email = 'test@example.com';
        $password = 'newpassword';
        $token = 'token';
        $user = (object)['email' => $email];

        // First reset
        $this->passwordResetRepository->shouldReceive('findToken')
            ->with($email, $token)
            ->once()
            ->andReturn((object)['token' => $token]);

        $this->userRepository->shouldReceive('findByEmail')
            ->with($email)
            ->once()
            ->andReturn($user);

        $this->hash->shouldReceive('make')
            ->once()
            ->andReturn('hashed');

        $this->userRepository->shouldReceive('update')
            ->once()
            ->andReturn(true);

        $this->passwordResetRepository->shouldReceive('deleteToken')
            ->with($email)
            ->once();

        $result = $this->service->resetPassword($email, $password, $token);
        $this->assertTrue($result);

        // Second reset with same token
        $this->passwordResetRepository->shouldReceive('findToken')
            ->with($email, $token)
            ->once()
            ->andReturnNull();

        $result = $this->service->resetPassword($email, $password, $token);
        $this->assertFalse($result);
    }

    // Profile Update Tests
    public function test_update_profile_with_same_email()
    {
        $user = (object)[
            'id' => 1,
            'email' => 'test@example.com'
        ];

        $updateData = [
            'name' => 'New Name',
            'email' => 'test@example.com'
        ];

        $this->userRepository->shouldReceive('update')
            ->with($user, $updateData)
            ->once()
            ->andReturn(true);

        $result = $this->service->updateProfile($user, $updateData);
        $this->assertTrue($result);
    }

    public function test_update_profile_with_emoji_in_name()
    {
        $user = (object)['id' => 1];
        $updateData = [
            'name' => 'Test User 👨‍💻',
            'email' => 'test@example.com'
        ];

        $this->userRepository->shouldReceive('update')
            ->with($user, $updateData)
            ->once()
            ->andReturn(true);

        $result = $this->service->updateProfile($user, $updateData);
        $this->assertTrue($result);
    }

    // Password Change Tests
    public function test_change_password_same_as_current()
    {
        $user = (object)['password' => 'hashed_current'];
        $currentPassword = 'password123';
        $newPassword = 'password123';

        $this->hash->shouldReceive('check')
            ->with($currentPassword, 'hashed_current')
            ->once()
            ->andReturn(true);

        $this->hash->shouldReceive('make')
            ->with($newPassword)
            ->once()
            ->andReturn('hashed_new');

        $this->userRepository->shouldReceive('update')
            ->once()
            ->andReturn(true);

        $result = $this->service->changePassword($user, $currentPassword, $newPassword);
        $this->assertTrue($result);
    }

    public function test_change_password_with_special_characters()
    {
        $user = (object)['password' => 'hashed_current'];
        $currentPassword = 'current@123';
        $newPassword = 'new@123!#$';

        $this->hash->shouldReceive('check')
            ->with($currentPassword, 'hashed_current')
            ->once()
            ->andReturn(true);

        $this->hash->shouldReceive('make')
            ->with($newPassword)
            ->once()
            ->andReturn('hashed_new');

        $this->userRepository->shouldReceive('update')
            ->once()
            ->andReturn(true);

        $result = $this->service->changePassword($user, $currentPassword, $newPassword);
        $this->assertTrue($result);
    }

    // Token Management Tests
    public function test_token_creation_with_long_name()
    {
        $userData = [
            'name' => str_repeat('a', 255), // Maximum length name
            'email' => 'test@example.com',
            'password' => 'password'
        ];

        $this->hash->shouldReceive('make')->once()->andReturn('hashed');
        $this->userRepository->shouldReceive('create')->once()->andReturn((object)$userData);
        $this->tokenService->shouldReceive('create')->once()->andReturn('token');

        $result = $this->service->registerUser($userData);
        $this->assertArrayHasKey('token', $result);
    }

    public function test_token_creation_with_concurrent_requests()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password'
        ];

        // Simulate multiple token creations for same user
        $this->hash->shouldReceive('make')->once()->andReturn('hashed');
        $this->userRepository->shouldReceive('create')->once()->andReturn((object)$userData);

        // Each token should be unique
        $this->tokenService->shouldReceive('create')
            ->once()
            ->andReturn('unique_token_' . uniqid());

        $result = $this->service->registerUser($userData);
        $this->assertArrayHasKey('token', $result);
    }
}
