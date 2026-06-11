<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####')),
            'brand' => fake()->randomElement(['Belden', 'TP-Link', 'Ubiquiti', 'Generic']),
            'stock' => fake()->numberBetween(0, 200),
            'min_stock' => 5,
            'purchase_price' => fake()->numberBetween(1000, 500000),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
