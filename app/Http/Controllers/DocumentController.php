<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class DocumentController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('documents/index', [
            'documents' => Document::query()->orderByDesc('id')->paginate(15),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'max:20480']]);
        $file = $request->file('file');
        $filename = $file->getClientOriginalName();
        if (mb_strlen($filename) > 255) {
            throw ValidationException::withMessages(['file' => 'The filename must not exceed 255 characters.']);
        }

        $path = null;
        try {
            DB::transaction(function () use ($file, $filename, &$path) {
                $path = $file->store('documents', 'local');
                if ($path === false) {
                    throw ValidationException::withMessages(['file' => 'The file could not be stored. Please try again.']);
                }
                Document::create(['filename' => $filename, 'path' => $path]);
            });
        } catch (Throwable $exception) {
            if (is_string($path)) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Document uploaded.']);

        return to_route('documents.index');
    }

    public function show(Document $document): StreamedResponse
    {
        $disk = Storage::disk('local');
        abort_unless($disk->exists($document->storagePath()), 404);

        return $disk->download($document->storagePath(), $document->filename, [
            'Content-Type' => 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroy(Document $document): RedirectResponse
    {
        if (! Storage::disk('local')->delete($document->storagePath())) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'The file could not be deleted. Please try again.']);

            return back();
        }

        $document->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Document deleted.']);

        return back();
    }
}
