<?php

namespace App\Models;

use App\Support\EmailNormalizer;
use App\Support\PhoneNormalizer;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name',
    'company_name',
    'job_title',
    'email',
    'phone',
    'whatsapp',
    'company_id',
    'contact_id',
    'assigned_user_id',
    'source_id',
    'channel_id',
    'first_touch_source_event_id',
    'last_touch_source_event_id',
    'status',
    'priority',
    'temperature',
    'score',
    'qualified_at',
    'converted_at',
    'lost_at',
    'last_interaction_at',
    'next_action_at',
    'notes',
])]
class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory, SoftDeletes;

    public const STATUSES = [
        'new',
        'contacted',
        'qualified',
        'nurturing',
        'converted',
        'disqualified',
    ];

    public const PRIORITIES = [
        'low',
        'medium',
        'high',
        'critical',
    ];

    public const TEMPERATURES = [
        'cold',
        'warm',
        'hot',
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

    /** @return BelongsTo<User, $this> */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /** @return BelongsTo<LeadSource, $this> */
    public function source(): BelongsTo
    {
        return $this->belongsTo(LeadSource::class, 'source_id');
    }

    /** @return BelongsTo<Channel, $this> */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class, 'channel_id');
    }

    /** @return HasMany<Opportunity, $this> */
    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }

    /** @return HasMany<LeadSourceEvent, $this> */
    public function sourceEvents(): HasMany
    {
        return $this->hasMany(LeadSourceEvent::class);
    }

    /** @return BelongsTo<LeadSourceEvent, $this> */
    public function firstTouchSourceEvent(): BelongsTo
    {
        return $this->belongsTo(
            LeadSourceEvent::class,
            'first_touch_source_event_id',
        );
    }

    /** @return BelongsTo<LeadSourceEvent, $this> */
    public function lastTouchSourceEvent(): BelongsTo
    {
        return $this->belongsTo(
            LeadSourceEvent::class,
            'last_touch_source_event_id',
        );
    }

    /** @return Attribute<string|null, string|null> */
    protected function email(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value === null
                ? null
                : EmailNormalizer::normalize($value),
        );
    }

    /** @return Attribute<string|null, string|null> */
    protected function phone(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value === null
                ? null
                : PhoneNormalizer::normalize($value),
        );
    }

    /** @return Attribute<string|null, string|null> */
    protected function whatsapp(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value === null
                ? null
                : PhoneNormalizer::normalize($value),
        );
    }

    /** @return HasMany<Task, $this> */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /** @return HasMany<Activity, $this> */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'qualified_at' => 'datetime',
            'converted_at' => 'datetime',
            'lost_at' => 'datetime',
            'last_interaction_at' => 'datetime',
            'next_action_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }
}
