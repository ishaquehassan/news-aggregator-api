<?php

namespace App\Http\Controllers\API\UserPreferences;

use App\Contracts\Articles\ArticleRepositoryInterface;
use App\Contracts\UserPreferences\UserPreferenceRepositoryInterface;
use App\Http\Controllers\API\BaseController;
use App\Http\Requests\UserPreferences\UpdateUserPreferenceRequest;
use App\Services\UserPreferenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

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

        return $this->sendJsonResponse('Preferences updated successfully.', $preferences);
    }

    public function getPersonalizedFeed(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return $this->sendJsonResponse(
                'Invalid pagination parameters.',
                $validator->errors(),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $perPage = (int) $request->get('per_page', 15);
        $articles = $this->preferenceService->getPersonalisedFeed($request->user(), $perPage);

        return $this->sendJsonResponse('Personalized feed retrieved', $articles);
    }

    public function getPreferenceOptions(): JsonResponse
    {
        $result = $this->preferenceService->getPreferenceOptions();

        return $this->sendJsonResponse('Following are the options to select for personalized news feed',$result->toArray());
    }
}
