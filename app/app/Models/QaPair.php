<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QaPair extends Model
{
    protected $fillable = [
        'website_id', 'question', 'question_variations', 'answer', 'alternate_answers', 'category', 'tags',
        'trigger_keywords', 'priority', 'is_active', 'is_published', 'version',
    ];

    protected function casts(): array
    {
        return [
            'trigger_keywords' => 'array',
            'alternate_answers' => 'array',
            'question_variations' => 'array',
            'tags' => 'array',
            'is_active' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }
}
