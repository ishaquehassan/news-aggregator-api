<?php

namespace App\Contracts\UserPreferences;

use App\Models\User;

interface UserPreferenceRepositoryInterface
{
    public function findByUser(User $user): ?object;

    public function create(User $user): object;

    public function update(object $preference, array $data): bool;

    public function firstOrCreate(User $user): object;

    /**
     * @return object
     */
    public function getPreferenceOptions(): object;
}
