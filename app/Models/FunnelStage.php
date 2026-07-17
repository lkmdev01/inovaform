<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class FunnelStage extends Model
{
    /** @use HasFactory<\Database\Factories\FunnelStageFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'funnel_id',
        'name',
        'stage_order',
        'conversion_rate',
        'expected_volume',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'conversion_rate' => 'decimal:2',
            'meta' => 'array',
        ];
    }

    public function funnel(): BelongsTo
    {
        return $this->belongsTo(Funnel::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(FunnelSubmissionAnswer::class);
    }
}
