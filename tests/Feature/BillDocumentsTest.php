<?php

use App\Models\BusinessRateBill;
use App\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

it('creates bills with multiple linked image and PDF documents', function () {
    Storage::fake('local');
    $this->post('/business-rate-bills', BusinessRateBill::factory()->raw() + ['files' => [UploadedFile::fake()->image('photo.png'), UploadedFile::fake()->create('bill.pdf', 10, 'application/pdf')]])
        ->assertRedirect('/business-rate-bills')->assertSessionHasNoErrors();
    $bill = BusinessRateBill::firstOrFail();
    expect($bill->documents)->toHaveCount(2);
    foreach ($bill->documents as $document) {
        expect($document->documentable->is($bill))->toBeTrue();
        Storage::disk('local')->assertExists($document->path);
        $this->get('/documents/'.$document->id)->assertDownload($document->filename);
    }
    $this->get('/business-rate-bills/'.$bill->id)->assertInertia(fn (Assert $page) => $page->component('business-rate-bills/show')->has('bill.documents', 2));
});

it('adds documents only to the selected bill', function () {
    Storage::fake('local');
    $bill = BusinessRateBill::factory()->create();
    $other = BusinessRateBill::factory()->create();
    $this->post('/business-rate-bills/'.$bill->id.'/documents', ['files' => [UploadedFile::fake()->image('photo.jpg')]])
        ->assertRedirect('/business-rate-bills/'.$bill->id)->assertSessionHasNoErrors()->assertInertiaFlash('toast.type', 'success');
    expect($bill->documents()->count())->toBe(1)->and($other->documents()->count())->toBe(0);
    $this->post('/business-rate-bills/999999/documents', ['files' => [UploadedFile::fake()->image('photo.jpg')]])->assertNotFound();
});

it('rejects unsupported or oversized attachments without creating a bill', function () {
    Storage::fake('local');
    $data = BusinessRateBill::factory()->raw();
    foreach ([UploadedFile::fake()->create('file.txt', 10, 'text/plain'), UploadedFile::fake()->create('large.pdf', 20481, 'application/pdf')] as $file) {
        $this->post('/business-rate-bills', $data + ['files' => [$file]])->assertSessionHasErrors('files.0');
    }
    $this->assertDatabaseCount('business_rate_bills', 0);
    $this->assertDatabaseCount('documents', 0);
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
});

it('rolls back uploaded files and the bill when a later file cannot be saved', function () {
    Storage::fake('local');
    $this->post('/business-rate-bills', BusinessRateBill::factory()->raw() + ['files' => [UploadedFile::fake()->image('photo.png'), UploadedFile::fake()->create(str_repeat('a', 256).'.pdf', 10, 'application/pdf')]])
        ->assertSessionHasErrors('files.1');
    $this->assertDatabaseCount('business_rate_bills', 0);
    $this->assertDatabaseCount('documents', 0);
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
});

it('keeps standalone documents and preserves attachments when a bill is deleted', function () {
    Storage::fake('local');
    $standalone = Document::create(['filename' => 'standalone.pdf', 'path' => 'documents/standalone']);
    expect($standalone->documentable)->toBeNull();
    $bill = BusinessRateBill::factory()->create();
    $this->post('/business-rate-bills/'.$bill->id.'/documents', ['files' => [UploadedFile::fake()->image('photo.jpg')]])->assertSessionHasNoErrors();
    $attachment = $bill->documents()->firstOrFail();
    $this->delete('/business-rate-bills/'.$bill->id)->assertRedirect('/business-rate-bills');
    expect($attachment->fresh()->documentable_id)->toBeNull()->and($attachment->fresh()->documentable_type)->toBeNull();
    $this->get('/documents/'.$attachment->id)->assertDownload('photo.jpg');
});

it('deletes bill attachments without deleting the bill and returns to details', function () {
    Storage::fake('local');
    $bill = BusinessRateBill::factory()->create();
    $this->post('/business-rate-bills/'.$bill->id.'/documents', ['files' => [UploadedFile::fake()->image('photo.jpg')]]);
    $document = $bill->documents()->firstOrFail();
    $this->from('/business-rate-bills/'.$bill->id)->delete('/documents/'.$document->id)
        ->assertRedirect('/business-rate-bills/'.$bill->id)->assertInertiaFlash('toast.type', 'success');
    $this->assertModelMissing($document);
    $this->assertModelExists($bill);
    Storage::disk('local')->assertMissing($document->path);
    expect($bill->documents()->count())->toBe(0);
});
