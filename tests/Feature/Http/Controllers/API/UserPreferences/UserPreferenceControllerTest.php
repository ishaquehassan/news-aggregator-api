<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use App\Models\UserPreference;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('Preferences API Authentication', function() {
    it('requires authentication for all preferences routes', function() {
        $this->getJson('/api/preferences')
            ->assertUnauthorized();

        $this->putJson('/api/preferences')
            ->assertUnauthorized();

        $this->getJson('/api/preferences/feed')
            ->assertUnauthorized();
    });
});

describe('GET /api/preferences', function() {
    beforeEach(function() {
        Sanctum::actingAs($this->user);
    });

    it('returns empty preferences for new user', function() {
        $response = $this->getJson('/api/preferences');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'categories' => [],
                    'sources' => [],
                    'authors' => []
                ]
            ]);
    });

    it('returns existing preferences for user', function() {
        $category = Category::create(['name' => 'Tech']);

        UserPreference::create([
            'user_id' => $this->user->id,
            'categories' => [$category->id],
            'sources' => ['test-source'],
            'authors' => ['test-author']
        ]);

        $response = $this->getJson('/api/preferences');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'categories',
                    'sources',
                    'authors'
                ]
            ]);
    });
});

describe('PUT /api/preferences', function() {
    beforeEach(function() {
        Sanctum::actingAs($this->user);

        $this->category = Category::create(['name' => 'Test Category']);

        Article::create([
            'title' => 'Test Article',
            'content' => 'Content',
            'category_id' => $this->category->id,
            'source' => 'test-source',
            'author' => 'test-author',
            'published_at' => now()
        ]);
    });

    it('creates new preferences', function() {
        $preferences = [
            'categories' => [$this->category->id],
            'sources' => ['test-source'],
            'authors' => ['test-author']
        ];

        $response = $this->putJson('/api/preferences', $preferences);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'categories',
                    'sources',
                    'authors'
                ]
            ]);
    });

    it('updates existing preferences', function() {
        UserPreference::create([
            'user_id' => $this->user->id,
            'categories' => [],
            'sources' => [],
            'authors' => []
        ]);

        $preferences = [
            'categories' => [$this->category->id],
            'sources' => ['test-source'],
            'authors' => ['test-author']
        ];

        $response = $this->putJson('/api/preferences', $preferences);
        $response->assertOk();
    });

    it('validates non-existent values', function() {
        $preferences = [
            'categories' => [999],
            'sources' => ['non-existent-source'],
            'authors' => ['non-existent-author']
        ];

        $response = $this->putJson('/api/preferences', $preferences);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors'
            ]);
    });
});

describe('GET /api/preferences/feed', function() {
    beforeEach(function() {
        Sanctum::actingAs($this->user);

        $this->category = Category::create(['name' => 'Test Category']);

        // Create test articles
        for ($i = 1; $i <= 10; $i++) {
            Article::create([
                'title' => "Test Article $i",
                'content' => "Content $i",
                'category_id' => $this->category->id,
                'source' => "test-source",
                'author' => "test-author",
                'published_at' => now()->subHours($i)
            ]);
        }
    });

    it('returns personalized feed based on preferences', function() {
        UserPreference::create([
            'user_id' => $this->user->id,
            'categories' => [$this->category->id],
            'sources' => ['test-source'],
            'authors' => ['test-author']
        ]);

        $response = $this->getJson('/api/preferences/feed');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'data',
                    'current_page',
                    'per_page'
                ]
            ]);
    });

    it('returns paginated results', function() {
        UserPreference::create([
            'user_id' => $this->user->id,
            'categories' => [$this->category->id],
            'sources' => ['test-source'],
            'authors' => ['test-author']
        ]);

        $response = $this->getJson('/api/preferences/feed?per_page=5');

        $response->assertOk();
        $data = $response->json('data');
        expect($data['per_page'])->toBe(5);
        expect(count($data['data']))->toBe(5);
    });

    it('returns all articles when no preferences exist', function() {
        $response = $this->getJson('/api/preferences/feed');

        $response->assertOk();
        $data = $response->json('data');
        expect(count($data['data']))->toBe(10);
    });

    it('filters by category preference', function() {
        $newCategory = Category::create(['name' => 'Another Category']);
        Article::create([
            'title' => 'Different Category Article',
            'content' => 'Content',
            'category_id' => $newCategory->id,
            'source' => 'test-source',
            'author' => 'test-author',
            'published_at' => now()
        ]);

        UserPreference::create([
            'user_id' => $this->user->id,
            'categories' => [$this->category->id],
            'sources' => [],
            'authors' => []
        ]);

        $response = $this->getJson('/api/preferences/feed');
        $data = $response->json('data.data');

        collect($data)->each(function ($article) {
            expect($article['category_id'])->toBe($this->category->id);
        });
    });

    it('handles invalid pagination parameters', function() {
        $invalidValues = ['invalid', '0', '101'];

        foreach ($invalidValues as $value) {
            $response = $this->getJson("/api/preferences/feed?per_page={$value}");
            $response->assertStatus(422);
        }
    });
});

describe('Edge Cases', function() {
    beforeEach(function() {
        Sanctum::actingAs($this->user);
    });

    it('handles user deletion cascade', function() {
        UserPreference::create([
            'user_id' => $this->user->id,
            'categories' => [],
            'sources' => [],
            'authors' => []
        ]);

        $this->user->delete();

        $this->assertDatabaseMissing('user_preferences', [
            'user_id' => $this->user->id
        ]);
    });
});
