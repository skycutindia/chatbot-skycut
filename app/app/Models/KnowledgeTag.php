<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class KnowledgeTag extends Model
{
    protected $fillable = ['website_id', 'name'];

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(KnowledgeArticle::class, 'knowledge_article_tag');
    }
}
