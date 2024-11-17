<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create([
        'email' => 'user@example.com',
        'password' => Hash::make('password'),
    ]);
});

it('registers a new user', function () {
    $response = $this->postJson('/api/auth/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'token',
                'name',
            ],
        ]);
});

it('logs in an existing user', function () {
    $response = $this->postJson('/api/auth/login', [
        'email' => $this->user->email,
        'password' => 'password',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'token',
                'name',
            ],
        ]);
});

it('fails to login with incorrect credentials', function () {
    $response = $this->postJson('/api/auth/login', [
        'email' => $this->user->email,
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'success' => false,
            'message' => 'Unauthorised.',
        ]);
});

it('sends reset password link', function () {
    $response = $this->postJson('/api/auth/forgot-password', [
        'email' => $this->user->email,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Password reset token created successfully.',
        ]);
});

it('fails to send reset password link for non-existent email', function () {
    $response = $this->postJson('/api/auth/forgot-password', [
        'email' => 'nonexistent@example.com',
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'message' => 'Invalid Request.',
        ]);
});

it('resets the password with a valid token', function () {
    $token = Password::createToken($this->user);

    DB::table('password_reset_tokens')->updateOrInsert([
        'email' => $this->user->email,
    ], [
        'email' => $this->user->email,
        'token' => $token,
        'created_at' => now(),
    ]);

    $response = $this->postJson('/api/auth/reset-password', [
        'email' => $this->user->email,
        'token' => $token,
        'password' => 'newpassword',
        'password_confirmation' => 'newpassword',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Password has been reset.',
        ]);

    $this->assertTrue(Hash::check('newpassword', $this->user->fresh()->password));
});

it('fails to reset password with invalid token', function () {
    $response = $this->postJson('/api/auth/reset-password', [
        'email' => $this->user->email,
        'token' => 'invalid-token',
        'password' => 'newpassword',
        'password_confirmation' => 'newpassword',
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'message' => 'Invalid Request.',
        ]);
});

it('retrieves the authenticated user', function () {
    Sanctum::actingAs($this->user);

    $response = $this->getJson('/api/auth/user');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'name',
                'email',
            ],
        ]);
});

it('fails to get user details when unauthenticated', function () {
    $response = $this->getJson('/api/auth/user');

    $response->assertStatus(401)
        ->assertJson([
            'message' => 'Unauthenticated.',
        ]);
});

it('logs out the authenticated user', function () {
    Sanctum::actingAs($this->user);

    $response = $this->getJson('/api/auth/logout');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
});

it('fails to log out when unauthenticated', function () {
    $response = $this->getJson('/api/auth/logout');

    $response->assertStatus(401)
        ->assertJson([
            'message' => 'Unauthenticated.',
        ]);
});

it('changes the user password', function () {
    Sanctum::actingAs($this->user);

    $response = $this->postJson('/api/auth/change-password', [
        'current_password' => 'password',
        'password' => 'newpassword',
        'password_confirmation' => 'newpassword',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Password updated successfully.',
        ]);

    $this->assertTrue(Hash::check('newpassword', $this->user->fresh()->password));
});

it('fails to change the password with incorrect current password', function () {
    Sanctum::actingAs($this->user);

    $response = $this->postJson('/api/auth/change-password', [
        'current_password' => 'wrongpassword',
        'password' => 'newpassword',
        'password_confirmation' => 'newpassword',
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'message' => 'Current password is incorrect.',
        ]);
});

it('updates the user profile', function () {
    Sanctum::actingAs($this->user);

    $response = $this->postJson('/api/auth/update-profile', [
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Profile updated successfully.',
        ]);

    $this->user->refresh();
    $this->assertEquals('Updated Name', $this->user->name);
    $this->assertEquals('updated@example.com', $this->user->email);
});
