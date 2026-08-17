<?php

namespace App\Models;

use Database\Factories\LeadSourceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'active'])]
class LeadSource extends Model
{
    /** @use HasFactory<LeadSourceFactory> */
    use HasFactory;

    /** @return HasMany<Company, $this> */
    public function companies(): HasMany
    {
        return $this->hasMany(Company::class, 'source_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }
}
