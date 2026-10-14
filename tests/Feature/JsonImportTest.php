<?php

use App\Models\Building;
use App\Models\Company;
use App\Models\Tenancy;
use App\Models\Tenant;
use App\Models\Unit;

function jsonImportRow(): array
{
    return [
        'company' => 'Sinclair', 'building' => 'High Street', 'unit' => 'Unit 1',
        'tenant' => 'Example Ltd', 'move_in_date' => '2026-01-01', 'move_out_date' => null,
    ];
}

it('imports JSON across companies and skips existing tenancies', function () {
    $rows = [jsonImportRow(), array_replace(jsonImportRow(), ['company' => 'Other Company', 'move_out_date' => '2026-03-31'])];
    foreach ([1, 2] as $attempt) {
        $this->post('/imports/json', ['json' => json_encode($rows)])
            ->assertRedirect('/imports')->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast.type', 'success');
        expect(Company::count())->toBe(2);
        expect(Building::count())->toBe(2);
        expect(Unit::count())->toBe(2);
        expect(Tenant::count())->toBe(1);
        expect(Tenancy::count())->toBe(2);
    }
    expect(Tenancy::whereNull('move_out_date')->count())->toBe(1);
});

it('rejects invalid JSON data without importing any rows', function (string $json) {
    $this->from('/imports')->post('/imports/json', ['json' => $json])
        ->assertRedirect('/imports')->assertSessionHasErrors('json');
    expect(Company::count())->toBe(0);
    expect(Tenancy::count())->toBe(0);
})->with([
    'malformed' => '{',
    'object' => json_encode(jsonImportRow()),
    'empty' => '[]',
    'scalar row' => '[123]',
    'missing company' => json_encode([array_diff_key(jsonImportRow(), ['company' => true])]),
    'blank name' => json_encode([array_replace(jsonImportRow(), ['tenant' => '   '])]),
    'invalid date after valid row' => json_encode([jsonImportRow(), array_replace(jsonImportRow(), ['move_in_date' => '2026-02-30'])]),
    'end before start' => json_encode([array_replace(jsonImportRow(), ['move_out_date' => '2025-12-31'])]),
]);

it('reuses existing records and trims imported names', function () {
    $unit = Unit::factory()->create(['name' => 'Unit 1']);
    $tenant = Tenant::factory()->create(['name' => 'Example Ltd']);
    $this->post('/imports/json', ['json' => json_encode([array_replace(jsonImportRow(), [
        'company' => $unit->building->company->name,
        'building' => $unit->building->name,
        'tenant' => ' Example Ltd ',
    ])])])->assertSessionHasNoErrors();
    expect(Company::count())->toBe(1);
    expect(Building::count())->toBe(1);
    expect(Unit::count())->toBe(1);
    expect(Tenancy::first()->tenant_id)->toBe($tenant->id);
});
