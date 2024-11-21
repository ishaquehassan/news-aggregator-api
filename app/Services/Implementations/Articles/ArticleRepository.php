<?php
namespace App\Services\Implementations\Articles;

use App\Contracts\Articles\ArticleRepositoryInterface;
use App\Models\Article;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class ArticleRepository implements ArticleRepositoryInterface
{
    public function paginate(int $perPage = 15, array $filters = []): object
    {
        $query = Article::with(['category']);

        $query = $this->applyFilters($query, $filters);

        return $query->latest('published_at')->paginate($perPage);
    }

    public function findById(int $id): ?object
    {
        return Article::with(['category'])->find($id);
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
            if(is_array($filters['author'])){
                $query->orWhereIn('author', $filters['author']);
            }else{
                $query->where('author', $filters['author']);
            }
        }

        if (!empty($filters['category'])) {
            if(is_array($filters['category'])){
                $query->orWhereIn('category_id', $filters['category']);
            }else{
                $query->where('category_id', $filters['category']);
            }
        }

        if (!empty($filters['source'])) {
            if(is_array($filters['source'])){
                $query->orWhereIn('source', $filters['source']);
            }else{
                $query->where('source', $filters['source']);
            }
        }

        if (!empty($filters['date_from'])) {
            $query->where('published_at', '>=', Carbon::parse($filters['date_from'])->startOfDay());
        }

        if (!empty($filters['date_to'])) {
            $query->where('published_at', '<=', Carbon::parse($filters['date_to'])->endOfDay());
        }

        return $query;
    }
}
