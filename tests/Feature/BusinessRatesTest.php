<?php

use App\Enums\BillingStage;
use App\Models\Biller;
use App\Models\Building;
use App\Models\BusinessRateBill;
use App\Models\Company;
use App\Models\Tenancy;
use App\Models\Tenant;
use App\Models\Unit;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

it('manages business rates resources without login', function (string $resource, string $model, string $field, string $value) {
    $data = $model::factory()->raw();
    $this->get('/'.$resource)->assertOk()->assertInertia(fn (Assert $page) => $page->component($resource.'/index'));
    if (in_array($resource, ['tenancies', 'business-rate-bills'])) {
        $this->get('/'.$resource.'/create')->assertOk()->assertInertia(fn (Assert $page) => $page->component($resource.'/create'));
    } else {
        expect(Route::has($resource.'.create'))->toBeFalse();
    }
    $this->post('/'.$resource, $data)->assertRedirect('/'.$resource)->assertSessionHasNoErrors();
    $saved = $model::latest('id')->firstOrFail();
    $this->assertModelExists($saved);
    if (in_array($resource, ['tenancies', 'business-rate-bills'])) {
        $this->get('/'.$resource.'/'.$saved->id.'/edit')->assertOk()->assertInertia(fn (Assert $page) => $page->component($resource.'/edit'));
    } else {
        expect(Route::has($resource.'.edit'))->toBeFalse();
    }
    $this->put('/'.$resource.'/'.$saved->id, array_replace($data, [$field => $value]))->assertRedirect('/'.$resource)->assertSessionHasNoErrors();
    $actual = $saved->fresh()->getAttribute($field);
    expect($actual instanceof CarbonInterface ? $actual->toDateString() : $actual)->toBe($value);
    $this->delete('/'.$resource.'/'.$saved->id)->assertRedirect('/'.$resource);
    $this->assertModelMissing($saved);
})->with([
    ['billers', Biller::class, 'name', 'Updated biller'],
    ['companies', Company::class, 'name', 'New company'],
    ['buildings', Building::class, 'address', 'New address'],
    ['units', Unit::class, 'name', 'New unit'],
    ['tenants', Tenant::class, 'name', 'New tenant'],
    ['tenancies', Tenancy::class, 'move_out_date', '2026-03-01'],
    ['business-rate-bills', BusinessRateBill::class, 'reference_number', 'Updated bill'],
]);

it('matches historical occupants and displays vacant bills', function () {
    $unit = Unit::factory()->create();
    BusinessRateBill::factory()->for($unit)->create();
    $a = Tenancy::factory()->for($unit)->create(['move_in_date' => '2025-06-15', 'move_out_date' => '2026-02-28']);
    $b = Tenancy::factory()->for($unit)->create(['move_in_date' => '2026-03-01']);
    Tenancy::factory()->for($unit)->create(['move_in_date' => '2026-04-01']);
    BusinessRateBill::factory()->create(['period_start' => '2025-01-01', 'period_end' => '2025-03-31']);

    $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('occupancy-report/index')->has('rows', 3)
        ->where('rows.0.tenancy_id', $a->id)->where('rows.0.occupied_start', '2026-01-01')->where('rows.0.occupied_end', '2026-02-28')
        ->where('rows.1.tenancy_id', $b->id)->where('rows.1.occupied_start', '2026-03-01')->where('rows.1.occupied_end', '2026-03-31')
        ->where('rows.2.occupant', null));
});

it('requires existing billers and displays their names', function () {
    $data = BusinessRateBill::factory()->raw();
    $biller = Biller::factory()->create(['name' => 'CDER']);
    $data['biller_id'] = $biller->id;
    $this->post('/business-rate-bills', $data)->assertRedirect('/business-rate-bills')->assertSessionHasNoErrors();
    $bill = BusinessRateBill::latest('id')->firstOrFail();
    expect($bill->biller_id)->toBe($biller->id);
    $this->get('/business-rate-bills')->assertInertia(fn (Assert $page) => $page->where('records.data.0.biller.name', 'CDER'));
    $other = Biller::factory()->create(['name' => 'London Borough of Hounslow (Council)']);
    $data['biller_id'] = $other->id;
    $this->put('/business-rate-bills/'.$bill->id, $data)->assertSessionHasNoErrors();
    $this->get('/business-rate-bills/'.$bill->id.'/edit')->assertInertia(fn (Assert $page) => $page->where('record.biller_id', $other->id));
    $this->get('/')->assertInertia(fn (Assert $page) => $page->where('rows.0.biller', $other->name));
    foreach ([null, 999999] as $invalid) {
        $data['biller_id'] = $invalid;
        $this->post('/business-rate-bills', $data)->assertSessionHasErrors('biller_id');
        $this->put('/business-rate-bills/'.$bill->id, $data)->assertSessionHasErrors('biller_id');
    }
    expect($bill->fresh()->biller_id)->toBe($other->id);
    $this->assertDatabaseCount('business_rate_bills', 1);
    $this->delete('/billers/'.$other->id)->assertInertiaFlash('toast.type', 'error');
    $this->assertModelExists($other);
});

