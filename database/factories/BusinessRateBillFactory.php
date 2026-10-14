<?php

namespace Database\Factories;

use App\Models\Biller;
use App\Models\BusinessRateBill;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BusinessRateBill> */
class BusinessRateBillFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'biller_id' => Biller::factory(),
            'unit_id' => Unit::factory(),
            'reference_number' => fake()->bothify('BR-####??'),
            'period_start' => '2026-01-01',
            'period_end' => '2026-03-31',
            'amount' => '1200.00',
        ];
    }
}
