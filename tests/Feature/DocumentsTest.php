<?php

use App\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

it('uploads, lists and downloads documents with duplicate filenames', function () {
    Storage::fake('local');
    foreach (['first file', 'second file'] as $content) {
        $this->post('/documents', ['file' => UploadedFile::fake()->createWithContent('report.txt', $content)])
            ->assertRedirect('/documents')->assertSessionHasNoErrors();
    }
    $documents = Document::orderBy('id')->get();
    expect($documents)->toHaveCount(2);
    foreach ($documents as $index => $document) {
        Storage::disk('local')->assertExists($document->storagePath());
        $response = $this->get('/documents/'.$document->id)->assertOk()->assertDownload('report.txt');
        expect($response->streamedContent())->toBe($index === 0 ? 'first file' : 'second file');
    }
    $this->get('/documents')->assertInertia(fn (Assert $page) => $page
        ->component('documents/index')->has('documents.data', 2)
        ->where('documents.data.0.id', $documents->last()->id));
});

it('rejects missing and oversized uploads', function () {
    Storage::fake('local');
    $this->post('/documents', [])->assertSessionHasErrors('file');
    $this->post('/documents', ['file' => UploadedFile::fake()->create('large.pdf', 20481)])->assertSessionHasErrors('file');
    $this->assertDatabaseCount('documents', 0);
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
});

it('returns not found for missing documents or files', function () {
    Storage::fake('local');
    $this->get('/documents/99999')->assertNotFound();
    $document = Document::create(['filename' => 'missing.txt', 'path' => 'documents/missing']);
    $this->get('/documents/'.$document->id)->assertNotFound();
});
