<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class FunnelSubmission extends Model
{
    /** @use HasFactory<\Database\Factories\FunnelSubmissionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'funnel_id',
        'status',
        'lead_name',
        'lead_email',
        'lead_phone',
        'session_id',
        'ip_address',
        'user_agent',
        'meta',
        'submitted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'submitted_at' => 'datetime',
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
