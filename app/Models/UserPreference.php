<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    protected $fillable = ['user_id', 'sources', 'authors', 'categories'];

    protected $casts = [
        'sources' => 'array',
        'authors' => 'array',
        'categories' => 'array'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
