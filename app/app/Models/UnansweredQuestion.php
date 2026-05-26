<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnansweredQuestion extends Model
{
    protected $fillable = [
        'website_id',
        'conversation_id',
        'visitor_message',
        'detected_intent',
        'confidence',
        'source',
        'status',
        'resolved_qa_pair_id',
        'admin_answer',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'confidence' => 'float',
            'resolved_at' => 'datetime',
        ];
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function qaPair(): BelongsTo
    {
        return $this->belongsTo(QaPair::class, 'resolved_qa_pair_id');
    }
}
