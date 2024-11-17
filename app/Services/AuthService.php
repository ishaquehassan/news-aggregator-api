<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class AuthService
{
    /**
     * Register a new user and return the user data
     *
     * @param array $data
     * @return array
     */
    public function registerUser(array $data): array
    {
        $data['password'] = bcrypt($data['password']);
        $user = User::create($data);

        return [
            'token' => $user->createToken('MyApp')->plainTextToken,
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
        if (Auth::attempt(['email' => $email, 'password' => $password])) {
            $user = Auth::user();
            return [
                'token' => $user->createToken('MyApp')->plainTextToken,
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
        $user = User::where('email', $email)->first();
        $token = Password::createToken($user);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['email' => $user->email, 'token' => $token, 'created_at' => now()]
        );

        return $token;
    }

    /**
     * Reset user password
     *
     * @param string $email
     * @param string $token
     * @param string $password
     * @return bool
     */
    public function resetPassword(string $email, string $password, string $token): bool
    {
        $passwordTokenData = DB::table('password_reset_tokens')->where([
            'email' => $email,
            'token' => $token
        ])->first();

        if ($passwordTokenData) {
            $user = User::where('email', $email)->first();
            $user->forceFill(['password' => bcrypt($password)])->save();
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            return true;
        }

        return false;
    }

    /**
     * Change the user's password
     *
     * @param \App\Models\User $user
     * @param string $currentPassword
     * @param string $newPassword
     * @return bool
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): bool
    {
        // Check if the current password is correct
        if (Hash::check($currentPassword, $user->password)) {
            $user->forceFill(['password' => bcrypt($newPassword)])->save();
            return true;
        }

        return false;
    }

    /**
     * Update the user's profile
     *
     * @param \App\Models\User $user
     * @param array $data
     * @return bool
     */
    public function updateProfile(User $user, array $data): bool
    {
        // Update user information (e.g., name, email)
        $user->update($data);
        return true;
    }

    /**
     * Logout user
     *
     * @param \App\Models\User $user
     * @return void
     */
    public function logoutUser(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}
