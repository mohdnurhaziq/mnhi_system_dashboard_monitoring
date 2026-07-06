<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedPrompt extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'context_snapshot' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function finding(): BelongsTo
    {
        return $this->belongsTo(Finding::class);
    }
}
