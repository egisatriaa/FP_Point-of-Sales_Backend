<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Transaction;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'transaction_code' => 'TRX-' . $this->faker->unique()->numerify('##############'),
            'transaction_date' => now(),
            'total_amount' => $this->faker->numberBetween(50000, 500000),
            'payment_amount' => $this->faker->numberBetween(50000, 500000),
            'change_amount' => 0,
            'status' => 'completed',
        ];
    }
}
