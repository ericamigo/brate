<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompanyRequest;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:255']]);
        $records = Company::query()->with([])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderByDesc('id')->paginate(15)->withQueryString();

        return Inertia::render('companies/index', [
            'title' => 'Companies', 'singular' => 'Company', 'resource' => 'companies',
            'columns' => ['name' => 'Name'], 'records' => $records,
        ]);
    }

    public function store(CompanyRequest $request): RedirectResponse
    {
        Company::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Company created.']);

        return to_route('companies.index');
    }

    public function update(CompanyRequest $request, Company $company): RedirectResponse
    {
        $company->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Company updated.']);

        return to_route('companies.index');
    }

    public function destroy(Company $company): RedirectResponse
    {
        if ($company->buildings()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'This company has related records. Remove those records first; historical records are not deleted automatically.']);

            return back();
        }

        $company->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Company deleted.']);

        return to_route('companies.index');
    }
}
