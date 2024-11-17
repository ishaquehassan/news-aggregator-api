<?php

namespace App\Services\Implementations\Auth;

use App\Contracts\Auth\PasswordResetRepositoryInterface;
use Illuminate\Support\Facades\DB;

class LaravelPasswordResetRepository implements PasswordResetRepositoryInterface
{
    public function storeToken(string $email, string $token): void
    {
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['email' => $email, 'token' => $token, 'created_at' => now()]
        );
    }

    public function findToken(string $email, string $token): ?object
    {
        return DB::table('password_reset_tokens')
            ->where(['email' => $email, 'token' => $token])
            ->first();
    }

    public function deleteToken(string $email): void
    {
        DB::table('password_reset_tokens')->where('email', $email)->delete();
    }
}
