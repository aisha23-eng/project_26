<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private const MONTH_LABELS = [
        'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو',
        'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر',
    ];

    private function createUser(): User
    {
        return User::factory()->create();
    }

    public function test_guests_are_redirected_to_the_login_page()
    {
        $this->get(route('reports'))->assertRedirect(route('login'));
    }

    public function test_reports_page_renders_expected_props_with_correct_sums()
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();

        $expenseCategory = Category::factory()->expense()->create(['name' => 'فئة مصاريف']);
        $incomeCategory = Category::factory()->income()->create(['name' => 'فئة دخل']);

        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $expenseCategory->id,
            'amount' => 100,
            'date' => now(),
        ]);

        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $expenseCategory->id,
            'amount' => 50,
            'date' => now()->copy()->subMonth(),
        ]);

        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $incomeCategory->id,
            'amount' => 300,
            'date' => now(),
        ]);

        Expense::factory()->create([
            'user_id' => $otherUser->id,
            'category_id' => $expenseCategory->id,
            'amount' => 9999,
            'date' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('reports'));
        $response->assertOk();

        $page = $response->viewData('page');
        $this->assertSame('Reports', $page['component']);
        $this->assertSame('SAR', $page['props']['currency']);

        $monthlySeries = $page['props']['monthly_series'];
        $this->assertCount(6, $monthlySeries);

        $last = $monthlySeries[count($monthlySeries) - 1];
        $this->assertSame(self::MONTH_LABELS[now()->month - 1], $last['label']);
        $this->assertSame(100.0, $last['expenses']);
        $this->assertSame(300.0, $last['income']);

        $totals = $page['props']['totals'];
        $this->assertSame(150.0, $totals['total_expenses']);
        $this->assertSame(300.0, $totals['total_income']);
        $this->assertSame(150.0, $totals['net']);
        $this->assertSame(25.0, $totals['avg_monthly_expenses']);

        $breakdown = $page['props']['category_breakdown'];
        $this->assertCount(1, $breakdown);
        $this->assertSame('فئة مصاريف', $breakdown[0]['name']);
        $this->assertSame(150.0, $breakdown[0]['amount']);
        $this->assertSame(100.0, $breakdown[0]['percentage']);

        $incomeBreakdown = $page['props']['income_breakdown'];
        $this->assertCount(1, $incomeBreakdown);
        $this->assertSame('فئة دخل', $incomeBreakdown[0]['name']);
        $this->assertSame(300.0, $incomeBreakdown[0]['amount']);
        $this->assertSame(100.0, $incomeBreakdown[0]['percentage']);
    }

    public function test_reports_export_returns_csv_with_bom_and_headers()
    {
        $user = $this->createUser();
        $expenseCategory = Category::factory()->expense()->create();

        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $expenseCategory->id,
            'description' => 'فواتير الإنترنت',
            'amount' => 120.50,
            'date' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('reports.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('id,type,date,description,vendor,category,amount,currency', $content);
        $this->assertStringContainsString('فواتير الإنترنت', $content);
        $this->assertStringContainsString('120.50', $content);
    }

    public function test_reports_export_is_scoped_to_the_user()
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();
        $expenseCategory = Category::factory()->expense()->create();

        Expense::factory()->create([
            'user_id' => $otherUser->id,
            'category_id' => $expenseCategory->id,
            'description' => 'معاملة سرية',
            'date' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('reports.export'));

        $content = $response->streamedContent();
        $this->assertStringNotContainsString('معاملة سرية', $content);
    }
}
