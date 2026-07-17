<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FunnelTemplate extends Model
{
    /** @use HasFactory<\Database\Factories\FunnelTemplateFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'source_funnel_id',
        'name',
        'slug',
        'description',
        'category',
        'thumbnail_path',
        'is_system',
        'is_premium',
        'is_active',
        'sort_order',
        'version',
        'schema',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_premium' => 'boolean',
            'is_active' => 'boolean',
            'version' => 'integer',
            'schema' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sourceFunnel(): BelongsTo
    {
        return $this->belongsTo(Funnel::class, 'source_funnel_id');
    }
}
