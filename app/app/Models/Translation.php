<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Translation extends Model
{
    protected $fillable = ['website_id', 'locale', 'key', 'value'];

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }
}
