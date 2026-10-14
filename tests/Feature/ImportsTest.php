<?php

use App\Models\Building;
use App\Models\Company;
use App\Models\Tenancy;
use App\Services\Imports\ReLeasedImport;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function reLeasedWorkbook(array $rows, ?array $headers = null): UploadedFile
{
    $book = new Spreadsheet;
    $book->getActiveSheet()->fromArray([$headers ?? ReLeasedImport::HEADERS, ...$rows]);
    $path = tempnam(sys_get_temp_dir(), 're-leased-');
    (new Xlsx($book))->save($path);
    $content = file_get_contents($path);
    unlink($path);
    $book->disconnectWorksheets();

    return UploadedFile::fake()->createWithContent('export.xlsx', $content);
}

function reLeasedRow(string $property = 'Building A', string $tenant = 'Tenant A', string $area = 'Unit A', mixed $start = '01/02/2026', mixed $end = '31/01/2027'): array
{
    return [$property, $tenant, $area, 'Occupied', 100, '1 year', $start, $end, 'Active', null, null, 1000, 10, 100, 'Monthly'];
}

it('shows the import page with company choices', function () {
    $company = Company::factory()->create();
    $this->get('/imports')->assertOk()->assertInertia(fn (Assert $page) => $page->component('imports/index')->where('companies.0.id', $company->id));
});

it('imports Excel rows and skips repeated rows and files', function () {
    $company = Company::factory()->create();
    $rows = [reLeasedRow(), reLeasedRow(), reLeasedRow(area: 'Unit B', end: null), reLeasedRow(start: 46054, end: 46418)];
    for ($i = 0; $i < 2; $i++) {
        $this->post('/imports', ['source' => 're-leased', 'company_id' => $company->id, 'file' => reLeasedWorkbook($rows)])
            ->assertRedirect('/imports')->assertSessionHasNoErrors();
    }
    $this->assertDatabaseCount('buildings', 1);
    $this->assertDatabaseCount('units', 2);
    $this->assertDatabaseCount('tenants', 1);
    $this->assertDatabaseCount('tenancies', 2);
    expect(Tenancy::whereDate('move_in_date', '2026-02-01')->whereDate('move_out_date', '2027-01-31')->count())->toBe(1);
    expect(Tenancy::whereNull('move_out_date')->count())->toBe(1);
});

it('scopes properties to company and reuses existing records', function () {
    $a = Company::factory()->create();
    $b = Company::factory()->create();
    $existing = Building::create(['company_id' => $a->id, 'name' => 'Building A']);
    foreach ([$a, $b] as $company) {
        $this->post('/imports', ['source' => 're-leased', 'company_id' => $company->id, 'file' => reLeasedWorkbook([reLeasedRow()])])->assertSessionHasNoErrors();
    }
    $this->assertDatabaseCount('buildings', 2);
    $this->assertDatabaseCount('units', 2);
    $this->assertDatabaseCount('tenants', 1);
    $this->assertDatabaseCount('tenancies', 2);
    $this->assertModelExists($existing);
});

it('rolls back all records when a later row is invalid', function () {
    $company = Company::factory()->create();
    $this->post('/imports', ['source' => 're-leased', 'company_id' => $company->id, 'file' => reLeasedWorkbook([reLeasedRow(), reLeasedRow(start: '31/02/2026')])])->assertSessionHasErrors('file');
    foreach (['buildings', 'units', 'tenants', 'tenancies'] as $table) {
        $this->assertDatabaseCount($table, 0);
    }
});

it('requires exact headers, valid expiry order and supported input', function () {
    $company = Company::factory()->create();
    $data = ['source' => 're-leased', 'company_id' => $company->id];
    $headers = ReLeasedImport::HEADERS;
    $headers[0] = 'property';
    $this->post('/imports', $data + ['file' => reLeasedWorkbook([reLeasedRow()], $headers)])->assertSessionHasErrors('file');
    $this->post('/imports', $data + ['file' => reLeasedWorkbook([reLeasedRow(end: '2020-01-01')])])->assertSessionHasErrors('file');
    $this->post('/imports', $data + ['file' => reLeasedWorkbook([])])->assertSessionHasErrors('file');
    $this->post('/imports', ['source' => 'other', 'company_id' => 99999])->assertSessionHasErrors(['source', 'company_id', 'file']);
    $this->assertDatabaseCount('tenancies', 0);
});

it('imports related records but skips tenancies with empty start dates', function () {
    $company = Company::factory()->create();
    $rows = [reLeasedRow(start: null, end: null), reLeasedRow(area: 'Unit B', start: '', end: '31/01/2027')];
    for ($i = 0; $i < 2; $i++) {
        $this->post('/imports', ['source' => 're-leased', 'company_id' => $company->id, 'file' => reLeasedWorkbook($rows)])
            ->assertRedirect('/imports')->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast.message', 'Import complete: 0 tenancies created, 0 existing tenancies skipped, 2 skipped without a start date.');
    }
    $this->assertDatabaseCount('buildings', 1);
    $this->assertDatabaseCount('units', 2);
    $this->assertDatabaseCount('tenants', 1);
    $this->assertDatabaseCount('tenancies', 0);
});

it('treats visually empty Excel date cells as blank', function (mixed $blank) {
    $company = Company::factory()->create();
    $rows = [reLeasedRow(end: $blank), reLeasedRow(area: 'Unit B', start: $blank, end: null)];
    $this->post('/imports', ['source' => 're-leased', 'company_id' => $company->id, 'file' => reLeasedWorkbook($rows)])
        ->assertRedirect('/imports')->assertSessionHasNoErrors();
    $this->assertDatabaseCount('tenancies', 1);
    expect(Tenancy::firstOrFail()->move_out_date)->toBeNull();
    $this->assertDatabaseCount('units', 2);
})->with([null, '', '   ', "\t\n", "\u{00A0}", "\u{200B}", '=""']);

it('skips the entire row when the tenant cell is blank', function (mixed $blank) {
    $company = Company::factory()->create();
    $row = reLeasedRow(property: '', area: '', start: 'invalid', end: 'invalid');
    $row[1] = $blank;
    $this->post('/imports', ['source' => 're-leased', 'company_id' => $company->id, 'file' => reLeasedWorkbook([$row, reLeasedRow()])])
        ->assertRedirect('/imports')->assertSessionHasNoErrors();
    foreach (['buildings', 'units', 'tenants', 'tenancies'] as $table) {
        $this->assertDatabaseCount($table, 1);
    }
})->with([null, '', '   ', "\u{00A0}", "\u{200B}", '=""']);
