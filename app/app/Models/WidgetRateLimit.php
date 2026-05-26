<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WidgetRateLimit extends Model
{
    protected $fillable = ['bot_token', 'identifier', 'window', 'hits', 'expires_at'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime'];
    }
}
