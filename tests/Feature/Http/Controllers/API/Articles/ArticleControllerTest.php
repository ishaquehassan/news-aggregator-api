<?php

use App\Models\Article;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function() {
    $this->category = Category::factory()->create();
});

test('it lists articles with pagination', function() {
    Article::factory(3)->create();

    $response = get('/api/articles');

    $response->assertOk()
        ->assertJson([
            'message' => 'Articles retrieved successfully.',
            'data' => [
                'current_page' => 1,
                'per_page' => 15,
                'total' => 3
            ]
        ])
        ->assertJsonCount(3, 'data.data');
});

test('it respects per page parameter', function() {
    Article::factory(5)->create();

    $response = get('/api/articles?per_page=2');

    $response->assertOk()
        ->assertJson([
            'data' => [
                'per_page' => 2,
                'total' => 5
            ]
        ])
        ->assertJsonCount(2, 'data.data');
});

test('it filters articles by keyword', function() {
    // Create articles with specific content
    $matchingTitle = Article::factory()->create([
        'title' => 'Test Article One'
    ]);

    $matchingContent = Article::factory()->create([
        'title' => 'Regular Title',
        'content' => 'This is a test content'
    ]);

    $nonMatching = Article::factory()->create([
        'title' => 'Another Article',
        'content' => 'Regular content'
    ]);

    $response = get('/api/articles?keyword=test');

    $response->assertOk()
        ->assertJsonCount(2, 'data.data')
        ->assertJson([
            'data' => [
                'data' => [
                    [
                        'id' => $matchingTitle->id,
                        'title' => 'Test Article One'
                    ],
                    [
                        'id' => $matchingContent->id,
                        'title' => 'Regular Title'
                    ]
                ]
            ]
        ]);
});

test('it filters articles by category', function() {
    $otherCategory = Category::factory()->create();

    $matchingArticle = Article::factory()->create(['category_id' => $this->category->id]);
    $nonMatchingArticle = Article::factory()->create(['category_id' => $otherCategory->id]);

    $response = get("/api/articles?category={$this->category->id}");

    $response->assertOk()
        ->assertJsonCount(1, 'data.data')
        ->assertJson([
            'data' => [
                'data' => [
                    [
                        'id' => $matchingArticle->id,
                        'category_id' => $this->category->id
                    ]
                ]
            ]
        ]);
});

test('it filters articles by author', function() {
    $matchingArticle = Article::factory()->create(['author' => 'John Doe']);
    $nonMatchingArticle = Article::factory()->create(['author' => 'Jane Smith']);

    $response = get('/api/articles?author=John+Doe');

    $response->assertOk()
        ->assertJsonCount(1, 'data.data')
        ->assertJson([
            'data' => [
                'data' => [
                    [
                        'id' => $matchingArticle->id,
                        'author' => 'John Doe'
                    ]
                ]
            ]
        ]);
});

test('it filters articles by source', function() {
    $matchingArticle = Article::factory()->create(['source' => 'BBC News']);
    $nonMatchingArticle = Article::factory()->create(['source' => 'CNN']);

    $response = get('/api/articles?source=BBC+News');

    $response->assertOk()
        ->assertJsonCount(1, 'data.data')
        ->assertJson([
            'data' => [
                'data' => [
                    [
                        'id' => $matchingArticle->id,
                        'source' => 'BBC News'
                    ]
                ]
            ]
        ]);
});

test('it filters articles by date range', function() {
    $beforeRange = Article::factory()->create(['published_at' => '2024-01-01 12:00:00']);
    $withinRange = Article::factory()->create(['published_at' => '2024-02-01 12:00:00']);
    $afterRange = Article::factory()->create(['published_at' => '2024-03-01 12:00:00']);

    $response = get('/api/articles?date_from=2024-01-15&date_to=2024-02-15');

    $response->assertOk()
        ->assertJsonCount(1, 'data.data')
        ->assertJson([
            'data' => [
                'data' => [
                    [
                        'id' => $withinRange->id,
                    ]
                ]
            ]
        ]);
});

test('it shows single article', function() {
    $article = Article::factory()->create([
        'category_id' => $this->category->id
    ]);

    $response = get("/api/articles/{$article->id}");

    $response->assertOk()
        ->assertJson([
            'message' => 'Article retrieved successfully.',
            'data' => [
                'id' => $article->id,
                'title' => $article->title,
                'content' => $article->content,
                'author' => $article->author,
                'source' => $article->source,
                'category_id' => $this->category->id
            ]
        ]);
});

test('it returns 404 for non-existent article', function() {
    $response = get('/api/articles/999');

    $response->assertNotFound()
        ->assertJson([
            'message' => 'Article not found'
        ]);
});

test('it properly orders articles by published date', function() {
    $oldArticle = Article::factory()->create([
        'published_at' => now()->subDays(2)
    ]);

    $newArticle = Article::factory()->create([
        'published_at' => now()
    ]);

    $response = get('/api/articles');

    $response->assertOk()
        ->assertJson([
            'data' => [
                'data' => [
                    [
                        'id' => $newArticle->id
                    ],
                    [
                        'id' => $oldArticle->id
                    ]
                ]
            ]
        ]);
});

test('it combines multiple filters', function() {
    $matchingArticle = Article::factory()->create([
        'author' => 'John Doe',
        'source' => 'BBC News',
        'published_at' => '2024-01-01 12:00:00'
    ]);

    $partialMatch = Article::factory()->create([
        'author' => 'John Doe',
        'source' => 'CNN',
        'published_at' => '2024-02-01 12:00:00'
    ]);

    $response = get('/api/articles?author=John+Doe&source=BBC+News&date_from=2024-01-01');

    $response->assertOk()
        ->assertJsonCount(1, 'data.data')
        ->assertJson([
            'data' => [
                'data' => [
                    [
                        'id' => $matchingArticle->id,
                        'source' => 'BBC News'
                    ]
                ]
            ]
        ]);
});

test('empty result set returns proper structure', function() {
    $response = get('/api/articles?author=NonExistentAuthor');

    $response->assertOk()
        ->assertJson([
            'message' => 'Articles retrieved successfully.',
            'data' => [
                'data' => [],
                'total' => 0,
                'per_page' => 15,
                'current_page' => 1
            ]
        ]);
});
