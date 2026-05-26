<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InboxFilterPreset extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'filters',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array<string, mixed> */
    public function toQueryParams(): array
    {
        $filters = $this->filters ?? [];
        $params = [];

        if (! empty($filters['q'])) {
            $params['q'] = $filters['q'];
        }
        if (! empty($filters['website_id'])) {
            $params['website_id'] = $filters['website_id'];
        }
        if (! empty($filters['department_id'])) {
            $params['department_id'] = $filters['department_id'];
        }
        if (! empty($filters['sort']) && $filters['sort'] !== 'newest') {
            $params['sort'] = $filters['sort'];
        }

        $view = $filters['view'] ?? 'all';
        if ($view === 'awaiting') {
            $params['awaiting'] = '1';
        } elseif ($view === 'assigned') {
            $params['assigned'] = 'me';
        } elseif ($view === 'starred') {
            $params['starred'] = '1';
        } elseif ($view === 'pinned') {
            $params['pinned'] = '1';
        }

        return $params;
    }
}
