<?php

namespace Tests\Unit\Services;

use App\Contracts\Articles\ArticleRepositoryInterface;
use App\Contracts\UserPreferences\UserPreferenceRepositoryInterface;
use App\Models\User;
use App\Services\UserPreferenceService;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class UserPreferenceServiceTest extends TestCase
{
    private ArticleRepositoryInterface $articleRepository;
    private UserPreferenceRepositoryInterface $preferenceRepository;
    private UserPreferenceService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->articleRepository = Mockery::mock(ArticleRepositoryInterface::class);
        $this->preferenceRepository = Mockery::mock(UserPreferenceRepositoryInterface::class);
        $this->service = new UserPreferenceService(
            $this->articleRepository,
            $this->preferenceRepository
        );

        // Create a proper User mock
        $this->user = Mockery::mock(User::class);
        $this->user->shouldReceive('getAttribute')->with('id')->andReturn(1);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test] public function it_returns_empty_preferences_for_new_user()
    {
        $this->preferenceRepository->shouldReceive('findByUser')
            ->once()
            ->with($this->user)
            ->andReturnNull();

        $this->preferenceRepository->shouldReceive('create')
            ->once()
            ->with($this->user)
            ->andReturn((object)[
                'categories' => [],
                'sources' => [],
                'authors' => []
            ]);

        $preferences = $this->service->getUserPreferences($this->user);

        $this->assertEquals([
            'categories' => [],
            'sources' => [],
            'authors' => []
        ], $preferences);
    }

    #[Test] public function it_returns_existing_preferences()
    {
        $existingPreferences = (object)[
            'categories' => [1, 2],
            'sources' => ['source1'],
            'authors' => ['author1']
        ];

        $this->preferenceRepository->shouldReceive('findByUser')
            ->once()
            ->with($this->user)
            ->andReturn($existingPreferences);

        $preferences = $this->service->getUserPreferences($this->user);

        $this->assertEquals([
            'categories' => [1, 2],
            'sources' => ['source1'],
            'authors' => ['author1']
        ], $preferences);
    }

    #[Test] public function it_updates_user_preferences()
    {
        $preferences = (object)[];
        $categories = [1, 2];
        $sources = ['source1'];
        $authors = ['author1'];

        $this->preferenceRepository->shouldReceive('firstOrCreate')
            ->once()
            ->with($this->user)
            ->andReturn($preferences);

        $this->preferenceRepository->shouldReceive('update')
            ->once()
            ->with($preferences, [
                'categories' => $categories,
                'sources' => $sources,
                'authors' => $authors
            ])
            ->andReturn(true);

        $this->preferenceRepository->shouldReceive('findByUser')
            ->once()
            ->with($this->user)
            ->andReturn((object)[
                'categories' => $categories,
                'sources' => $sources,
                'authors' => $authors
            ]);

        $result = $this->service->updatePreferences($this->user, $categories, $sources, $authors);

        $this->assertEquals([
            'categories' => $categories,
            'sources' => $sources,
            'authors' => $authors
        ], $result);
    }

    #[Test] public function it_gets_personalized_feed_with_all_preferences()
    {
        $preferences = (object)[
            'categories' => [1, 2],
            'sources' => ['source1'],
            'authors' => ['author1']
        ];

        $expectedFilters = [
            'category' => [1, 2],
            'source' => ['source1'],
            'author' => ['author1']
        ];

        $this->preferenceRepository->shouldReceive('findByUser')
            ->once()
            ->with($this->user)
            ->andReturn($preferences);

        $this->articleRepository->shouldReceive('paginate')
            ->once()
            ->with(15, $expectedFilters)
            ->andReturn((object)['data' => []]);

        $result = $this->service->getPersonalisedFeed($this->user);

        $this->assertIsObject($result);
    }

    #[Test] public function it_gets_personalized_feed_with_no_preferences()
    {
        $preferences = (object)[
            'categories' => [],
            'sources' => [],
            'authors' => []
        ];

        $this->preferenceRepository->shouldReceive('findByUser')
            ->once()
            ->with($this->user)
            ->andReturn($preferences);

        $this->articleRepository->shouldReceive('paginate')
            ->once()
            ->with(15, [])
            ->andReturn((object)['data' => []]);

        $result = $this->service->getPersonalisedFeed($this->user);

        $this->assertIsObject($result);
    }

    #[Test] public function it_respects_pagination_in_personalized_feed()
    {
        $preferences = (object)[
            'categories' => [1],
            'sources' => ['source1'],
            'authors' => ['author1']
        ];

        $perPage = 5;

        $this->preferenceRepository->shouldReceive('findByUser')
            ->once()
            ->with($this->user)
            ->andReturn($preferences);

        $this->articleRepository->shouldReceive('paginate')
            ->once()
            ->with($perPage, Mockery::type('array'))
            ->andReturn((object)[
                'data' => [],
                'per_page' => $perPage
            ]);

        $result = $this->service->getPersonalisedFeed($this->user, $perPage);

        $this->assertEquals($perPage, $result->per_page);
    }

    #[Test] public function it_handles_partial_preferences()
    {
        $preferences = (object)[
            'categories' => [1],
            'sources' => [],
            'authors' => ['author1']
        ];

        $expectedFilters = [
            'category' => [1],
            'author' => ['author1']
        ];

        $this->preferenceRepository->shouldReceive('findByUser')
            ->once()
            ->with($this->user)
            ->andReturn($preferences);

        $this->articleRepository->shouldReceive('paginate')
            ->once()
            ->with(15, $expectedFilters)
            ->andReturn((object)['data' => []]);

        $result = $this->service->getPersonalisedFeed($this->user);

        $this->assertIsObject($result);
    }

    #[Test] public function it_handles_null_preferences()
    {
        $this->preferenceRepository->shouldReceive('findByUser')
            ->once()
            ->with($this->user)
            ->andReturnNull();

        $this->articleRepository->shouldReceive('paginate')
            ->once()
            ->with(15, [])
            ->andReturn((object)['data' => []]);

        $result = $this->service->getPersonalisedFeed($this->user);

        $this->assertIsObject($result);
    }
}
