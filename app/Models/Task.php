<?php

namespace App\Models;

use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'title',
    'description',
    'status',
    'priority',
    'is_follow_up',
    'follow_up_channel',
    'company_id',
    'contact_id',
    'lead_id',
    'opportunity_id',
    'completed_activity_id',
    'assigned_user_id',
    'created_by_user_id',
    'due_at',
    'started_at',
    'completed_at',
    'cancelled_at',
])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory, SoftDeletes;

    public const STATUSES = [
        'pending',
        'in_progress',
        'completed',
        'cancelled',
    ];

    public const PRIORITIES = [
        'low',
        'medium',
        'high',
        'urgent',
    ];

    public const FOLLOW_UP_CHANNELS = [
        'call',
        'email',
        'whatsapp',
        'meeting',
    ];

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class)->withTrashed();
    }

    /** @return BelongsTo<Contact, $this> */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class)->withTrashed();
    }

    /** @return BelongsTo<Lead, $this> */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class)->withTrashed();
    }

    /** @return BelongsTo<Opportunity, $this> */
    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class)->withTrashed();
    }

    /** @return BelongsTo<Activity, $this> */
    public function completedActivity(): BelongsTo
    {
        return $this->belongsTo(
            Activity::class,
            'completed_activity_id',
        )->withTrashed();
    }

    /** @return BelongsTo<User, $this> */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_follow_up' => 'boolean',
            'due_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }
}
