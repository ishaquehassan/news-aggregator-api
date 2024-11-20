<?php

namespace App\Jobs\News;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class NewsAPIJob extends BaseNewsAPIJob
{
    public function handle(): void
    {
        $articles = $this->fetchArticles();
        $this->saveArticles($articles);
    }

    protected function fetchArticles(): array
    {
        $rateLimiterKey = "news-api-{$this->serviceName}";

        return RateLimiter::attempt(
            key: $rateLimiterKey,
            maxAttempts: $this->getRateLimit(),
            callback: fn() => $this->makeApiCall(),
        ) ? $this->makeApiCall() : [];
    }

    private function getRateLimit(): int
    {
        return config("services.news_apis.services.{$this->serviceName}.rate_limit", 30);
    }

    private function makeApiCall(): array
    {
        try {
            $response = $this->client->get($this->serviceConfig['endpoint'], [
                'query' => [
                    ...$this->serviceConfig['queryParams'],
                    $this->serviceConfig['search_key'] => $this->category
                ]
            ]);
            $data = json_decode($response->getBody()->getContents(), true);
            return data_get($data, $this->serviceConfig['listKey']) ?? [];
        } catch (Exception $e) {
            Log::error("Failed to fetch from {$this->serviceName}: " . $e->getMessage());
            return [];
        }
    }
}
