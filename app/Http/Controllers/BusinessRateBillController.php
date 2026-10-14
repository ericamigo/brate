<?php

namespace App\Http\Controllers;

use App\Enums\BillingStage;
use App\Http\Requests\BusinessRateBillRequest;
use App\Models\Biller;
use App\Models\BusinessRateBill;
use App\Models\Unit;
use App\Services\BillDocuments;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BusinessRateBillController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:255']]);
        $records = BusinessRateBill::query()->with(['unit.building', 'biller'])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->whereHas('unit', fn ($query) => $query->where('name', 'like', '%'.$search.'%')))
            ->orderByDesc('id')->paginate(15)->withQueryString();

        return Inertia::render('business-rate-bills/index', [
            'title' => 'Business Rate Bills', 'singular' => 'Business Rate Bill', 'resource' => 'business-rate-bills',
            'columns' => ['case_number' => 'Case Number', 'reference_number' => 'Reference', 'biller.name' => 'Biller', 'unit.building.name' => 'Building', 'unit.name' => 'Unit', 'period_start' => 'Period Start', 'period_end' => 'Period End', 'amount' => 'Amount', 'stage' => 'Stage'], 'records' => $records,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('business-rate-bills/create', $this->formData(new BusinessRateBill));
    }

    public function store(BusinessRateBillRequest $request, BillDocuments $documents): RedirectResponse
    {
        $data = $request->safe()->except('files');
        $files = $request->file('files', []);
        $documents->save(fn () => BusinessRateBill::create($data), is_array($files) ? array_values($files) : []);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Business Rate Bill created.']);

        return to_route('business-rate-bills.index');
    }

    public function show(BusinessRateBill $businessRateBill): Response
    {
        return Inertia::render('business-rate-bills/show', [
            'bill' => $businessRateBill->load(['unit.building.company', 'biller', 'documents']),
        ]);
    }

    public function storeDocuments(Request $request, BusinessRateBill $businessRateBill, BillDocuments $documents): RedirectResponse
    {
        $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:10'],
            'files.*' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp,bmp,pdf', 'max:20480'],
        ]);
        $files = $request->file('files', []);
        $documents->save(fn () => $businessRateBill, is_array($files) ? array_values($files) : []);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Bill documents uploaded.']);

        return to_route('business-rate-bills.show', $businessRateBill);
    }

    public function edit(BusinessRateBill $businessRateBill): Response
    {
        return Inertia::render('business-rate-bills/edit', $this->formData($businessRateBill));
    }

    public function update(BusinessRateBillRequest $request, BusinessRateBill $businessRateBill): RedirectResponse
    {
        $businessRateBill->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Business Rate Bill updated.']);

        return to_route('business-rate-bills.index');
    }

    public function destroy(BusinessRateBill $businessRateBill): RedirectResponse
    {
        DB::transaction(function () use ($businessRateBill) {
            $businessRateBill->documents()->update(['documentable_type' => null, 'documentable_id' => null]);
            $businessRateBill->delete();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Business Rate Bill deleted.']);

        return to_route('business-rate-bills.index');
    }

    /** @return array{title: string, singular: string, resource: string, record: BusinessRateBill, fields: list<array{name: string, label: string, type: string, required: bool, options?: array<int|string, string>}>} */
    private function formData(BusinessRateBill $record): array
    {
        $units = Unit::with('building.company')->orderBy('name')->orderBy('id')->get()->mapWithKeys(fn (Unit $unit) => [$unit->id => $unit->building->company->name.' / '.$unit->building->name.' / '.$unit->name])->all();

        return [
            'title' => 'Business Rate Bills', 'singular' => 'Business Rate Bill', 'resource' => 'business-rate-bills', 'record' => $record,
            'fields' => [
                ['name' => 'case_number', 'label' => 'Case number', 'type' => 'text', 'required' => false],
                ['name' => 'unit_id', 'label' => 'Unit', 'type' => 'select', 'required' => true, 'options' => $units],
                ['name' => 'biller_id', 'label' => 'Biller', 'type' => 'select', 'required' => true, 'options' => Biller::orderBy('name')->orderBy('id')->pluck('name', 'id')->all()],
                ['name' => 'reference_number', 'label' => 'Reference number', 'type' => 'text', 'required' => false],
                ['name' => 'period_start', 'label' => 'Period start', 'type' => 'date', 'required' => true],
                ['name' => 'period_end', 'label' => 'Period end', 'type' => 'date', 'required' => true],
                ['name' => 'amount', 'label' => 'Amount', 'type' => 'number', 'required' => true],
                ['name' => 'stage', 'label' => 'Stage', 'type' => 'select', 'required' => false, 'options' => collect(BillingStage::cases())->mapWithKeys(fn (BillingStage $stage) => [$stage->value => $stage->name])->all()],
            ],
        ];
    }
}
