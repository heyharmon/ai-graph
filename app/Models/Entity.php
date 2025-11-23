<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Entity extends Model
{
    protected $fillable = [
        'graph_id',
        'type',
        'name',
        'normalized_name',
        'attributes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'attributes' => 'array',
            'metadata' => 'array',
        ];
    }

    public function graph(): BelongsTo
    {
        return $this->belongsTo(Graph::class);
    }

    public function outgoingRelationships(): HasMany
    {
        return $this->hasMany(Relationship::class, 'from_entity_id');
    }

    public function incomingRelationships(): HasMany
    {
        return $this->hasMany(Relationship::class, 'to_entity_id');
    }
}
