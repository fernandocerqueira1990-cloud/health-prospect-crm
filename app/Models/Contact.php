<?php

namespace App\Models;

use App\Support\EmailNormalizer;
use App\Support\PhoneNormalizer;
use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'company_id', 'name', 'job_title', 'department', 'email', 'phone', 'whatsapp', 'linkedin_url',
    'decision_role', 'influence_level', 'is_primary', 'active', 'notes',
])]
class Contact extends Model
{
    /** @use HasFactory<ContactFactory> */
    use HasFactory, SoftDeletes;

    public const DECISION_ROLES = ['decision_maker', 'influencer', 'champion', 'user', 'technical', 'procurement', 'financial', 'gatekeeper', 'blocker', 'other'];

    public const INFLUENCE_LEVELS = ['low', 'medium', 'high', 'critical'];

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class)->withTrashed();
    }

    /** @return Attribute<string|null, string|null> */
    protected function email(): Attribute
    {
        return Attribute::make(set: fn (?string $value): ?string => $value === null ? null : EmailNormalizer::normalize($value));
    }

    /** @return Attribute<string|null, string|null> */
    protected function phone(): Attribute
    {
        return Attribute::make(set: fn (?string $value): ?string => $value === null ? null : PhoneNormalizer::normalize($value));
    }

    /** @return Attribute<string|null, string|null> */
    protected function whatsapp(): Attribute
    {
        return Attribute::make(set: fn (?string $value): ?string => $value === null ? null : PhoneNormalizer::normalize($value));
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['is_primary' => 'boolean', 'active' => 'boolean', 'deleted_at' => 'datetime'];
    }
}
