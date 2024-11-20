<?php

use Illuminate\Support\Carbon;

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'news_apis' => [
        'categories' => [
            'General',
            'Breaking News',
            'World News',
            'Politics',
            'Business',
            'Technology',
            'Science',
            'Health',
            'Sports',
            'Entertainment',
            'Lifestyle',
            'Education',
            'Environment',
            'Arts & Culture',
            'Travel',
            'Food & Dining',
            'Opinion',
            'Weather',
            'Crime',
            'Real Estate',
            'Markets'
        ],
        "services" => [
            "newsapi.org" => [
                'url' => "https://newsapi.org/v2/",
                'rate_limit' => 10,
                'queryParams' => [
                    'from' => Carbon::now()->subDay()->format('Y-m-d'),
                    'sortBy' => 'popularity',
                    'apiKey' => "3a7802822f05495faa60a3074103cdd8"
                ],
                'search_key' => "q",
                "endpoint" => "everything",
                "listKey" => "articles",
                "mapping" => [
                    'title' => 'title',
                    'content' => 'content|description',
                    'author' => 'author',
                    'source' => 'source.name',
                    'published_at' => 'publishedAt',
                ]
            ],
            "theguardian.com" => [
                'url' => "https://content.guardianapis.com/",
                'rate_limit' => 10,
                'queryParams' => [
                    'order-by' => "newest",
                    'api-key' => "1b77f328-5318-458b-ac4d-5712115a7d0a",
                    'show-fields' => 'all',
                ],
                'search_key' => "q",
                "endpoint" => "search",
                "listKey" => "response.results",
                "mapping" => [
                    'title' => 'fields.headline',
                    'content' => 'fields.body',
                    'author' => 'fields.byline',
                    'source' => 'fields.publication',
                    'published_at' => 'fields.firstPublicationDate',
                ]
            ],
            "newsdata.io" => [
                'url' => "https://newsdata.io/api/1/",
                'rate_limit' => 10,
                'queryParams' => [
                    'from_date' => Carbon::now()->subDay()->format('Y-m-d'),
                    'apikey' => 'pub_5972384bb4d9efc9d22314a55be29f22772ce',
                ],
                'search_key' => "q",
                "endpoint" => "archive",
                "listKey" => "results",
                "mapping" => [
                    'title' => 'title',
                    'content' => 'description',
                    'author' => 'creator.0|source_name',
                    'source' => 'source_name',
                    'published_at' => 'pubDate',
                ]
            ],
            "thenewsapi.com" => [
                'url' => "https://api.thenewsapi.com/v1/",
                'rate_limit' => 10,
                'queryParams' => [
                    'limit' => 3,
                    'api_token' => 'AHaeSULJGws0JZtfnewtfr3A68hoEjLPlwKAXVi7',
                ],
                'search_key' => "search",
                "endpoint" => "news/all",
                "listKey" => "data",
                "mapping" => [
                    'title' => 'title',
                    'content' => 'description',
                    'author' => 'source',
                    'source' => 'source',
                    'published_at' => 'published_at',
                ]
            ],
            "worldnewsapi.com" => [
                'url' => "https://api.worldnewsapi.com/",
                'rate_limit' => 10,
                'queryParams' => [
                    'number' => 10,
                    'sortBy' => 'publish-time',
                    'api-key' => "8e978e1a1f4e4c968e4022ff2371e436"
                ],
                'search_key' => "text",
                "endpoint" => "search-news",
                "listKey" => "news",
                "mapping" => [
                    'title' => 'title',
                    'content' => 'text',
                    'author' => 'author',
                    'source' => ['url' => fn($value) => extract_domain($value)],
                    'published_at' => 'publish_date',
                ]
            ],
        ]
    ],
];
