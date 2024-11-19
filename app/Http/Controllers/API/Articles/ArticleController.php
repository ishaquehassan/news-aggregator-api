<?php
namespace App\Http\Controllers\API\Articles;

use App\Http\Controllers\API\BaseController;
use App\Services\ArticleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
            'content' => 'required',
            'category_id' => 'required|exists:categories,id',
            'source' => 'required|max:255',
            'published_at' => 'required|date'
        ]);

        if ($validator->fails()) {
            return $this->sendJsonResponse('Invalid Request.', $validator->errors(), Response::HTTP_BAD_REQUEST);
        }

        try {
            $article = $this->articleService->createArticle($request->all());
            return $this->sendJsonResponse('Article created successfully.', $article, Response::HTTP_CREATED);
        } catch (RuntimeException $e) {
            return $this->sendJsonResponse($e->getMessage(), statusCode: Response::HTTP_BAD_REQUEST);
        }
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

    public function destroy(int $id): JsonResponse
    {
        try {
            $success = $this->articleService->deleteArticle($id);

            if (!$success) {
                return $this->sendJsonResponse('Failed to delete article.', statusCode: Response::HTTP_BAD_REQUEST);
            }

            return $this->sendJsonResponse('Article deleted successfully.');
        } catch (RuntimeException $e) {
            return $this->sendJsonResponse($e->getMessage(), statusCode: Response::HTTP_NOT_FOUND);
        }
    }
}
