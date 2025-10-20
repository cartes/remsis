<?php

namespace Modules\AdminPanel\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BankFactoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\AdminPanel\Models\BankFactory::class;

    /**
     * Define the model's default state.
     */
    public function definition()
    {
        return [
            'name' => $this->faker->unique()->company . ' Bank',
            'code' => $this->faker->unique()->swiftBicNumber,
            'status' => 'active',
        ];
    }
}

