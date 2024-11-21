<?php

namespace App\Contracts\Articles;

use App\Models\Article;

interface ArticleRepositoryInterface
{
    /**
     * Get paginated articles with optional filters
     *
     * @param int $perPage
     * @param array $filters
     * @return object
     */
    public function paginate(int $perPage, array $filters = []): object;

    /**
     * Find article by ID
     *
     * @param int $id
     * @return object|null
     */
    public function findById(int $id): ?object;

    /**
     * Create a new article
     *
     * @param Article $article
     * @return bool
     */
    public function create(Article $article): bool;
}
