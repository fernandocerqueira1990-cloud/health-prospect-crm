<?php

namespace App\Models;

use Database\Factories\OpportunityStageHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'pipeline_id',
    'opportunity_id',
    'from_stage_id',
    'to_stage_id',
    'changed_by_user_id',
    'changed_at',
    'notes',
])]
class OpportunityStageHistory extends Model
{
    /** @use HasFactory<OpportunityStageHistoryFactory> */
    use HasFactory;

    /** @return BelongsTo<Opportunity, $this> */
    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    /** @return BelongsTo<Pipeline, $this> */
    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    /** @return BelongsTo<Stage, $this> */
    public function fromStage(): BelongsTo
    {
        return $this->belongsTo(Stage::class, 'from_stage_id');
    }

    /** @return BelongsTo<Stage, $this> */
    public function toStage(): BelongsTo
    {
        return $this->belongsTo(Stage::class, 'to_stage_id');
    }

    /** @return BelongsTo<User, $this> */
    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'changed_at' => 'datetime',
        ];
    }
}
