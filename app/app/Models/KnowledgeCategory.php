<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeCategory extends Model
{
    protected $fillable = ['website_id', 'name', 'slug', 'description', 'sort_order'];

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    public function articles(): HasMany
    {
        return $this->hasMany(KnowledgeArticle::class);
    }
}
