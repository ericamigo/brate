<?php

namespace App\Http\Controllers;

use App\Http\Requests\UnitRequest;
use App\Models\Building;
use App\Models\Company;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UnitController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'building_id' => ['nullable', 'integer', 'exists:buildings,id'],
        ]);
        $records = Unit::query()->with(['building.company'])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('name', 'like', '%'.$search.'%'))
            ->when($filters['company_id'] ?? null, fn ($query, $id) => $query->whereHas('building', fn ($query) => $query->where('company_id', $id)))
            ->when($filters['building_id'] ?? null, fn ($query, $id) => $query->where('building_id', $id))
            ->orderBy('name')->orderBy('id')->paginate(15)->withQueryString();

        return Inertia::render('units/index', [
            'title' => 'Units', 'singular' => 'Unit', 'resource' => 'units',
            'columns' => ['name' => 'Name', 'building.name' => 'Building', 'building.company.name' => 'Company'], 'records' => $records,
            'filters' => $filters,
            'companies' => Company::orderBy('name')->get(['id', 'name']),
            'buildings' => Building::with('company:id,name')->orderBy('name')->get(['id', 'name', 'company_id']),
        ]);
    }

    public function store(UnitRequest $request): RedirectResponse
    {
        Unit::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Unit created.']);

        return to_route('units.index');
    }

    public function update(UnitRequest $request, Unit $unit): RedirectResponse
    {
        $unit->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Unit updated.']);

        return to_route('units.index');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        if ($unit->tenancies()->exists() || $unit->businessRateBills()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'This unit has related records. Remove those records first; historical records are not deleted automatically.']);

            return back();
        }

        $unit->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Unit deleted.']);

        return to_route('units.index');
    }
}
