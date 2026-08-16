<?php

namespace App\Http\Controllers;

use App\Http\Requests\BudgetRequest;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $monthInput = $request->input('month', now()->format('Y-m'));
        $start = Carbon::parse($monthInput.'-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $budgets = Budget::forUser($user)
            ->where('month', $start->toDateString())
            ->with('category:id,name,color,icon')
            ->get();

        $spentByCategory = Expense::forUser($user)
            ->whereBetween('date', [$start, $end])
            ->whereIn('category_id', $budgets->pluck('category_id'))
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        $items = $budgets->map(function (Budget $budget) use ($spentByCategory): array {
            $amount = (float) $budget->amount;
            $spent = (float) ($spentByCategory[$budget->category_id] ?? 0);

            return [
                'id' => $budget->id,
                'category' => $budget->category
                    ? [
                        'id' => $budget->category->id,
                        'name' => $budget->category->name,
                        'color' => $budget->category->color,
                        'icon' => $budget->category->icon,
                    ]
                    : null,
                'amount' => $amount,
                'spent' => $spent,
                'remaining' => max($amount - $spent, 0),
                'percentage' => $amount > 0 ? round(($spent / $amount) * 100, 1) : 0.0,
                'status' => $amount > 0 && $spent > $amount
                    ? 'over'
                    : ($amount > 0 && $spent >= $amount * 0.8 ? 'warning' : 'ok'),
                'currency' => $budget->currency,
            ];
        })->values();

        $availableCategories = Category::forUser($user)
            ->where('type', 'expense')
            ->parents()
            ->ordered()
            ->whereNotIn('id', $budgets->pluck('category_id'))
            ->get()
            ->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'color' => $category->color,
                'icon' => $category->icon,
            ])
            ->values();

        return Inertia::render('Budgets', [
            'budgets' => $items,
            'available_categories' => $availableCategories,
            'month' => $monthInput,
            'currency' => 'SAR',
        ]);
    }

    public function store(BudgetRequest $request): RedirectResponse
    {
        $user = $request->user();

        $start = Carbon::parse($request->validated('month').'-01')->startOfMonth();

        $exists = Budget::forUser($user)
            ->where('category_id', $request->validated('category_id'))
            ->where('month', $start->toDateString())
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'category_id' => __('هذه الفئة لديها ميزانية بالفعل لهذا الشهر.'),
            ]);
        }

        Budget::create([
            'user_id' => $user->id,
            'category_id' => $request->validated('category_id'),
            'amount' => $request->validated('amount'),
            'month' => $start,
            'currency' => 'SAR',
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('تم إنشاء الميزانية.')]);

        return back();
    }

    public function update(BudgetRequest $request, Budget $budget): RedirectResponse
    {
        $user = $request->user();

        $budget = Budget::forUser($user)->findOrFail($budget->id);

        $budget->update([
            'amount' => $request->validated('amount'),
            'month' => Carbon::parse($request->validated('month').'-01')->startOfMonth(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('تم تحديث الميزانية.')]);

        return back();
    }

    public function destroy(Request $request, Budget $budget): RedirectResponse
    {
        $user = $request->user();

        Budget::forUser($user)->findOrFail($budget->id)->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('تم حذف الميزانية.')]);

        return back();
    }
}
