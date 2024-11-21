<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\UserPreferences\UpdateUserPreferenceRequest;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class UpdateUserPreferenceRequestTest extends TestCase
{
    private UpdateUserPreferenceRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new class extends UpdateUserPreferenceRequest {
            public array $testData = [];

            public function validated($key = null, $default = null): mixed
            {
                return $this->testData;
            }
        };
    }

    #[Test]
    public function it_has_correct_validation_rules()
    {
        $rules = $this->request->rules();

        $this->assertArrayHasKey('categories', $rules);
        $this->assertArrayHasKey('sources', $rules);
        $this->assertArrayHasKey('authors', $rules);

        $this->assertEquals('array', $rules['categories']);
        $this->assertEquals('array', $rules['sources']);
        $this->assertEquals('array', $rules['authors']);

        $this->assertContains('distinct', $rules['categories.*']);
        $this->assertContains('distinct', $rules['sources.*']);
        $this->assertContains('distinct', $rules['authors.*']);

        $this->assertContains('exists:categories,id', $rules['categories.*']);
        $this->assertContains('exists:articles,source', $rules['sources.*']);
        $this->assertContains('exists:articles,author', $rules['authors.*']);
    }

    #[Test]
    public function it_returns_correct_validation_messages()
    {
        $messages = $this->request->messages();

        $this->assertEquals(
            'Each category can only be selected once.',
            $messages['categories.*.distinct']
        );
        $this->assertEquals(
            'Each source can only be selected once.',
            $messages['sources.*.distinct']
        );
        $this->assertEquals(
            'Each author can only be selected once.',
            $messages['authors.*.distinct']
        );
    }

    #[Test]
    public function it_returns_correct_summary_message_for_single_field()
    {
        $this->request->testData = ['categories' => [1, 2]];

        $this->assertEquals(
            'Categories preferences updated successfully.',
            $this->request->getSummaryMessage()
        );
    }

    #[Test]
    public function it_returns_correct_summary_message_for_multiple_fields()
    {
        $this->request->testData = [
            'categories' => [1, 2],
            'authors' => ['author1']
        ];

        $this->assertEquals(
            'Categories and Authors preferences updated successfully.',
            $this->request->getSummaryMessage()
        );
    }

    #[Test]
    public function it_returns_correct_summary_message_for_no_updates()
    {
        $this->request->testData = [
            'categories' => [],
            'authors' => [],
            'sources' => []
        ];

        $this->assertEquals(
            'No preferences were updated.',
            $this->request->getSummaryMessage()
        );
    }

    #[Test]
    public function it_allows_empty_arrays()
    {
        $rules = $this->request->rules();

        $this->assertEquals('array', $rules['categories']);
        $this->assertEquals('array', $rules['sources']);
        $this->assertEquals('array', $rules['authors']);

        foreach ($rules as $field => $rule) {
            if (is_string($rule)) {
                $this->assertStringNotContainsString('required', $rule);
            } else {
                $this->assertNotContains('required', $rule);
            }
        }
    }
}
