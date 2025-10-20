<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RoomFactory extends Factory
{
    public function definition(): array
    {
        return [
            'avatar' => 'https://api.dicebear.com/6.x/bottts/svg?seed='.fake()->word(),
            'name' => fake()->company(),
            'reference' => fake()->unique()->slug(),
        ];
    }
}
