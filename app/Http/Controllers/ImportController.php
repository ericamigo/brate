<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\Imports\ReLeasedImport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ImportController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('imports/index', [
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request, ReLeasedImport $importer): RedirectResponse
    {
        $data = $request->validate([
            'source' => ['required', 'in:re-leased'],
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'file' => ['required', 'file', 'extensions:xlsx', 'mimes:xlsx', 'max:20480'],
        ]);
        $result = $importer->import($request->file('file'), (int) $data['company_id']);

        return to_route('imports.index')->with('success', "Import complete: {$result['created']} tenancies created, {$result['skipped']} existing tenancies skipped, {$result['missingStart']} skipped without a start date.");
    }
}
