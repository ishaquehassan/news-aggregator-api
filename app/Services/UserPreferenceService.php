<?php

namespace App\Services;

use App\Contracts\Articles\ArticleRepositoryInterface;
use App\Contracts\UserPreferences\UserPreferenceRepositoryInterface;
use App\Models\User;

readonly class UserPreferenceService
{
    public function __construct(
        private ArticleRepositoryInterface        $articleRepository,
        private UserPreferenceRepositoryInterface $preferenceRepository
    )
    {
    }

    public function updatePreferences(User $user, array $categories, array $sources, array $authors): array
    {
        $preferences = $this->preferenceRepository->firstOrCreate($user);
        $this->preferenceRepository->update($preferences, [
            'categories' => $categories,
            'sources' => $sources,
            'authors' => $authors
        ]);

        return $this->getUserPreferences($user);
    }

    public function getUserPreferences(User $user): array
    {
        $preferences = $this->preferenceRepository->findByUser($user);
        if (!$preferences) {
            $preferences = $this->preferenceRepository->create($user);
        }

        return [
            'categories' => $preferences->categories ?? [],
            'sources' => $preferences->sources ?? [],
            'authors' => $preferences->authors ?? []
        ];
    }

    public function getPersonalisedFeed(User $user, int $perPage = 15): object
    {
        $preferences = $this->preferenceRepository->findByUser($user);
        $filters = [];

        if (!empty($preferences->categories)) {
            $filters['category'] = $preferences->categories;
        }

        if (!empty($preferences->sources)) {
            $filters['source'] = $preferences->sources;
        }

        if (!empty($preferences->authors)) {
            $filters['author'] = $preferences->authors;
        }

        return $this->articleRepository->paginate($perPage, $filters);
    }

    public function getPreferenceOptions(): object
    {
        return $this->preferenceRepository->getPreferenceOptions();
    }
}
