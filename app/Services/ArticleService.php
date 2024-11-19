<?php

namespace App\Services;

use App\Contracts\Articles\ArticleRepositoryInterface;
use App\Models\Article;
use RuntimeException;

readonly class ArticleService
{
    public function __construct(private ArticleRepositoryInterface $articleRepository)
    {
    }

    public function getArticles(int $perPage = 15, array $filters = []): object
    {
        return $this->articleRepository->paginate($perPage, $filters);
    }

    public function getArticle(int $id): object
    {
        $article = $this->articleRepository->findById($id);

        if (!$article) {
            throw new RuntimeException('Article not found');
        }

        return $article;
    }

    public function createArticle(Article $data): object
    {
        return $this->articleRepository->create($data);
    }
}
