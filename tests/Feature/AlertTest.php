<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AlertTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        return User::factory()->create();
    }

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('alerts'))->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_alerts_page(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)->get(route('alerts'))->assertOk();
    }

    public function test_alerts_page_shows_budget_alert_when_spending_exceeds_threshold(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();

        $category = Category::factory()->expense()->create(['name' => 'تسويق']);
        $otherCategory = Category::factory()->expense()->create(['name' => 'سفر']);

        Budget::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 1000,
            'month' => now()->startOfMonth(),
        ]);

        Budget::factory()->create([
            'user_id' => $otherUser->id,
            'category_id' => $otherCategory->id,
            'amount' => 1000,
            'month' => now()->startOfMonth(),
        ]);

        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 900,
            'date' => now(),
        ]);

        Expense::factory()->create([
            'user_id' => $otherUser->id,
            'category_id' => $otherCategory->id,
            'amount' => 9999,
            'date' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('alerts'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Alerts')
                ->has('budget_alerts', 1)
                ->where('budget_alerts.0.category.name', 'تسويق')
                ->where('budget_alerts.0.type', 'near_budget')
                ->where('budget_alerts.0.percentage', 90)
                ->where('count', 1)
            );
    }

    public function test_budget_alert_is_marked_over_budget_when_limit_is_exceeded(): void
    {
        $user = $this->createUser();
        $category = Category::factory()->expense()->create();

        Budget::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 1000,
            'month' => now()->startOfMonth(),
        ]);

        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 1100,
            'date' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('alerts'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('budget_alerts.0.type', 'over_budget')
            );
    }

    public function test_alerts_page_does_not_flag_healthy_budgets(): void
    {
        $user = $this->createUser();
        $category = Category::factory()->expense()->create();

        Budget::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 1000,
            'month' => now()->startOfMonth(),
        ]);

        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 500,
            'date' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('alerts'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('budget_alerts', 0)
                ->where('count', 0)
            );
    }

    public function test_alerts_page_lists_upcoming_recurring_bills(): void
    {
        $user = $this->createUser();
        $category = Category::factory()->expense()->create();

        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'description' => 'اشتراك شهري',
            'amount' => 99,
            'is_recurring' => true,
            'recurring_frequency' => 'monthly',
            'recurring_next_date' => now()->addDays(3),
        ]);

        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'description' => 'فاتورة قديمة',
            'is_recurring' => true,
            'recurring_frequency' => 'monthly',
            'recurring_next_date' => now()->subDays(5),
        ]);

        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'description' => 'فار لاحقاً',
            'is_recurring' => true,
            'recurring_frequency' => 'monthly',
            'recurring_next_date' => now()->addDays(30),
        ]);

        $this->actingAs($user)
            ->get(route('alerts'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('bill_reminders', 2)
                ->where('bill_reminders.0.description', 'فاتورة قديمة')
                ->where('bill_reminders.0.overdue', true)
                ->where('bill_reminders.1.description', 'اشتراك شهري')
                ->where('bill_reminders.1.overdue', false)
            );
    }
}
