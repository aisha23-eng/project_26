<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        return User::factory()->create();
    }

    private function systemCategory(string $name, string $type): Category
    {
        return Category::where('name', $name)
            ->where('type', $type)
            ->where('is_system', true)
            ->firstOrFail();
    }

    public function test_guests_are_redirected_to_the_login_page()
    {
        $this->get(route('categories'))->assertRedirect(route('login'));
    }

    public function test_index_lists_categories_with_expenses_count()
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();

        $custom = Category::factory()->expense()->create([
            'user_id' => $user->id,
            'name' => 'فئة مخصصة',
        ]);

        Expense::factory()->create(['user_id' => $user->id, 'category_id' => $custom->id]);
        Expense::factory()->create(['user_id' => $user->id, 'category_id' => $custom->id]);
        Expense::factory()->create(['user_id' => $otherUser->id, 'category_id' => $custom->id]);

        $response = $this->actingAs($user)->get(route('categories'));
        $response->assertOk();

        $page = $response->viewData('page');
        $this->assertSame('Categories', $page['component']);
        $this->assertSame('SAR', $page['props']['currency']);

        $categories = collect($page['props']['categories']);
        $this->assertCount(20, $categories);

        $customRow = $categories->firstWhere('name', 'فئة مخصصة');
        $this->assertNotNull($customRow);
        $this->assertSame('expense', $customRow['type']);
        $this->assertFalse($customRow['is_system']);
        $this->assertSame(2, $customRow['expenses_count']);

        $systemRow = $categories->firstWhere('is_system', true);
        $this->assertNotNull($systemRow);
        $this->assertTrue($systemRow['is_system']);
        $this->assertSame(0, $systemRow['expenses_count']);
    }

    public function test_user_can_store_a_custom_category()
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)
            ->from(route('categories'))
            ->post(route('categories.store'), [
                'name' => 'فئة جديدة',
                'type' => 'expense',
                'icon' => 'star',
                'color' => '#ff0000',
                'description' => 'وصف الفئة',
            ]);

        $response
            ->assertRedirect(route('categories', absolute: false))
            ->assertSessionHas('inertia.flash_data', [
                'toast' => ['type' => 'success', 'message' => 'Category created.'],
            ]);

        $this->assertDatabaseHas('categories', [
            'user_id' => $user->id,
            'name' => 'فئة جديدة',
            'type' => 'expense',
            'icon' => 'star',
            'color' => '#ff0000',
            'is_system' => false,
            'sort_order' => 0,
        ]);
    }

    public function test_user_can_update_their_custom_category()
    {
        $user = $this->createUser();

        $category = Category::factory()->create([
            'user_id' => $user->id,
            'name' => 'قديم',
            'type' => 'expense',
        ]);

        $response = $this->actingAs($user)
            ->from(route('categories'))
            ->patch(route('categories.update', $category), [
                'name' => 'جديد',
                'type' => 'expense',
                'icon' => 'zap',
                'color' => '#0000ff',
                'description' => null,
            ]);

        $response->assertRedirect(route('categories', absolute: false));

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'جديد',
            'icon' => 'zap',
            'color' => '#0000ff',
        ]);
    }

    public function test_system_categories_cannot_be_updated()
    {
        $user = $this->createUser();
        $system = $this->systemCategory('أخرى', 'expense');

        $this->actingAs($user)
            ->patch(route('categories.update', $system), [
                'name' => 'محاولة تعديل',
                'type' => 'expense',
            ])
            ->assertForbidden();
    }

    public function test_system_categories_cannot_be_deleted()
    {
        $user = $this->createUser();
        $system = $this->systemCategory('أخرى', 'expense');

        $this->actingAs($user)
            ->delete(route('categories.destroy', $system))
            ->assertForbidden();

        $this->assertDatabaseHas('categories', ['id' => $system->id]);
    }

    public function test_deleting_a_custom_category_reassigns_its_expenses_to_other()
    {
        $user = $this->createUser();

        $category = Category::factory()->expense()->create([
            'user_id' => $user->id,
            'name' => 'فئة للحذف',
        ]);

        $expense = Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($user)
            ->from(route('categories'))
            ->delete(route('categories.destroy', $category));

        $response->assertRedirect(route('categories', absolute: false));

        $this->assertSoftDeleted('categories', ['id' => $category->id]);
        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'category_id' => $this->systemCategory('أخرى', 'expense')->id,
        ]);
    }

    public function test_deleted_category_expenses_stay_visible_on_lists_and_dashboard_totals()
    {
        $user = $this->createUser();

        $category = Category::factory()->expense()->create([
            'user_id' => $user->id,
            'name' => 'فئة للحذف',
        ]);

        $expense = Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'description' => 'مصروف محفوظ',
            'amount' => 250,
            'date' => now(),
        ]);

        $this->actingAs($user)->delete(route('categories.destroy', $category));

        $other = $this->systemCategory('أخرى', 'expense');

        $this->actingAs($user)
            ->get(route('expenses'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Expenses')
                ->has('transactions', 1)
                ->where('transactions.0.id', $expense->id)
                ->where('transactions.0.type', 'expense')
                ->where('transactions.0.category.id', $other->id)
            );

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.total_expenses', 250)
                ->where('stats.monthly_expenses', 250)
            );
    }

    public function test_color_validation_rejects_malformed_or_overlong_hex_colors()
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->from(route('categories'))
            ->post(route('categories.store'), [
                'name' => 'لون طويل',
                'type' => 'expense',
                'color' => '#ff00001',
            ])
            ->assertSessionHasErrors('color');

        $this->actingAs($user)
            ->from(route('categories'))
            ->post(route('categories.store'), [
                'name' => 'لون غير صالح',
                'type' => 'expense',
                'color' => 'red',
            ])
            ->assertSessionHasErrors('color');

        $this->assertDatabaseCount('categories', 19);
    }

    public function test_duplicate_category_name_for_same_user_and_type_is_rejected()
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->post(route('categories.store'), [
                'name' => 'فئة مكررة',
                'type' => 'expense',
            ])
            ->assertRedirect();

        $this->actingAs($user)
            ->from(route('categories'))
            ->post(route('categories.store'), [
                'name' => 'فئة مكررة',
                'type' => 'expense',
            ])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('categories', 20);
    }

    public function test_same_category_name_with_different_type_is_allowed()
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->post(route('categories.store'), [
                'name' => 'نفس الاسم',
                'type' => 'expense',
            ])
            ->assertRedirect();

        $this->actingAs($user)
            ->post(route('categories.store'), [
                'name' => 'نفس الاسم',
                'type' => 'income',
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('categories', 21);
    }

    public function test_same_category_name_for_different_users_is_allowed()
    {
        $first = $this->createUser();
        $second = $this->createUser();

        $this->actingAs($first)
            ->post(route('categories.store'), [
                'name' => 'اسم مشترك',
                'type' => 'expense',
            ])
            ->assertRedirect();

        $this->actingAs($second)
            ->post(route('categories.store'), [
                'name' => 'اسم مشترك',
                'type' => 'expense',
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('categories', 21);
    }

    public function test_cannot_change_type_of_a_category_that_has_expenses()
    {
        $user = $this->createUser();

        $category = Category::factory()->expense()->create([
            'user_id' => $user->id,
            'name' => 'فئة ذات معاملات',
        ]);

        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $this->actingAs($user)
            ->from(route('categories'))
            ->patch(route('categories.update', $category), [
                'name' => 'فئة ذات معاملات',
                'type' => 'income',
            ])
            ->assertSessionHasErrors('type');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'type' => 'expense',
        ]);
    }

    public function test_can_change_type_of_a_category_without_expenses()
    {
        $user = $this->createUser();

        $category = Category::factory()->expense()->create([
            'user_id' => $user->id,
            'name' => 'فئة فارغة',
        ]);

        $this->actingAs($user)
            ->from(route('categories'))
            ->patch(route('categories.update', $category), [
                'name' => 'فئة فارغة',
                'type' => 'income',
            ])
            ->assertRedirect(route('categories', absolute: false));

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'type' => 'income',
        ]);
    }
}
