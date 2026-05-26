<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeSynonym extends Model
{
    protected $fillable = ['website_id', 'term', 'synonyms'];

    protected function casts(): array
    {
        return ['synonyms' => 'array'];
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }
}
