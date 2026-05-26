<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeSource extends Model
{
    protected $fillable = [
        'website_id', 'type', 'label', 'source_url', 'file_path', 'file_name',
        'status', 'items_indexed', 'metadata', 'error_message',
        'started_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    public function markProcessing(): void
    {
        $this->update(['status' => 'processing', 'started_at' => now(), 'error_message' => null]);
    }

    public function markCompleted(int $items): void
    {
        $this->update([
            'status' => 'completed',
            'items_indexed' => $items,
            'completed_at' => now(),
        ]);
    }

    public function markFailed(string $message): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $message,
            'completed_at' => now(),
        ]);
    }
}
