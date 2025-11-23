<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Relationship extends Model
{
    protected $fillable = [
        'graph_id',
        'from_entity_id',
        'to_entity_id',
        'type',
        'strength',
        'source_urls',
    ];

    protected function casts(): array
    {
        return [
            'strength' => 'float',
            'source_urls' => 'array',
        ];
    }

    public function graph(): BelongsTo
    {
        return $this->belongsTo(Graph::class);
    }

    public function fromEntity(): BelongsTo
    {
        return $this->belongsTo(Entity::class, 'from_entity_id');
    }

    public function toEntity(): BelongsTo
    {
        return $this->belongsTo(Entity::class, 'to_entity_id');
    }
}
