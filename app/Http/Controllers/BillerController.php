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

    public function store(BillerRequest $request): RedirectResponse
    {
        Biller::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Biller created.']);

        return to_route('billers.index');
    }

    public function update(BillerRequest $request, Biller $biller): RedirectResponse
    {
        $biller->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Biller updated.']);

        return to_route('billers.index');
    }

    public function destroy(Biller $biller): RedirectResponse
    {
        if ($biller->bills()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'This biller has related records. Remove those records first; historical records are not deleted automatically.']);

            return back();
        }

        $biller->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Biller deleted.']);

        return to_route('billers.index');
    }
}
