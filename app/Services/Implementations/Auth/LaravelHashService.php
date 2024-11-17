<?php

namespace App\Services\Implementations\Auth;

use App\Contracts\Auth\HashServiceInterface;
use Illuminate\Support\Facades\Hash;

class LaravelHashService implements HashServiceInterface
{
    public function make(string $value): string
    {
        return Hash::make($value);
    }

    public function check(string $value, string $hashedValue): bool
    {
        return Hash::check($value, $hashedValue);
    }
}
