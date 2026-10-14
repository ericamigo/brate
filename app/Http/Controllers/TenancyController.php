<?php

namespace App\Http\Controllers;

use App\Http\Requests\TenancyRequest;
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
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:255']]);
        $records = Tenancy::query()->with(['unit.building', 'tenant'])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->whereHas('unit', fn ($query) => $query->where('name', 'like', '%'.$search.'%')))
            ->orderByDesc('id')->paginate(15)->withQueryString();

        return Inertia::render('tenancies/index', [
            'title' => 'Tenancies', 'singular' => 'Tenancy', 'resource' => 'tenancies',
            'columns' => ['tenant.name' => 'Tenant', 'unit.building.name' => 'Building', 'unit.name' => 'Unit', 'move_in_date' => 'Move-in date', 'move_out_date' => 'Move-out date'], 'records' => $records,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('tenancies/create', $this->formData(new Tenancy));
    }

    public function store(TenancyRequest $request): RedirectResponse
    {
        Tenancy::create($request->validated());

        return to_route('tenancies.index')->with('success', 'Tenancy created.');
    }

    public function edit(Tenancy $tenancy): Response
    {
        return Inertia::render('tenancies/edit', $this->formData($tenancy));
    }

    public function update(TenancyRequest $request, Tenancy $tenancy): RedirectResponse
    {
        $tenancy->update($request->validated());

        return to_route('tenancies.index')->with('success', 'Tenancy updated.');
    }

    public function destroy(Tenancy $tenancy): RedirectResponse
    {
        $tenancy->delete();

        return to_route('tenancies.index')->with('success', 'Tenancy deleted.');
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
