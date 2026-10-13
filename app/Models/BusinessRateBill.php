<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\BusinessRateBillFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * @property CarbonImmutable $period_start
 * @property CarbonImmutable $period_end
 */
#[Fillable(['unit_id', 'reference_number', 'period_start', 'period_end', 'amount'])]
class BusinessRateBill extends Model
{
    /** @use HasFactory<BusinessRateBillFactory> */
    use HasFactory;

    /** @return BelongsTo<Unit, $this> */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'period_start' => 'immutable_date',
            'period_end' => 'immutable_date',
            'amount' => 'decimal:2',
        ];
    }

    /**
     * @return Collection<int, array{bill: $this, tenancy: null, occupied_start: null, occupied_end: null}>|Collection<int, array{bill: $this, tenancy: Tenancy, occupied_start: CarbonImmutable, occupied_end: CarbonImmutable}>
     */
    public function occupancyRows(): Collection
    {
        $tenancies = $this->unit->tenancies->filter(fn (Tenancy $tenancy) => $tenancy->move_in_date->lte($this->period_end)
            && ($tenancy->move_out_date === null || $tenancy->move_out_date->gte($this->period_start))
        );

        if ($tenancies->isEmpty()) {
            return collect([['bill' => $this, 'tenancy' => null, 'occupied_start' => null, 'occupied_end' => null]]);
        }

        return $tenancies->map(fn (Tenancy $tenancy) => [
            'bill' => $this,
            'tenancy' => $tenancy,
            'occupied_start' => $tenancy->move_in_date->max($this->period_start),
            'occupied_end' => ($tenancy->move_out_date ?? $this->period_end)->min($this->period_end),
        ])->values();
    }
}
