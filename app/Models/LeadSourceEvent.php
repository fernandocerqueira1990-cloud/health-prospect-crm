<?php

namespace App\Models;

use Database\Factories\LeadSourceEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'lead_id',
    'event_type',
    'source',
    'medium',
    'campaign',
    'channel',
    'referrer',
    'landing_page',
    'utm_source',
    'utm_medium',
    'utm_campaign',
    'utm_content',
    'utm_term',
    'social_network',
    'external_id',
    'occurred_at',
    'metadata',
])]
class LeadSourceEvent extends Model
{
    /** @use HasFactory<LeadSourceEventFactory> */
    use HasFactory;

    /** @return BelongsTo<Lead, $this> */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
