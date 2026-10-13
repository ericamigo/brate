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

        return Inertia::render('business-rates/index', [
            'title' => 'Companies', 'singular' => 'Company', 'resource' => 'companies',
            'columns' => ['name' => 'Name'], 'records' => $records,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('business-rates/form', $this->formData(new Company));
    }

    public function store(CompanyRequest $request): RedirectResponse
    {
        Company::create($request->validated());

        return to_route('companies.index')->with('success', 'Company created.');
    }

    public function edit(Company $company): Response
    {
        return Inertia::render('business-rates/form', $this->formData($company));
    }

    public function update(CompanyRequest $request, Company $company): RedirectResponse
    {
        $company->update($request->validated());

        return to_route('companies.index')->with('success', 'Company updated.');
    }

    public function destroy(Company $company): RedirectResponse
    {
        if ($company->buildings()->exists()) {
            return back()->with('error', 'This company has related records. Remove those records first; historical records are not deleted automatically.');
        }

        $company->delete();

        return to_route('companies.index')->with('success', 'Company deleted.');
    }

    /** @return array{title: string, singular: string, resource: string, record: Company, fields: list<array{name: string, label: string, type: string, required: bool, options?: array<int, string>}>} */
    private function formData(Company $record): array
    {

        return [
            'title' => 'Companies', 'singular' => 'Company', 'resource' => 'companies', 'record' => $record,
            'fields' => [
                ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
            ],
        ];
    }
}
