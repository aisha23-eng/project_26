<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BudgetTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        return User::factory()->create();
    }

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('budgets'))->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_budgets_page(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)->get(route('budgets'))->assertOk();
    }

    public function test_budgets_page_shows_budget_with_spent_progress(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();

        $category = Category::factory()->expense()->create(['name' => 'وجبات']);
        $otherCategory = Category::factory()->expense()->create(['name' => 'سفر']);

        $budget = Budget::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 1000,
            'month' => now()->startOfMonth(),
        ]);

        Budget::factory()->create([
            'user_id' => $otherUser->id,
            'category_id' => $otherCategory->id,
            'amount' => 5000,
            'month' => now()->startOfMonth(),
        ]);

        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 400,
            'date' => now(),
        ]);

        Expense::factory()->create([
            'user_id' => $otherUser->id,
            'category_id' => $otherCategory->id,
            'amount' => 9999,
            'date' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('budgets'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Budgets')
                ->has('budgets', 1)
                ->where('budgets.0.category.name', 'وجبات')
                ->where('budgets.0.amount', 1000)
                ->where('budgets.0.spent', 400)
                ->where('budgets.0.remaining', 600)
                ->where('budgets.0.percentage', 40)
                ->where('budgets.0.status', 'ok')
            );
    }

    public function test_budget_is_marked_over_when_spending_exceeds_the_limit(): void
    {
        $user = $this->createUser();
        $category = Category::factory()->expense()->create();

        $budget = Budget::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 1000,
            'month' => now()->startOfMonth(),
        ]);

        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 1200,
            'date' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('budgets'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('budgets.0.status', 'over')
                ->where('budgets.0.percentage', 120)
            );
    }

    public function test_users_can_create_a_budget(): void
    {
        $user = $this->createUser();
        $category = Category::factory()->expense()->create();

        $this->actingAs($user)->post(route('budgets.store'), [
            'category_id' => $category->id,
            'amount' => 2500,
            'month' => now()->format('Y-m'),
        ])->assertRedirect();

        $this->assertDatabaseHas('budgets', [
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 2500,
        ]);
    }

    public function test_users_cannot_create_a_duplicate_budget_for_the_same_category_and_month(): void
    {
        $user = $this->createUser();
        $category = Category::factory()->expense()->create();

        Budget::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'month' => now()->startOfMonth(),
        ]);

        $response = $this->actingAs($user)->post(route('budgets.store'), [
            'category_id' => $category->id,
            'amount' => 2500,
            'month' => now()->format('Y-m'),
        ]);

        $response->assertSessionHasErrors('category_id');
        $this->assertDatabaseCount('budgets', 1);
    }

    public function test_users_can_update_a_budget(): void
    {
        $user = $this->createUser();
        $category = Category::factory()->expense()->create();

        $budget = Budget::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 1000,
            'month' => now()->startOfMonth(),
        ]);

        $this->actingAs($user)->patch(route('budgets.update', $budget), [
            'amount' => 3000,
            'month' => now()->format('Y-m'),
        ])->assertRedirect();

        $this->assertDatabaseHas('budgets', [
            'id' => $budget->id,
            'amount' => 3000,
        ]);
    }

    public function test_users_cannot_update_another_users_budget(): void
    {
        $user = $this->createUser();
        $owner = $this->createUser();
        $category = Category::factory()->expense()->create();

        $budget = Budget::factory()->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'amount' => 1000,
            'month' => now()->startOfMonth(),
        ]);

        $this->actingAs($user)
            ->patch(route('budgets.update', $budget), ['amount' => 3000, 'month' => now()->format('Y-m')])
            ->assertNotFound();
    }

    public function test_users_can_delete_a_budget(): void
    {
        $user = $this->createUser();
        $category = Category::factory()->expense()->create();

        $budget = Budget::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'month' => now()->startOfMonth(),
        ]);

        $this->actingAs($user)->delete(route('budgets.destroy', $budget))->assertRedirect();

        $this->assertDatabaseMissing('budgets', ['id' => $budget->id]);
    }
}
