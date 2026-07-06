<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScanSnapshot extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'metrics' => 'array',
        'scanned_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
