<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use App\Models\Expense;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $categories = Category::forUser($user)
            ->withCount([
                'expenses as expenses_count' => fn (Builder $query) => $query->where('user_id', $user->id),
            ])
            ->orderBy('type')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'type' => $category->type,
                'icon' => $category->icon,
                'color' => $category->color,
                'is_system' => $category->is_system,
                'expenses_count' => $category->expenses_count,
            ])
            ->values();

        return Inertia::render('Categories', [
            'categories' => $categories,
            'currency' => 'SAR',
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $user = $request->user();

        Category::create([
            'user_id' => $user->id,
            'name' => $request->validated('name'),
            'type' => $request->validated('type'),
            'icon' => $request->validated('icon'),
            'color' => $request->validated('color'),
            'description' => $request->validated('description'),
            'is_system' => false,
            'sort_order' => 0,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category created.')]);

        return back();
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $user = $request->user();

        $category = Category::forUser($user)->findOrFail($category->id);

        if ($category->is_system) {
            abort(403);
        }

        if ($request->validated('type') !== $category->type) {
            $hasExpenses = Expense::forUser($user)
                ->where('category_id', $category->id)
                ->exists();

            if ($hasExpenses) {
                throw ValidationException::withMessages([
                    'type' => __('لا يمكن تغيير نوع فئة لديها معاملات.'),
                ]);
            }
        }

        $category->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category updated.')]);

        return back();
    }

    public function destroy(Request $request, Category $category): RedirectResponse
    {
        $user = $request->user();

        $category = Category::forUser($user)->findOrFail($category->id);

        if ($category->is_system) {
            abort(403);
        }

        $fallback = Category::forUser($user)
            ->where('type', $category->type)
            ->where('name', 'أخرى')
            ->where('is_system', true)
            ->whereNull('user_id')
            ->first();

        Expense::forUser($user)
            ->where('category_id', $category->id)
            ->update(['category_id' => $fallback?->id]);

        $category->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category deleted.')]);

        return back();
    }
}
