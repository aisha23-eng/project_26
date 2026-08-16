<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        return User::factory()->create();
    }

    /**
     * @return array{0: Category, 1: Category}
     */
    private function createCategories(): array
    {
        return [
            Category::factory()->expense()->create(),
            Category::factory()->income()->create(),
        ];
    }

    public function test_guests_are_redirected_to_the_login_page()
    {
        $this->get(route('expenses'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_store_an_expense()
    {
        $user = $this->createUser();
        [$expenseCategory] = $this->createCategories();

        $response = $this->actingAs($user)
            ->from(route('expenses'))
            ->post(route('transactions.store'), [
                'description' => 'شراء أدوات',
                'amount' => 120.50,
                'date' => now()->toDateString(),
                'vendor' => 'مكتبة الرياض',
                'category_id' => $expenseCategory->id,
                'type' => 'expense',
            ]);

        $response
            ->assertRedirect(route('expenses', absolute: false))
            ->assertSessionHas('inertia.flash_data', [
                'toast' => ['type' => 'success', 'message' => 'Transaction created.'],
            ]);

        $this->assertDatabaseHas('expenses', [
            'user_id' => $user->id,
            'category_id' => $expenseCategory->id,
            'description' => 'شراء أدوات',
            'amount' => 120.50,
            'vendor' => 'مكتبة الرياض',
        ]);
    }

    public function test_authenticated_user_can_store_an_income_record()
    {
        $user = $this->createUser();
        [, $incomeCategory] = $this->createCategories();

        $response = $this->actingAs($user)
            ->from(route('income'))
            ->post(route('transactions.store'), [
                'description' => 'إيراد جديد',
                'amount' => 500,
                'date' => now()->toDateString(),
                'category_id' => $incomeCategory->id,
                'type' => 'income',
            ]);

        $response->assertRedirect(route('income', absolute: false));

        $this->assertDatabaseHas('expenses', [
            'user_id' => $user->id,
            'category_id' => $incomeCategory->id,
            'description' => 'إيراد جديد',
        ]);
    }

    public function test_store_falls_back_to_the_other_category_when_none_is_given()
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)
            ->from(route('expenses'))
            ->post(route('transactions.store'), [
                'description' => 'بدون فئة',
                'amount' => 25,
                'date' => now()->toDateString(),
                'type' => 'expense',
            ]);

        $response->assertRedirect(route('expenses', absolute: false));

        $this->assertDatabaseHas('expenses', [
            'user_id' => $user->id,
            'description' => 'بدون فئة',
            'category_id' => Category::where('name', 'أخرى')->where('type', 'expense')->where('is_system', true)->firstOrFail()->id,
        ]);
    }

    public function test_store_validation_fails_when_description_and_amount_are_missing()
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)
            ->from(route('expenses'))
            ->post(route('transactions.store'), [
                'type' => 'expense',
            ]);

        $response->assertSessionHasErrors(['description', 'amount']);
        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_store_rejects_a_category_that_does_not_match_the_type()
    {
        $user = $this->createUser();
        [$expenseCategory, $incomeCategory] = $this->createCategories();

        $response = $this->actingAs($user)
            ->from(route('expenses'))
            ->post(route('transactions.store'), [
                'description' => 'فئة خاطئة',
                'amount' => 50,
                'date' => now()->toDateString(),
                'category_id' => $incomeCategory->id,
                'type' => 'expense',
            ]);

        $response->assertSessionHasErrors('category_id');
        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_user_can_update_their_transaction()
    {
        $user = $this->createUser();
        [$expenseCategory] = $this->createCategories();

        $transaction = Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $expenseCategory->id,
            'amount' => 100,
            'description' => 'قبل التحديث',
            'date' => now(),
        ]);

        $response = $this->actingAs($user)
            ->from(route('expenses'))
            ->patch(route('transactions.update', $transaction), [
                'description' => 'بعد التحديث',
                'amount' => 150,
                'date' => now()->toDateString(),
                'vendor' => null,
                'category_id' => $expenseCategory->id,
                'type' => 'expense',
            ]);

        $response->assertRedirect(route('expenses', absolute: false));

        $this->assertDatabaseHas('expenses', [
            'id' => $transaction->id,
            'description' => 'بعد التحديث',
            'amount' => 150,
        ]);
    }

    public function test_update_reassigns_category_when_the_type_changes()
    {
        $user = $this->createUser();
        [$expenseCategory] = $this->createCategories();

        $transaction = Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $expenseCategory->id,
            'amount' => 100,
            'date' => now(),
        ]);

        $otherIncome = Category::where('name', 'أخرى')->where('type', 'income')->where('is_system', true)->firstOrFail();

        $response = $this->actingAs($user)
            ->from(route('expenses'))
            ->patch(route('transactions.update', $transaction), [
                'description' => 'تحويل إلى دخل',
                'amount' => 100,
                'date' => now()->toDateString(),
                'vendor' => null,
                'type' => 'income',
            ]);

        $response->assertRedirect(route('expenses', absolute: false));

        $this->assertDatabaseHas('expenses', [
            'id' => $transaction->id,
            'category_id' => $otherIncome->id,
        ]);
    }

    public function test_user_cannot_update_another_users_transaction()
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();
        [$expenseCategory] = $this->createCategories();

        $transaction = Expense::factory()->create([
            'user_id' => $otherUser->id,
            'category_id' => $expenseCategory->id,
        ]);

        $this->actingAs($user)
            ->patch(route('transactions.update', $transaction), [
                'description' => 'تعديل مسروق',
                'amount' => 10,
                'date' => now()->toDateString(),
                'vendor' => null,
                'type' => 'expense',
            ])
            ->assertNotFound();
    }

    public function test_user_cannot_delete_another_users_transaction()
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();
        [$expenseCategory] = $this->createCategories();

        $transaction = Expense::factory()->create([
            'user_id' => $otherUser->id,
            'category_id' => $expenseCategory->id,
        ]);

        $this->actingAs($user)
            ->delete(route('transactions.destroy', $transaction))
            ->assertNotFound();

        $this->assertDatabaseHas('expenses', ['id' => $transaction->id]);
    }

    public function test_delete_soft_deletes_the_transaction()
    {
        $user = $this->createUser();
        [$expenseCategory] = $this->createCategories();

        $transaction = Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $expenseCategory->id,
        ]);

        $this->actingAs($user)
            ->from(route('expenses'))
            ->delete(route('transactions.destroy', $transaction))
            ->assertRedirect(route('expenses', absolute: false));

        $this->assertSoftDeleted('expenses', ['id' => $transaction->id]);
    }

    public function test_expenses_page_only_shows_expense_records()
    {
        $user = $this->createUser();
        [$expenseCategory, $incomeCategory] = $this->createCategories();

        $expense = Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $expenseCategory->id,
            'description' => 'فاتورة الإنترنت',
            'amount' => 100,
            'date' => now(),
        ]);

        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $incomeCategory->id,
            'description' => 'عائدات مبيعات',
            'amount' => 1000,
            'date' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('expenses'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Expenses')
                ->where('currency', 'SAR')
                ->where('filters.search', null)
                ->has('transactions', 1)
                ->where('transactions.0.id', $expense->id)
                ->where('transactions.0.type', 'expense')
                ->where('transactions.0.amount', 100)
                ->has('categories')
            );
    }

    public function test_income_page_only_shows_income_records()
    {
        $user = $this->createUser();
        [$expenseCategory, $incomeCategory] = $this->createCategories();

        $income = Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $incomeCategory->id,
            'description' => 'عائدات مبيعات',
            'amount' => 1000,
            'date' => now(),
        ]);

        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $expenseCategory->id,
            'description' => 'فاتورة الإنترنت',
            'amount' => 100,
            'date' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('income'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Income')
                ->where('currency', 'SAR')
                ->has('transactions', 1)
                ->where('transactions.0.id', $income->id)
                ->where('transactions.0.type', 'income')
            );
    }

    public function test_expenses_search_filters_by_description_or_vendor()
    {
        $user = $this->createUser();
        [$expenseCategory] = $this->createCategories();

        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $expenseCategory->id,
            'description' => 'اشتراك إنترنت',
            'vendor' => 'STC',
            'date' => now(),
        ]);

        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $expenseCategory->id,
            'description' => 'أدوات مكتبية',
            'vendor' => 'مكتبة جرير',
            'date' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('expenses', ['search' => 'إنترنت']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('transactions', 1)
                ->where('transactions.0.description', 'اشتراك إنترنت')
                ->where('filters.search', 'إنترنت')
            );
    }

    public function test_expenses_page_filters_by_category()
    {
        $user = $this->createUser();
        [$expenseCategory] = $this->createCategories();
        $otherCategory = Category::factory()->expense()->create();

        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $expenseCategory->id,
            'description' => 'في الفئة الأولى',
            'date' => now(),
        ]);

        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $otherCategory->id,
            'description' => 'في الفئة الثانية',
            'date' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('expenses', ['category' => $expenseCategory->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('transactions', 1)
                ->where('transactions.0.description', 'في الفئة الأولى')
                ->where('filters.category', $expenseCategory->id)
            );
    }

    public function test_expenses_page_sorts_by_amount_desc()
    {
        $user = $this->createUser();
        [$expenseCategory] = $this->createCategories();

        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $expenseCategory->id,
            'description' => 'صغير',
            'amount' => 10,
            'date' => now(),
        ]);

        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $expenseCategory->id,
            'description' => 'كبير',
            'amount' => 500,
            'date' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('expenses', ['sort' => 'amount-desc']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('transactions', 2)
                ->where('transactions.0.description', 'كبير')
                ->where('transactions.1.description', 'صغير')
                ->where('filters.sort', 'amount-desc')
            );
    }
}
