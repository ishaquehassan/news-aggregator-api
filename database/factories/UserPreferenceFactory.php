<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserPreferenceFactory extends Factory
{
    protected $model = UserPreference::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'categories' => $this->faker->randomElements(['tech', 'science', 'health', 'business'], 2),
            'sources' => $this->faker->randomElements(['newsapi.org', 'theguardian.com', 'newsdata.io'], 2),
            'authors' => $this->faker->randomElements([$this->faker->name(), $this->faker->name(), $this->faker->name()], 2)
        ];
    }

    /**
     * Empty preferences state
     */
    public function empty(): static
    {
        return $this->state(fn (array $attributes) => [
            'categories' => [],
            'sources' => [],
            'authors' => []
        ]);
    }

    /**
     * Tech focused preferences
     */
    public function techFocused(): static
    {
        return $this->state(fn (array $attributes) => [
            'categories' => ['tech', 'science'],
            'sources' => ['techcrunch.com', 'wired.com'],
            'authors' => ['Tech Writer', 'Science Reporter']
        ]);
    }
}
