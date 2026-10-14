<?php

namespace Database\Factories;

use App\Models\Biller;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Biller> */
class BillerFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
        ];
    }
}
