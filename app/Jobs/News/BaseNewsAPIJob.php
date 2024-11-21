<?php

namespace App\Jobs\News;

use App\Models\Article;
use App\Models\Category;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

abstract class BaseNewsAPIJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Client $client;
    protected string $serviceName;
    protected string $category;
    protected array $serviceConfig;

    public function __construct(string $serviceName, string $category)
    {
        $this->serviceName = $serviceName;
        $this->category = $category;
        $this->serviceConfig = config("services.news_apis.services")[$serviceName];
        $this->client = new Client([
            'base_uri' => $this->serviceConfig['url'],
            'timeout' => 30,
        ]);
    }

    abstract protected function fetchArticles(): array;

    protected function saveArticles(array $articles): void
    {
        foreach ($articles as $article) {
            try {
                $articleMapping = [
                    'category' => null
                ];
                $articleMapping = array_merge($articleMapping, $this->serviceConfig['mapping']);

                $articleMappingProcessors = [];
                foreach ($articleMapping as $key => $value) {
                    $articleMappingProcessors[$key] = null;
                    if (is_array($value)) {
                        $articleMappingProcessors[$key] = array_values($value)[0];
                        $articleMapping[$key] = array_keys($value)[0];
                    }
                }

                if (!getNestedValue($article, $articleMapping['content'], $articleMappingProcessors['content'])) {
                    continue;
                }

                $category = Category::firstOrCreate(['name' => getNestedValue($article, $articleMapping['category'] ?? '') ?? $this->category]);
                Article::updateOrCreate(
                    [
                        'title' => getNestedValue($article, $articleMapping['title'], $articleMappingProcessors['title']),
                        'source' => getNestedValue($article, $articleMapping['source'], $articleMappingProcessors['source'])
                    ],
                    [
                        'title' => getNestedValue($article, $articleMapping['title'], $articleMappingProcessors['title']),
                        'content' => getNestedValue($article, $articleMapping['content'], $articleMappingProcessors['content']),
                        'author' => getNestedValue($article, $articleMapping['author'], $articleMappingProcessors['author']),
                        'source' => getNestedValue($article, $articleMapping['source'], $articleMappingProcessors['source']),
                        'published_at' => Carbon::parse(getNestedValue($article, $articleMapping['published_at'], $articleMappingProcessors['published_at'])),
                        "category_id" => $category->id,
                    ]
                );
            } catch (Exception $e) {
                Log::error("Failed to save article from {$this->serviceName}: " . $e->getMessage());
            }
        }
    }
}
