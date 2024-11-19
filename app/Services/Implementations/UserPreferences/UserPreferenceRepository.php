<?php

namespace App\Services\Implementations\UserPreferences;

use App\Contracts\UserPreferences\UserPreferenceRepositoryInterface;
use App\Models\User;
use App\Models\UserPreference;

class UserPreferenceRepository implements UserPreferenceRepositoryInterface
{
    public function findByUser(User $user): ?object
    {
        return UserPreference::where('user_id', $user->id)->first();
    }

    public function create(User $user): object
    {
        return UserPreference::create([
            'user_id' => $user->id
        ]);
    }

    public function update(object $preference, array $data): bool
    {
        return $preference->update($data);
    }

    public function firstOrCreate(User $user): object
    {
        return UserPreference::firstOrCreate([
            'user_id' => $user->id
        ]);
    }
}
