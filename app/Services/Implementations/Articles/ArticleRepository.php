<?php
namespace App\Services\Implementations\Articles;

use App\Contracts\Articles\ArticleRepositoryInterface;
use App\Models\Article;
use Illuminate\Database\Eloquent\Builder;

class ArticleRepository implements ArticleRepositoryInterface
{
    public function paginate(int $perPage = 15, array $filters = []): object
    {
        $query = Article::with(['user', 'category','author']);

        $query = $this->applyFilters($query, $filters);

        return $query->latest('published_at')->paginate($perPage);
    }

    public function findById(int $id): ?object
    {
        return Article::with(['user', 'category'])->find($id);
    }

    /**
     * @throws \Throwable
     */
    public function create(Article $article): bool
    {
        return $article->saveOrFail();
    }

    /**
     * Apply filters to the query
     *
     * @param Builder $query
     * @param array $filters
     * @return Builder
     */
    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (!empty($filters['keyword'])) {
            $keyword = $filters['keyword'];
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'LIKE', "%{$keyword}%")
                    ->orWhere('content', 'LIKE', "%{$keyword}%");
            });
        }

        if (!empty($filters['author'])) {
            $query->where('author', $filters['author']);
        }

        if (!empty($filters['category'])) {
            $query->where('category_id', $filters['category']);
        }

        if (!empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('published_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('published_at', '<=', $filters['date_to']);
        }

        return $query;
    }
}
