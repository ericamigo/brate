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
        $records = Tenant::query()->with([])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderByDesc('id')->paginate(15)->withQueryString();

        return Inertia::render('business-rates/index', [
            'title' => 'Tenants', 'singular' => 'Tenant', 'resource' => 'tenants',
            'columns' => ['name' => 'Name'], 'records' => $records,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('business-rates/form', $this->formData(new Tenant));
    }

    public function store(TenantRequest $request): RedirectResponse
    {
        Tenant::create($request->validated());

        return to_route('tenants.index')->with('success', 'Tenant created.');
    }

    public function edit(Tenant $tenant): Response
    {
        return Inertia::render('business-rates/form', $this->formData($tenant));
    }

    public function update(TenantRequest $request, Tenant $tenant): RedirectResponse
    {
        $tenant->update($request->validated());

        return to_route('tenants.index')->with('success', 'Tenant updated.');
    }

    public function destroy(Tenant $tenant): RedirectResponse
    {
        if ($tenant->tenancies()->exists()) {
            return back()->with('error', 'This tenant has related records. Remove those records first; historical records are not deleted automatically.');
        }

        $tenant->delete();

        return to_route('tenants.index')->with('success', 'Tenant deleted.');
    }

    /** @return array{title: string, singular: string, resource: string, record: Tenant, fields: list<array{name: string, label: string, type: string, required: bool, options?: array<int, string>}>} */
    private function formData(Tenant $record): array
    {

        return [
            'title' => 'Tenants', 'singular' => 'Tenant', 'resource' => 'tenants', 'record' => $record,
            'fields' => [
                ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
            ],
        ];
    }
}
