<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SourcePage extends Model
{
    protected $fillable = [
        'graph_id',
        'url',
        'title',
        'content_snippet',
    ];

    public function graph(): BelongsTo
    {
        return $this->belongsTo(Graph::class);
    }

    public function entities(): BelongsToMany
    {
        return $this->belongsToMany(Entity::class, 'entity_source_page');
    }
}
