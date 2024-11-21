<?php

namespace App\Services\Implementations\UserPreferences;

use App\Contracts\UserPreferences\UserPreferenceRepositoryInterface;
use App\Http\Resources\UserPreferenceOptionsResource;
use App\Models\Article;
use App\Models\Category;
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

    public function getPreferenceOptions(): object
    {
        $authors = Article::distinct()
            ->orderBy('author')
            ->pluck('author')
            ->filter()  // Remove any null values
            ->values()  // Re-index array
            ->toArray();

        $sources = Article::distinct()
            ->orderBy('source')
            ->pluck('source')
            ->filter()  // Remove any null values
            ->values()  // Re-index array
            ->toArray();

        $categories = Category::select(['id', 'name'])
            ->orderBy('name')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name
                ];
            })
            ->toArray();

        return new UserPreferenceOptionsResource($authors, $sources, $categories);
    }
}
