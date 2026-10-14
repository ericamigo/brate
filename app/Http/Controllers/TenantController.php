<?php

namespace App\Http\Controllers;

use App\Http\Requests\TenantRequest;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TenantController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:255']]);
        $records = Tenant::query()->with(['currentTenancies.unit.building.company'])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')->orderBy('id')->paginate(15)->withQueryString();

        return Inertia::render('tenants/index', [
            'title' => 'Tenants', 'singular' => 'Tenant', 'resource' => 'tenants',
            'columns' => ['name' => 'Name'], 'records' => $records,
        ]);
    }

    public function show(Tenant $tenant): Response
    {
        return Inertia::render('tenants/show', [
            'tenant' => $tenant,
            'tenancies' => $tenant->tenancies()->with('unit.building.company')
                ->orderByDesc('move_in_date')->orderByDesc('id')->paginate(15),
        ]);
    }

    public function store(TenantRequest $request): RedirectResponse
    {
        Tenant::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tenant created.']);

        return to_route('tenants.index');
    }

    public function update(TenantRequest $request, Tenant $tenant): RedirectResponse
    {
        $tenant->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tenant updated.']);

        return to_route('tenants.index');
    }

    public function destroy(Tenant $tenant): RedirectResponse
    {
        if ($tenant->tenancies()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'This tenant has related records. Remove those records first; historical records are not deleted automatically.']);

            return back();
        }

        $tenant->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tenant deleted.']);

        return to_route('tenants.index');
    }
}
