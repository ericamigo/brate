<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\TenancyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property CarbonImmutable $move_in_date
 * @property CarbonImmutable|null $move_out_date
 */
#[Fillable(['unit_id', 'tenant_id', 'move_in_date', 'move_out_date'])]
class Tenancy extends Model
{
    /** @use HasFactory<TenancyFactory> */
    use HasFactory;

    /** @return BelongsTo<Unit, $this> */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'move_in_date' => 'immutable_date',
            'move_out_date' => 'immutable_date',
        ];
    }

    /** @param Builder<Tenancy> $query
     * @return Builder<Tenancy>
     */
    #[Scope]
    protected function overlapping(Builder $query, string $start, string $end): Builder
    {
        return $query->whereDate('move_in_date', '<=', $end)
            ->where(fn (Builder $query) => $query->whereNull('move_out_date')->orWhereDate('move_out_date', '>=', $start));
    }
}
