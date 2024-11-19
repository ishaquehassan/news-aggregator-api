<?php
namespace App\Http\Controllers\API\Articles;

use App\Http\Controllers\API\BaseController;
use App\Services\ArticleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class ArticleController extends BaseController
{
    public function __construct(
        private readonly ArticleService $articleService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);

        $filters = $request->only([
            'keyword',
            'category',
            'author',
            'source',
            'date_from',
            'date_to'
        ]);

        $articles = $this->articleService->getArticles($perPage, $filters);

        return $this->sendJsonResponse('Articles retrieved successfully.', $articles);
    }

    public function show(int $id): JsonResponse
    {
        try {
            $article = $this->articleService->getArticle($id);
            return $this->sendJsonResponse('Article retrieved successfully.', $article);
        } catch (RuntimeException $e) {
            return $this->sendJsonResponse($e->getMessage(), statusCode: Response::HTTP_NOT_FOUND);
        }
    }
}
