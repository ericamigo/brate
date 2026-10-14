<?php

use App\Models\Biller;
use App\Models\Building;
use App\Models\BusinessRateBill;
use App\Models\Company;
use App\Models\Tenancy;
use App\Models\Tenant;
use App\Models\Unit;
use Carbon\CarbonInterface;
use Inertia\Testing\AssertableInertia as Assert;

it('manages business rates resources without login', function (string $resource, string $model, string $field, string $value) {
    $data = $model::factory()->raw();
    $this->get('/'.$resource)->assertOk()->assertInertia(fn (Assert $page) => $page->component('business-rates/index'));
    $this->get('/'.$resource.'/create')->assertOk()->assertInertia(fn (Assert $page) => $page->component('business-rates/form'));
    $this->post('/'.$resource, $data)->assertRedirect('/'.$resource)->assertSessionHasNoErrors();
    $saved = $model::latest('id')->firstOrFail();
    $this->assertModelExists($saved);
    $this->get('/'.$resource.'/'.$saved->id.'/edit')->assertOk();
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
        ->component('business-rates/report')->has('rows', 3)
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
    $this->delete('/billers/'.$other->id)->assertSessionHas('error');
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
        $this->delete('/'.$resource.'/'.$parent->id)->assertSessionHas('error');
        $this->assertModelExists($parent);
    }
    $this->assertModelExists($tenancy);
    $this->assertModelExists($bill);
});
