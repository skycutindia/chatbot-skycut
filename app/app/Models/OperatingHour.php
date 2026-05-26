<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperatingHour extends Model
{
    protected $fillable = [
        'website_id', 'day_of_week', 'opens_at', 'closes_at', 'is_closed', 'timezone',
    ];

    protected function casts(): array
    {
        return ['is_closed' => 'boolean'];
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    public static function isWithinHours(Website $website): bool
    {
        $hours = $website->operatingHours;
        if ($hours->isEmpty()) {
            return true;
        }

        $tz = $hours->first()->timezone ?? config('app.timezone');
        $now = Carbon::now($tz);
        $today = $hours->firstWhere('day_of_week', $now->dayOfWeek);

        if (! $today || $today->is_closed) {
            return false;
        }

        if (! $today->opens_at || ! $today->closes_at) {
            return true;
        }

        $open = Carbon::parse($today->opens_at, $tz)->setDateFrom($now);
        $close = Carbon::parse($today->closes_at, $tz)->setDateFrom($now);

        return $now->between($open, $close);
    }
}
