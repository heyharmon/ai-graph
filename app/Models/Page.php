<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Page extends Model
{
    protected $fillable = [
        'graph_id',
        'url',
        'page_type',
        'title',
        'content',
        'crawled_at',
    ];

    protected function casts(): array
    {
        return [
            'crawled_at' => 'datetime',
        ];
    }

    public function graph(): BelongsTo
    {
        return $this->belongsTo(Graph::class);
    }
}
