<?php

namespace Database\Factories;

use App\Enums\InventoryItemType;
use App\Models\Department;
use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    protected $model = InventoryItem::class;

    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'created_by_id' => User::factory(),
            'type' => InventoryItemType::Asset,
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'serial_number' => fake()->unique()->bothify('SN-########'),
            'quantity' => null,
            'unit' => null,
        ];
    }

    public function asset(): static
    {
        return $this->state(fn () => [
            'type' => InventoryItemType::Asset,
            'serial_number' => fake()->unique()->bothify('SN-########'),
            'quantity' => null,
            'unit' => null,
        ]);
    }

    public function stock(): static
    {
        return $this->state(fn () => [
            'type' => InventoryItemType::Stock,
            'serial_number' => null,
            'quantity' => fake()->numberBetween(1, 100),
            'unit' => fake()->randomElement(['reams', 'boxes', 'units']),
        ]);
    }
}
