<?php

namespace App\Contracts\Auth;

interface HashServiceInterface
{
    public function make(string $value): string;

    public function check(string $value, string $hashedValue): bool;
}
