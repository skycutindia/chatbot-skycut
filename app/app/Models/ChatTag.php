<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatTag extends Model
{
    protected $fillable = ['organization_id', 'name', 'color'];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
