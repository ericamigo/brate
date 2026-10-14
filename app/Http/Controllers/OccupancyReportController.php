<?php

namespace App\Http\Controllers;

use App\Http\Requests\OccupancyReportRequest;
use App\Models\Building;
use App\Models\BusinessRateBill;
use App\Models\Company;
use App\Models\Tenancy;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Inertia\Inertia;
use Inertia\Response;

class OccupancyReportController extends Controller
{
    public function __invoke(OccupancyReportRequest $request): Response
    {
        $filters = $request->validated();
        $bills = BusinessRateBill::query()
            ->when($filters['company_id'] ?? null, fn (Builder $query, $id) => $query->whereHas('unit.building', fn (Builder $query) => $query->where('company_id', $id)))
            ->when($filters['building_id'] ?? null, fn (Builder $query, $id) => $query->whereHas('unit', fn (Builder $query) => $query->where('building_id', $id)))
            ->when($filters['unit_id'] ?? null, fn (Builder $query, $id) => $query->where('unit_id', $id))
            ->when($filters['period_start'] ?? null, fn (Builder $query, $date) => $query->whereDate('period_end', '>=', $date))
            ->when($filters['period_end'] ?? null, fn (Builder $query, $date) => $query->whereDate('period_start', '<=', $date))
            ->orderByDesc('period_start')->orderByDesc('id')
            ->paginate(15)->withQueryString();

        if ($bills->isNotEmpty()) {
            $start = $bills->getCollection()->min('period_start')->toDateString();
            $end = $bills->getCollection()->max('period_end')->toDateString();
            $bills->getCollection()->load(['biller', 'unit.building.company', 'unit.tenancies' => fn ($query) => $this->occupants($query, $start, $end)]);
        }

        return Inertia::render('business-rates/report', [
            'bills' => $bills,
            'rows' => $bills->getCollection()->flatMap(fn (BusinessRateBill $bill) => $bill->occupancyRows()->map(fn (array $row) => [
                'bill_id' => $bill->id,
                'reference_number' => $bill->reference_number,
                'biller' => $bill->biller->name,
                'building' => $bill->unit->building->name,
                'company' => $bill->unit->building->company->name,
                'unit' => $bill->unit->name,
                'period_start' => $bill->period_start->toDateString(),
                'period_end' => $bill->period_end->toDateString(),
                'occupant' => $row['tenancy']?->tenant->name,
                'tenancy_id' => $row['tenancy']?->id,
                'move_in_date' => $row['tenancy']?->move_in_date->toDateString(),
                'move_out_date' => $row['tenancy']?->move_out_date?->toDateString(),
                'occupied_start' => $row['occupied_start']?->toDateString(),
                'occupied_end' => $row['occupied_end']?->toDateString(),
            ]))->values(),
            'filters' => $filters,
            'companies' => Company::orderBy('name')->orderBy('id')->get(),
            'buildings' => Building::with('company')
                ->when($filters['company_id'] ?? null, fn (Builder $query, $id) => $query->where('company_id', $id))
                ->orderBy('name')->orderBy('id')->get(),
            'units' => Unit::with('building.company')
                ->when($filters['company_id'] ?? null, fn (Builder $query, $id) => $query->whereHas('building', fn (Builder $query) => $query->where('company_id', $id)))
                ->when($filters['building_id'] ?? null, fn (Builder $query, $id) => $query->where('building_id', $id))
                ->orderBy('name')->orderBy('id')->get(),
        ]);
    }

    /** @param HasMany<Tenancy, Unit> $query
     * @return HasMany<Tenancy, Unit>
     */
    private function occupants(HasMany $query, string $start, string $end): HasMany
    {
        $query->getQuery()->overlapping($start, $end);

        return $query->with('tenant')->orderBy('move_in_date')->orderBy('id');
    }
}
