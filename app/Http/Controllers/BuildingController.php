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
            'companies' => Company::orderBy('name')->get(['id', 'name']),
            'title' => 'Buildings', 'singular' => 'Building', 'resource' => 'buildings',
            'columns' => ['name' => 'Name', 'company.name' => 'Company', 'address' => 'Address'], 'records' => $records,
        ]);
    }

    public function store(BuildingRequest $request): RedirectResponse
    {
        Building::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Building created.']);

        return to_route('buildings.index');
    }

    public function update(BuildingRequest $request, Building $building): RedirectResponse
    {
        $building->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Building updated.']);

        return to_route('buildings.index');
    }

    public function destroy(Building $building): RedirectResponse
    {
        if ($building->units()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'This building has related records. Remove those records first; historical records are not deleted automatically.']);

            return back();
        }

        $building->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Building deleted.']);

        return to_route('buildings.index');
    }
}