it('includes occupancy on the final billing day', function () {
    $bill = BusinessRateBill::factory()->create();
    $tenancy = Tenancy::factory()->for($bill->unit)->create(['move_in_date' => '2026-03-31']);
    $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page->has('rows', 1)->where('rows.0.tenancy_id', $tenancy->id)->where('rows.0.occupied_start', '2026-03-31')->where('rows.0.occupied_end', '2026-03-31'));
});

it('validates date order and amounts without creating records', function () {
    $tenancy = Tenancy::factory()->raw();
    $bill = BusinessRateBill::factory()->raw();
    $this->post('/tenancies', [])->assertSessionHasErrors(['unit_id', 'tenant_id', 'move_in_date']);
    $this->post('/business-rate-bills', [])->assertSessionHasErrors(['biller_id', 'unit_id', 'period_start', 'period_end', 'amount']);
    $this->post('/tenancies', array_replace($tenancy, ['move_out_date' => '2025-12-31']))->assertSessionHasErrors('move_out_date');
    $this->post('/business-rate-bills', array_replace($bill, ['period_end' => '2025-12-31', 'amount' => '-1']))->assertSessionHasErrors(['period_end', 'amount']);
    $this->assertDatabaseCount('tenancies', 0);
    $this->assertDatabaseCount('business_rate_bills', 0);
});

it('filters billing dates and properties and keeps tenancy rows together across pages', function () {
    $bill = BusinessRateBill::factory()->create();
    Tenancy::factory()->count(2)->for($bill->unit)->create();
    BusinessRateBill::factory()->count(15)->for($bill->unit)->create();
    BusinessRateBill::factory()->create();
    $filters = ['company_id' => $bill->unit->building->company_id, 'building_id' => $bill->unit->building_id, 'unit_id' => $bill->unit_id, 'period_start' => '2026-03-31', 'period_end' => '2026-04-01'];
    $this->get('/?'.http_build_query($filters))->assertOk()->assertInertia(fn (Assert $page) => $page->where('bills.total', 16)->has('rows', 30));
    $this->get('/?'.http_build_query($filters + ['page' => 2]))->assertOk()->assertInertia(fn (Assert $page) => $page->has('rows', 2));
    $this->get('/?period_end=2025-12-31')->assertOk()->assertInertia(fn (Assert $page) => $page->has('rows', 0));
});

it('blocks deletion of parents with historical records', function () {
    $tenancy = Tenancy::factory()->create();
    $bill = BusinessRateBill::factory()->for($tenancy->unit)->create();
    foreach ([['companies', $tenancy->unit->building->company], ['buildings', $tenancy->unit->building], ['units', $tenancy->unit], ['tenants', $tenancy->tenant]] as [$resource, $parent]) {
        $this->delete('/'.$resource.'/'.$parent->id)->assertInertiaFlash('toast.type', 'error');
        $this->assertModelExists($parent);
    }
    $this->assertModelExists($tenancy);
    $this->assertModelExists($bill);
});

it('manages bill case numbers and enum stages', function () {
    $data = BusinessRateBill::factory()->raw();
    $this->post('/business-rate-bills', $data + ['case_number' => 'CASE-123', 'stage' => 'enforcement'])->assertSessionHasNoErrors();
    $bill = BusinessRateBill::latest('id')->firstOrFail();
    expect($bill->case_number)->toBe('CASE-123')->and($bill->stage)->toBe(BillingStage::Enforcement);
    $this->get('/business-rate-bills/create')->assertInertia(fn (Assert $page) => $page->where('fields.0.name', 'case_number')->where('record.stage', 'unverified'));
    $this->get('/business-rate-bills/'.$bill->id.'/edit')->assertInertia(fn (Assert $page) => $page->where('record.stage', 'enforcement')->where('record.case_number', 'CASE-123'));
    $this->put('/business-rate-bills/'.$bill->id, $data + ['case_number' => null, 'stage' => 'summon'])->assertSessionHasNoErrors();
    expect($bill->fresh()->case_number)->toBeNull()->and($bill->fresh()->stage)->toBe(BillingStage::Summon);
    $this->put('/business-rate-bills/'.$bill->id, $data + ['stage' => 'unknown'])->assertSessionHasErrors('stage');
    $this->put('/business-rate-bills/'.$bill->id, $data + ['stage' => null])->assertSessionHasNoErrors();
    expect($bill->fresh()->stage)->toBeNull();
});

