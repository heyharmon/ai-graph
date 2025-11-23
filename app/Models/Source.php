<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Source extends Model
{
    protected $fillable = [
        'knowledge_graph_id',
        'url',
        'title',
        'status',
        'discovered_at',
    ];

    protected function casts(): array
    {
        return [
            'discovered_at' => 'datetime',
        ];
    }

    public function knowledgeGraph(): BelongsTo
    {
        return $this->belongsTo(KnowledgeGraph::class);
    }
}
