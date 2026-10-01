<?php

namespace Database\Factories;

use App\Models\TransportMode;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TransportMode>
 */
class TransportModeFactory extends Factory
{
    protected $model = TransportMode::class;

    public function definition(): array
    {
        $name = 'Moda '.fake()->unique()->word();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'code' => strtoupper(fake()->lexify('???')),
            'description' => fake()->sentence(),
            'icon' => 'heroicon-o-truck',
            'color' => 'primary',
            'sort_order' => 0,
            'is_active' => true,
            'settings' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
