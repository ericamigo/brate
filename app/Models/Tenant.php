<?php

namespace App\Models;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name'])]
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    /** @return Attribute<string, string> */
    protected function name(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => trim($value),
        );
    }

    /** @return HasMany<Tenancy, $this> */
    public function tenancies(): HasMany
    {
        return $this->hasMany(Tenancy::class);
    }

    /** @return HasMany<Tenancy, $this> */
    public function currentTenancies(): HasMany
    {
        return $this->tenancies()->overlapping(today()->toDateString(), today()->toDateString())
            ->orderByDesc('move_in_date')->orderByDesc('id');
    }
}
