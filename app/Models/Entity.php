<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Entity extends Model
{
    protected $fillable = [
        'graph_id',
        'type',
        'name',
        'description',
        'attributes',
    ];

    protected function casts(): array
    {
        return [
            'attributes' => 'array',
        ];
    }

    public function graph(): BelongsTo
    {
        return $this->belongsTo(Graph::class);
    }

    public function sourcePages(): BelongsToMany
    {
        return $this->belongsToMany(SourcePage::class, 'entity_source_page');
    }

    public function sourceRelationships(): HasMany
    {
        return $this->hasMany(Relationship::class, 'source_entity_id');
    }

    public function targetRelationships(): HasMany
    {
        return $this->hasMany(Relationship::class, 'target_entity_id');
    }

    public function relationships(): HasMany
    {
        return $this->sourceRelationships()->union($this->targetRelationships());
    }
}
