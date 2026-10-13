<?php

namespace Database\Factories;

use App\Models\Tenancy;
use App\Models\Tenant;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Tenancy> */
class TenancyFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'unit_id' => Unit::factory(),
            'tenant_id' => Tenant::factory(),
            'move_in_date' => '2026-01-01',
            'move_out_date' => null,
        ];
    }
}
