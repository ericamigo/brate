<?php

namespace App\Http\Controllers;

use App\Http\Requests\BillerRequest;
use App\Models\Biller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BillerController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:255']]);
        $records = Biller::query()->with([])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderByDesc('id')->paginate(15)->withQueryString();

        return Inertia::render('billers/index', [
            'title' => 'Billers', 'singular' => 'Biller', 'resource' => 'billers',
            'columns' => ['name' => 'Name'], 'records' => $records,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('billers/create', $this->formData(new Biller));
    }

    public function store(BillerRequest $request): RedirectResponse
    {
        Biller::create($request->validated());

        return to_route('billers.index')->with('success', 'Biller created.');
    }

    public function edit(Biller $biller): Response
    {
        return Inertia::render('billers/edit', $this->formData($biller));
    }

    public function update(BillerRequest $request, Biller $biller): RedirectResponse
    {
        $biller->update($request->validated());

        return to_route('billers.index')->with('success', 'Biller updated.');
    }

    public function destroy(Biller $biller): RedirectResponse
    {
        if ($biller->bills()->exists()) {
            return back()->with('error', 'This biller has related records. Remove those records first; historical records are not deleted automatically.');
        }

        $biller->delete();

        return to_route('billers.index')->with('success', 'Biller deleted.');
    }

    /** @return array{title: string, singular: string, resource: string, record: Biller, fields: list<array{name: string, label: string, type: string, required: bool, options?: array<int, string>}>} */
    private function formData(Biller $record): array
    {

        return [
            'title' => 'Billers', 'singular' => 'Biller', 'resource' => 'billers', 'record' => $record,
            'fields' => [
                ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
            ],
        ];
    }
}
