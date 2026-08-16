<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'amount' => fake()->randomFloat(2, 10, 1000),
            'currency' => 'SAR',
            'description' => fake()->sentence(4),
            'vendor' => fake()->company(),
            'date' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }

    public function recurring(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_recurring' => true,
            'recurring_frequency' => fake()->randomElement(['daily', 'weekly', 'monthly', 'yearly']),
            'recurring_start_date' => now(),
            'recurring_next_date' => now()->addMonth(),
        ]);
    }

    public function withReceipt(): static
    {
        return $this->state(fn (array $attributes) => [
            'receipt_path' => 'receipts/'.fake()->uuid().'.jpg',
        ]);
    }

    public function uncategorized(): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => null,
        ]);
    }
}
