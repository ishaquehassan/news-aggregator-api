<?php

namespace App\Http\Resources;

class UserPreferenceOptionsResource
{
    public function __construct(
        public array $authors,
        public array $sources,
        public array $categories
    ) {}

    public function toArray(): array
    {
        return [
            'authors' => $this->authors,
            'sources' => $this->sources,
            'categories' => $this->categories
        ];
    }
}