it('filters units by company, building and name', function () {
    $a = Unit::factory()->create(['name' => 'Matching unit']);
    $b = Unit::factory()->for($a->building)->create(['name' => 'Other unit']);
    $otherBuilding = Building::factory()->create(['company_id' => $a->building->company_id]);
    Unit::factory()->for($otherBuilding)->create();
    Unit::factory()->create();
    $this->get('/units?company_id='.$a->building->company_id)->assertInertia(fn (Assert $page) => $page->where('records.total', 3));
    $this->get('/units?building_id='.$a->building_id)->assertInertia(fn (Assert $page) => $page->where('records.total', 2));
    $this->get('/units?'.http_build_query(['company_id' => $a->building->company_id, 'building_id' => $a->building_id, 'search' => 'Matching']))
        ->assertInertia(fn (Assert $page) => $page->where('records.total', 1)->where('records.data.0.id', $a->id));
    $this->get('/units?company_id=999999&building_id=999999')->assertSessionHasErrors(['company_id', 'building_id']);
});

it('filters tenancies by company, building, unit and search', function () {
    $a = Tenancy::factory()->create();
    Tenancy::factory()->for($a->unit)->create();
    $unit = Unit::factory()->for($a->unit->building)->create();
    Tenancy::factory()->for($unit)->create();
    $building = Building::factory()->create(['company_id' => $a->unit->building->company_id]);
    Tenancy::factory()->for(Unit::factory()->for($building)->create())->create();
    Tenancy::factory()->create();
    $this->get('/tenancies?company_id='.$a->unit->building->company_id)->assertInertia(fn (Assert $page) => $page->where('records.total', 4));
    $this->get('/tenancies?building_id='.$a->unit->building_id)->assertInertia(fn (Assert $page) => $page->where('records.total', 3));
    $this->get('/tenancies?unit_id='.$a->unit_id)->assertInertia(fn (Assert $page) => $page->where('records.total', 2));
    $this->get('/tenancies?'.http_build_query(['company_id' => $a->unit->building->company_id, 'building_id' => $a->unit->building_id, 'unit_id' => $a->unit_id, 'search' => $a->unit->name]))
        ->assertInertia(fn (Assert $page) => $page->where('records.total', 2));
    $this->get('/tenancies?company_id=999999&building_id=999999&unit_id=999999')->assertSessionHasErrors(['company_id', 'building_id', 'unit_id']);
});

it('searches tenancies by tenant or unit name while preserving filters', function () {
    $tenant = Tenant::factory()->create(['name' => 'Searchable Tenant']);
    $match = Tenancy::factory()->for($tenant)->create();
    Tenancy::factory()->for($tenant)->create();
    Tenancy::factory()->create();
    $this->get('/tenancies?search=Searchable')->assertInertia(fn (Assert $page) => $page->where('records.total', 2));
    $this->get('/tenancies?'.http_build_query(['search' => 'Searchable', 'unit_id' => $match->unit_id]))
        ->assertInertia(fn (Assert $page) => $page->where('records.total', 1)->where('records.data.0.id', $match->id));
});

it('filters bills by biller, stage, company and building independently and together', function () {
    $bill = BusinessRateBill::factory()->create(['stage' => BillingStage::Enforcement]);
    BusinessRateBill::factory()->create(['stage' => BillingStage::Summon]);
    $filters = [
        'biller_id' => $bill->biller_id,
        'stage' => BillingStage::Enforcement->value,
        'company_id' => $bill->unit->building->company_id,
        'building_id' => $bill->unit->building_id,
    ];

    foreach ($filters as $key => $value) {
        $this->get('/business-rate-bills?'.http_build_query([$key => $value]))
            ->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('records.total', 1)
            ->where('records.data.0.id', $bill->id));
    }

    $this->get('/business-rate-bills?'.http_build_query($filters + ['search' => $bill->unit->name]))
        ->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('records.total', 1)
        ->where('records.data.0.id', $bill->id)
        ->where('filters.stage', 'enforcement')
        ->has('billers', 2)->has('stages', 3)->has('companies', 2)->has('buildings', 2));

    $this->get('/business-rate-bills?'.http_build_query(array_replace($filters, ['stage' => 'summon'])))
        ->assertOk()->assertInertia(fn (Assert $page) => $page->where('records.total', 0));

    BusinessRateBill::factory()->count(15)->for($bill->unit)->for($bill->biller)
        ->create(['stage' => BillingStage::Enforcement]);
    $this->get('/business-rate-bills?'.http_build_query($filters + ['page' => 2]))
        ->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('records.total', 16)->has('records.data', 1)
        ->where('records.prev_page_url', fn ($url) => collect($filters)->every(
            fn ($value, $key) => str_contains($url, $key.'='.$value)
        )));
});

it('validates bill index filters', function () {
    $this->from('/business-rate-bills')->get('/business-rate-bills?'.http_build_query([
        'biller_id' => 999999, 'stage' => 'unknown', 'company_id' => 999999, 'building_id' => 999999,
    ]))->assertSessionHasErrors(['biller_id', 'stage', 'company_id', 'building_id']);
});
