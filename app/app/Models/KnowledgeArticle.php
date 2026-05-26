<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class KnowledgeArticle extends Model
{
    protected $fillable = [
        'website_id', 'knowledge_category_id', 'title', 'slug',
        'content', 'source_url', 'is_published', 'view_count', 'embedding', 'embedded_at',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'embedding' => 'array',
            'embedded_at' => 'datetime',
        ];
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(KnowledgeCategory::class, 'knowledge_category_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(KnowledgeTag::class, 'knowledge_article_tag');
    }
}
