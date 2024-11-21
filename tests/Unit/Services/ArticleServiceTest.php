<?php

namespace Tests\Unit\Services;

use App\Contracts\Articles\ArticleRepositoryInterface;
use App\Models\Article;
use App\Services\ArticleService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Mockery;
use RuntimeException;

class ArticleServiceTest extends TestCase
{
    private ArticleRepositoryInterface $repository;
    private ArticleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = Mockery::mock(ArticleRepositoryInterface::class);
        $this->service = new ArticleService($this->repository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_gets_paginated_articles()
    {
        $expectedResult = (object)['data' => []];
        $filters = ['keyword' => 'test'];

        $this->repository->shouldReceive('paginate')
            ->once()
            ->with(15, $filters)
            ->andReturn($expectedResult);

        $result = $this->service->getArticles(15, $filters);

        $this->assertEquals($expectedResult, $result);
    }

    #[Test]
    public function it_gets_single_article()
    {
        $article = (object)['id' => 1, 'title' => 'Test Article'];

        $this->repository->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($article);

        $result = $this->service->getArticle(1);

        $this->assertEquals($article, $result);
    }

    #[Test]
    public function it_throws_exception_when_article_not_found()
    {
        $this->repository->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturnNull();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Article not found');

        $this->service->getArticle(1);
    }

    #[Test]
    public function it_creates_article()
    {
        // Mock the Article model for input
        $inputArticle = Mockery::mock(Article::class);

        // Set up repository expectation to return boolean
        $this->repository->shouldReceive('create')
            ->once()
            ->with($inputArticle)
            ->andReturn(true);

        $result = $this->service->createArticle($inputArticle);

        $this->assertTrue($result);
    }
}
