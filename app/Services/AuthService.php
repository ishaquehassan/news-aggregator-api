<?php

namespace App\Services;

use App\Contracts\Auth\AuthenticationInterface;
use App\Contracts\Auth\HashServiceInterface;
use App\Contracts\Auth\PasswordResetRepositoryInterface;
use App\Contracts\Auth\TokenServiceInterface;
use App\Contracts\Auth\UserRepositoryInterface;
use RuntimeException;

readonly class AuthService
{
    public function __construct(
        private UserRepositoryInterface          $userRepository,
        private HashServiceInterface             $hash,
        private TokenServiceInterface            $tokenService,
        private AuthenticationInterface          $auth,
        private PasswordResetRepositoryInterface $passwordResetRepository
    )
    {
    }

    /**
     * Register a new user and return the user data
     *
     * @param array $data
     * @return array
     */
    public function registerUser(array $data): array
    {
        $data['password'] = $this->hash->make($data['password']);
        $user = $this->userRepository->create($data);

        return [
            'token' => $this->tokenService->create($user, 'MyApp'),
            'name' => $user->name,
        ];
    }

    /**
     * Login a user and return user data
     *
     * @param string $email
     * @param string $password
     * @return array|null
     */
    public function loginUser(string $email, string $password): ?array
    {
        if ($this->auth->attempt(['email' => $email, 'password' => $password])) {
            $user = $this->auth->user();
            return [
                'token' => $this->tokenService->create($user, 'MyApp'),
                'name' => $user->name,
            ];
        }

        return null;
    }

    /**
     * Send password reset link via email
     *
     * @param string $email
     * @return string
     */
    public function getPasswordResetToken(string $email): string
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            throw new RuntimeException('User not found');
        }

        $token = $this->tokenService->createPasswordResetToken($user);
        $this->passwordResetRepository->storeToken($email, $token);

        return $token;
    }

    /**
     * Reset user password
     *
     * @param string $email
     * @param string $password
     * @param string $token
     * @return bool
     */
    public function resetPassword(string $email, string $password, string $token): bool
    {
        $tokenData = $this->passwordResetRepository->findToken($email, $token);

        if ($tokenData) {
            $user = $this->userRepository->findByEmail($email);
            $this->userRepository->update($user, [
                'password' => $this->hash->make($password)
            ]);
            $this->passwordResetRepository->deleteToken($email);

            return true;
        }

        return false;
    }

    /**
     * Change the user's password
     *
     * @param object $user
     * @param string $currentPassword
     * @param string $newPassword
     * @return bool
     */
    public function changePassword(object $user, string $currentPassword, string $newPassword): bool
    {
        if ($this->hash->check($currentPassword, $user->password)) {
            $this->userRepository->update($user, [
                'password' => $this->hash->make($newPassword)
            ]);
            return true;
        }

        return false;
    }

    /**
     * Update the user's profile
     *
     * @param object $user
     * @param array $data
     * @return bool
     */
    public function updateProfile(object $user, array $data): bool
    {
        return $this->userRepository->update($user, $data);
    }

    /**
     * Logout user
     *
     * @param object $user
     * @return void
     */
    public function logoutUser(object $user): void
    {
        if (method_exists($user, 'currentAccessToken') && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }
    }
}
