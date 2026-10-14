<?php

namespace App\Http\Controllers;

use App\Http\Requests\BuildingRequest;
use App\Models\Building;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BuildingController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:255']]);
        $records = Building::query()->with(['company'])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderByDesc('id')->paginate(15)->withQueryString();

        return Inertia::render('buildings/index', [
            'title' => 'Buildings', 'singular' => 'Building', 'resource' => 'buildings',
            'columns' => ['name' => 'Name', 'company.name' => 'Company', 'address' => 'Address'], 'records' => $records,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('buildings/create', $this->formData(new Building));
    }

    public function store(BuildingRequest $request): RedirectResponse
    {
        Building::create($request->validated());

        return to_route('buildings.index')->with('success', 'Building created.');
    }

    public function edit(Building $building): Response
    {
        return Inertia::render('buildings/edit', $this->formData($building));
    }

    public function update(BuildingRequest $request, Building $building): RedirectResponse
    {
        $building->update($request->validated());

        return to_route('buildings.index')->with('success', 'Building updated.');
    }

    public function destroy(Building $building): RedirectResponse
    {
        if ($building->units()->exists()) {
            return back()->with('error', 'This building has related records. Remove those records first; historical records are not deleted automatically.');
        }

        $building->delete();

        return to_route('buildings.index')->with('success', 'Building deleted.');
    }

    /** @return array{title: string, singular: string, resource: string, record: Building, fields: list<array{name: string, label: string, type: string, required: bool, options?: array<int, string>}>} */
    private function formData(Building $record): array
    {
        $companies = Company::orderBy('name')->orderBy('id')->pluck('name', 'id')->all();

        return [
            'title' => 'Buildings', 'singular' => 'Building', 'resource' => 'buildings', 'record' => $record,
            'fields' => [
                ['name' => 'company_id', 'label' => 'Company', 'type' => 'select', 'required' => true, 'options' => $companies],
                ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
                ['name' => 'address', 'label' => 'Address', 'type' => 'text', 'required' => false],
            ],
        ];
    }
}
