<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_dashboard_shows_stats_recent_transactions_and_category_breakdown()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $expenseCategory = Category::factory()->expense()->create();
        $incomeCategory = Category::factory()->income()->create();

        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $expenseCategory->id,
            'amount' => 100,
            'description' => 'فاتورة الحالية',
            'date' => now(),
        ]);

        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $expenseCategory->id,
            'amount' => 50,
            'description' => 'فاتورة قديمة',
            'date' => now()->subMonths(2),
        ]);

        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $incomeCategory->id,
            'amount' => 300,
            'description' => 'دخل الشهر',
            'date' => now(),
        ]);

        Expense::factory()->create([
            'user_id' => $otherUser->id,
            'category_id' => $expenseCategory->id,
            'amount' => 9999,
            'description' => 'مصروف مستخدم آخر',
            'date' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('stats.total_income', 300)
                ->where('stats.total_expenses', 150)
                ->where('stats.total_balance', 150)
                ->where('stats.monthly_income', 300)
                ->where('stats.monthly_expenses', 100)
                ->where('stats.net_monthly', 200)
                ->where('stats.previous_month_income', 0)
                ->where('stats.previous_month_expenses', 0)
                ->where('stats.previous_month_net', 0)
                ->where('stats.currency', 'SAR')
                ->where('quick_stats.expense_count', 1)
                ->where('quick_stats.largest_transaction.amount', 100)
                ->where('quick_stats.largest_transaction.description', 'فاتورة الحالية')
                ->where('quick_stats.largest_transaction.type', 'expense')
                ->where('quick_stats.average_daily_expense', round(100 / max((int) now()->day, 1), 2))
                ->has('recent_transactions', 3)
                ->has('category_breakdown', 1)
                ->where('category_breakdown.0.name', $expenseCategory->name)
                ->where('category_breakdown.0.amount', 100)
                ->where('category_breakdown.0.percentage', 100)
            );
    }

    public function test_dashboard_includes_previous_month_comparison()
    {
        $user = User::factory()->create();

        $expenseCategory = Category::factory()->expense()->create();
        $incomeCategory = Category::factory()->income()->create();

        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $expenseCategory->id,
            'amount' => 200,
            'date' => now()->subMonth(),
        ]);

        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $expenseCategory->id,
            'amount' => 100,
            'date' => now(),
        ]);

        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $incomeCategory->id,
            'amount' => 150,
            'date' => now()->subMonth(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('stats.previous_month_expenses', 200)
                ->where('stats.previous_month_income', 150)
                ->where('stats.previous_month_net', -50)
            );
    }
}
