<?php

namespace App\Http\Controllers;

use App\Http\Requests\BusinessRateBillRequest;
use App\Models\BusinessRateBill;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BusinessRateBillController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:255']]);
        $records = BusinessRateBill::query()->with(['unit.building'])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->whereHas('unit', fn ($query) => $query->where('name', 'like', '%'.$search.'%')))
            ->orderByDesc('id')->paginate(15)->withQueryString();

        return Inertia::render('business-rates/index', [
            'title' => 'Business Rate Bills', 'singular' => 'Business Rate Bill', 'resource' => 'business-rate-bills',
            'columns' => ['reference_number' => 'Reference', 'unit.building.name' => 'Building', 'unit.name' => 'Unit', 'period_start' => 'Period start', 'period_end' => 'Period end', 'amount' => 'Amount'], 'records' => $records,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('business-rates/form', $this->formData(new BusinessRateBill));
    }

    public function store(BusinessRateBillRequest $request): RedirectResponse
    {
        BusinessRateBill::create($request->validated());

        return to_route('business-rate-bills.index')->with('success', 'Business Rate Bill created.');
    }

    public function edit(BusinessRateBill $businessRateBill): Response
    {
        return Inertia::render('business-rates/form', $this->formData($businessRateBill));
    }

    public function update(BusinessRateBillRequest $request, BusinessRateBill $businessRateBill): RedirectResponse
    {
        $businessRateBill->update($request->validated());

        return to_route('business-rate-bills.index')->with('success', 'Business Rate Bill updated.');
    }

    public function destroy(BusinessRateBill $businessRateBill): RedirectResponse
    {
        $businessRateBill->delete();

        return to_route('business-rate-bills.index')->with('success', 'Business Rate Bill deleted.');
    }

    /** @return array{title: string, singular: string, resource: string, record: BusinessRateBill, fields: list<array{name: string, label: string, type: string, required: bool, options?: array<int, string>}>} */
    private function formData(BusinessRateBill $record): array
    {
        $units = Unit::with('building.company')->orderBy('name')->orderBy('id')->get()->mapWithKeys(fn (Unit $unit) => [$unit->id => $unit->building->company->name.' / '.$unit->building->name.' / '.$unit->name])->all();

        return [
            'title' => 'Business Rate Bills', 'singular' => 'Business Rate Bill', 'resource' => 'business-rate-bills', 'record' => $record,
            'fields' => [
                ['name' => 'unit_id', 'label' => 'Unit', 'type' => 'select', 'required' => true, 'options' => $units],
                ['name' => 'reference_number', 'label' => 'Reference number', 'type' => 'text', 'required' => false],
                ['name' => 'period_start', 'label' => 'Period start', 'type' => 'date', 'required' => true],
                ['name' => 'period_end', 'label' => 'Period end', 'type' => 'date', 'required' => true],
                ['name' => 'amount', 'label' => 'Amount', 'type' => 'number', 'required' => true],
            ],
        ];
    }
}
