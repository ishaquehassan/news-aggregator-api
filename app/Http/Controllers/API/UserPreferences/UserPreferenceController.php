<?php

namespace App\Http\Controllers\API\UserPreferences;

use App\Contracts\Articles\ArticleRepositoryInterface;
use App\Contracts\UserPreferences\UserPreferenceRepositoryInterface;
use App\Http\Controllers\API\BaseController;
use App\Http\Requests\UserPreferences\UpdateUserPreferenceRequest;
use App\Services\UserPreferenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserPreferenceController extends BaseController
{
    private UserPreferenceService $preferenceService;

    public function __construct(
        ArticleRepositoryInterface        $articleRepository,
        UserPreferenceRepositoryInterface $preferenceRepository
    )
    {
        $this->preferenceService = new UserPreferenceService($articleRepository, $preferenceRepository);
    }

    public function getPreferences(Request $request): JsonResponse
    {
        $preferences = $this->preferenceService->getUserPreferences($request->user());
        return $this->sendJsonResponse('User preferences retrieved', $preferences);
    }

    public function updatePreferences(UpdateUserPreferenceRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $preferences = $this->preferenceService->updatePreferences(
            $request->user(),
            $validated['categories'] ?? [],
            $validated['sources'] ?? [],
            $validated['authors'] ?? []
        );

        return $this->sendJsonResponse($request->getSummaryMessage(), $preferences);
    }

    public function getPersonalizedFeed(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $articles = $this->preferenceService->getPersonalisedFeed($request->user(), $perPage);
        return $this->sendJsonResponse('Personalized feed retrieved', $articles);
    }
}
