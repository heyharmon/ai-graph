<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeGraph extends Model
{
    protected $fillable = [
        'user_id',
        'website_url',
        'sources_count',
    ];

    protected function casts(): array
    {
        return [
            'sources_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sources(): HasMany
    {
        return $this->hasMany(Source::class);
    }
}
