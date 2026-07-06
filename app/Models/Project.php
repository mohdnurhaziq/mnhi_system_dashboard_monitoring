<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'metrics' => 'array',
        'has_commits' => 'boolean',
        'last_scanned_at' => 'datetime',
        'last_commit_at' => 'datetime',
        'ai_summary_at' => 'datetime',
    ];

    public function scanSnapshots(): HasMany
    {
        return $this->hasMany(ScanSnapshot::class);
    }

    public function findings(): HasMany
    {
        return $this->hasMany(Finding::class);
    }

    public function generatedPrompts(): HasMany
    {
        return $this->hasMany(GeneratedPrompt::class);
    }

    public function scopeIncluded(Builder $query): Builder
    {
        return $query->where('status', 'included');
    }

    public function scopeStale(Builder $query, int $days): Builder
    {
        return $query->whereNotNull('last_commit_at')
            ->where('last_commit_at', '<', now()->subDays($days));
    }
}
