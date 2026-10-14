<?php

use App\Models\Tenancy;
use App\Models\Tenant;
use Inertia\Testing\AssertableInertia as Assert;

it('shows only current tenancies in the tenant index including the last occupancy day', function () {
    $this->travelTo(now()->setDate(2026, 10, 14));
    $tenant = Tenant::factory()->create();
    $ongoing = Tenancy::factory()->for($tenant)->create(['move_in_date' => '2026-01-01', 'move_out_date' => null]);
    $ending = Tenancy::factory()->for($tenant)->create(['move_in_date' => '2026-02-01', 'move_out_date' => '2026-10-14']);
    Tenancy::factory()->for($tenant)->create(['move_in_date' => '2025-01-01', 'move_out_date' => '2026-10-13']);
    Tenancy::factory()->for($tenant)->create(['move_in_date' => '2026-10-15', 'move_out_date' => null]);
    $this->get('/tenants')->assertInertia(fn (Assert $page) => $page->component('tenants/index')
        ->has('records.data.0.current_tenancies', 2)
        ->where('records.data.0.current_tenancies.0.id', $ending->id)
        ->where('records.data.0.current_tenancies.1.id', $ongoing->id)
        ->where('records.data.0.current_tenancies.0.unit.building.company.name', $ending->unit->building->company->name));
});

it('shows paginated tenancy history only for the selected tenant', function () {
    $tenant = Tenant::factory()->create();
    Tenancy::factory()->for($tenant)->count(16)->create();
    Tenancy::factory()->create();
    $this->get('/tenants/'.$tenant->id)->assertInertia(fn (Assert $page) => $page->component('tenants/show')
        ->where('tenant.id', $tenant->id)->where('tenancies.total', 16)->has('tenancies.data', 15));
    $this->get('/tenants/'.$tenant->id.'?page=2')->assertInertia(fn (Assert $page) => $page->has('tenancies.data', 1));
    $empty = Tenant::factory()->create();
    $this->get('/tenants/'.$empty->id)->assertInertia(fn (Assert $page) => $page->has('tenancies.data', 0));
    $this->get('/tenants/999999')->assertNotFound();
});
