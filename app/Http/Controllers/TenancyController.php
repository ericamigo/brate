<?php

namespace App\Http\Controllers;

use App\Http\Requests\TenancyRequest;
use App\Models\Building;
use App\Models\Company;
use App\Models\Tenancy;
use App\Models\Tenant;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TenancyController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'building_id' => ['nullable', 'integer', 'exists:buildings,id'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
        ]);
        $records = Tenancy::query()->with(['unit.building', 'tenant'])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->whereLike(['tenant.name', 'unit.name'], $search))
            ->when($filters['company_id'] ?? null, fn ($query, $id) => $query->whereHas('unit.building', fn ($query) => $query->where('company_id', $id)))
            ->when($filters['building_id'] ?? null, fn ($query, $id) => $query->whereHas('unit', fn ($query) => $query->where('building_id', $id)))
            ->when($filters['unit_id'] ?? null, fn ($query, $id) => $query->where('unit_id', $id))
            ->orderByDesc('id')->paginate(15)->withQueryString();

        return Inertia::render('tenancies/index', [
            'title' => 'Tenancies', 'singular' => 'Tenancy', 'resource' => 'tenancies',
            'filters' => $filters,
            'companies' => Company::orderBy('name')->get(['id', 'name']),
            'buildings' => Building::with('company:id,name')->orderBy('name')->get(['id', 'name', 'company_id']),
            'units' => Unit::with('building.company')->orderBy('name')->get(['id', 'name', 'building_id']),
            'columns' => ['tenant.name' => 'Tenant', 'unit.building.name' => 'Building', 'unit.name' => 'Unit', 'move_in_date' => 'Move-In Date', 'move_out_date' => 'Move-Out Date'], 'records' => $records,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('tenancies/create', $this->formData(new Tenancy));
    }

    public function store(TenancyRequest $request): RedirectResponse
    {
        Tenancy::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tenancy created.']);

        return to_route('tenancies.index');
    }

    public function edit(Tenancy $tenancy): Response
    {
        return Inertia::render('tenancies/edit', $this->formData($tenancy));
    }

    public function update(TenancyRequest $request, Tenancy $tenancy): RedirectResponse
    {
        $tenancy->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tenancy updated.']);

        return to_route('tenancies.index');
    }

    public function destroy(Tenancy $tenancy): RedirectResponse
    {
        $tenancy->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tenancy deleted.']);

        return to_route('tenancies.index');
    }

    /** @return array{title: string, singular: string, resource: string, record: Tenancy, fields: list<array{name: string, label: string, type: string, required: bool, options?: array<int, string>}>} */
    private function formData(Tenancy $record): array
    {
        $units = Unit::with('building.company')->orderBy('name')->orderBy('id')->get()->mapWithKeys(fn (Unit $unit) => [$unit->id => $unit->building->company->name.' / '.$unit->building->name.' / '.$unit->name])->all();
        $tenants = Tenant::orderBy('name')->orderBy('id')->pluck('name', 'id')->all();

        return [
            'title' => 'Tenancies', 'singular' => 'Tenancy', 'resource' => 'tenancies', 'record' => $record,
            'fields' => [
                ['name' => 'unit_id', 'label' => 'Unit', 'type' => 'select', 'required' => true, 'options' => $units],
                ['name' => 'tenant_id', 'label' => 'Tenant', 'type' => 'select', 'required' => true, 'options' => $tenants],
                ['name' => 'move_in_date', 'label' => 'Move-in date', 'type' => 'date', 'required' => true],
                ['name' => 'move_out_date', 'label' => 'Move-out date', 'type' => 'date', 'required' => false],
            ],
        ];
    }
}
