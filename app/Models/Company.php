<?php

namespace App\Models;

use App\Support\EmailNormalizer;
use App\Support\TaxIdNormalizer;
use App\Support\WebsiteNormalizer;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'legal_name', 'trade_name', 'tax_id', 'tax_id_country', 'segment', 'category', 'website', 'phone', 'email',
    'street', 'number', 'complement', 'district', 'city', 'state', 'postal_code',
    'employee_count_estimate', 'assigned_user_id', 'source_id', 'priority', 'notes',
])]
class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory, SoftDeletes;

    public const PRIORITIES = ['low', 'medium', 'high', 'critical'];

    /** @return BelongsTo<User, $this> */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /** @return HasMany<Contact, $this> */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    /** @return HasMany<Opportunity, $this> */
    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }

    /** @return BelongsTo<LeadSource, $this> */
    public function source(): BelongsTo
    {
        return $this->belongsTo(LeadSource::class, 'source_id');
    }

    public function formattedTaxId(): ?string
    {
        return TaxIdNormalizer::format($this->tax_id, $this->tax_id_country);
    }

    /** @return Attribute<string|null, string|null> */
    protected function email(): Attribute
    {
        return Attribute::make(set: fn (?string $email): ?string => $email === null ? null : EmailNormalizer::normalize($email));
    }

    /** @return Attribute<string|null, string|null> */
    protected function website(): Attribute
    {
        return Attribute::make(set: fn (?string $website): ?string => WebsiteNormalizer::normalize($website));
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'employee_count_estimate' => 'integer',
            'deleted_at' => 'datetime',
        ];
    }
}
