<?php

namespace App\Http\Controllers;

use App\Http\Requests\UnitRequest;
use App\Models\Building;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UnitController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:255']]);
        $records = Unit::query()->with(['building.company'])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderByDesc('id')->paginate(15)->withQueryString();

        return Inertia::render('units/index', [
            'title' => 'Units', 'singular' => 'Unit', 'resource' => 'units',
            'columns' => ['name' => 'Name', 'building.name' => 'Building', 'building.company.name' => 'Company'], 'records' => $records,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('units/create', $this->formData(new Unit));
    }

    public function store(UnitRequest $request): RedirectResponse
    {
        Unit::create($request->validated());

        return to_route('units.index')->with('success', 'Unit created.');
    }

    public function edit(Unit $unit): Response
    {
        return Inertia::render('units/edit', $this->formData($unit));
    }

    public function update(UnitRequest $request, Unit $unit): RedirectResponse
    {
        $unit->update($request->validated());

        return to_route('units.index')->with('success', 'Unit updated.');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        if ($unit->tenancies()->exists() || $unit->businessRateBills()->exists()) {
            return back()->with('error', 'This unit has related records. Remove those records first; historical records are not deleted automatically.');
        }

        $unit->delete();

        return to_route('units.index')->with('success', 'Unit deleted.');
    }

    /** @return array{title: string, singular: string, resource: string, record: Unit, fields: list<array{name: string, label: string, type: string, required: bool, options?: array<int, string>}>} */
    private function formData(Unit $record): array
    {
        $buildings = Building::with('company')->orderBy('name')->orderBy('id')->get()->mapWithKeys(fn (Building $building) => [$building->id => $building->company->name.' / '.$building->name])->all();

        return [
            'title' => 'Units', 'singular' => 'Unit', 'resource' => 'units', 'record' => $record,
            'fields' => [
                ['name' => 'building_id', 'label' => 'Building', 'type' => 'select', 'required' => true, 'options' => $buildings],
                ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
            ],
        ];
    }
}
