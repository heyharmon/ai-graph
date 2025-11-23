<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Graph extends Model
{
    protected $fillable = [
        'user_id',
        'website_url',
        'name',
        'status',
        'error_message',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    public function entities(): HasMany
    {
        return $this->hasMany(Entity::class);
    }

    public function relationships(): HasMany
    {
        return $this->hasMany(Relationship::class);
    }
}
