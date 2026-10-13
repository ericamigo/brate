<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\BusinessRateBill;
use App\Models\Company;
use App\Models\Tenancy;
use App\Models\Tenant;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class BusinessRatesSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::firstOrCreate(['name' => 'Example Company']);
        $building = Building::firstOrCreate(['company_id' => $company->id, 'name' => 'Building A']);
        $unit = Unit::firstOrCreate(['building_id' => $building->id, 'name' => 'Unit 101']);
        $tenantA = Tenant::firstOrCreate(['name' => 'Tenant A']);
        $tenantB = Tenant::firstOrCreate(['name' => 'Tenant B']);

        Tenancy::whereDate('move_in_date', '2025-06-15')->firstOrCreate([
            'unit_id' => $unit->id,
            'tenant_id' => $tenantA->id,
        ], ['move_in_date' => '2025-06-15', 'move_out_date' => '2026-02-28']);

        Tenancy::whereDate('move_in_date', '2026-03-01')->firstOrCreate([
            'unit_id' => $unit->id,
            'tenant_id' => $tenantB->id,
        ], ['move_in_date' => '2026-03-01']);

        BusinessRateBill::firstOrCreate([
            'unit_id' => $unit->id,
            'reference_number' => 'EXAMPLE-2026-Q1',
        ], ['period_start' => '2026-01-01', 'period_end' => '2026-03-31', 'amount' => '1200.00']);
    }
}
