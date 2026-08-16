<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransactionRequest;
use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function expenses(Request $request): Response
    {
        return $this->index($request, 'expense', 'Expenses');
    }

    public function income(Request $request): Response
    {
        return $this->index($request, 'income', 'Income');
    }

    public function store(TransactionRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        Expense::create([
            'user_id' => $user->id,
            'category_id' => $this->resolveCategoryId($user, $data),
            'amount' => $data['amount'],
            'currency' => 'SAR',
            'description' => $data['description'],
            'vendor' => $data['vendor'] ?? null,
            'date' => $data['date'],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transaction created.')]);

        return back();
    }

    public function update(TransactionRequest $request, Expense $transaction): RedirectResponse
    {
        $user = $request->user();
        $transaction = Expense::forUser($user)->findOrFail($transaction->id);

        $data = $request->validated();
        $categoryId = $data['category_id'] ?? null;

        if ($categoryId === null) {
            $categoryId = $this->resolveCategoryId($user, $data, $transaction);
        } else {
            $this->authorizeCategory($user, $categoryId, $data['type']);
        }

        $transaction->update([
            'category_id' => $categoryId,
            'amount' => $data['amount'],
            'currency' => 'SAR',
            'description' => $data['description'],
            'vendor' => array_key_exists('vendor', $data) ? $data['vendor'] : $transaction->vendor,
            'date' => $data['date'],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transaction updated.')]);

        return back();
    }

    public function destroy(Request $request, Expense $transaction): RedirectResponse
    {
        $user = $request->user();

        Expense::forUser($user)->findOrFail($transaction->id)->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transaction deleted.')]);

        return back();
    }

    private function index(Request $request, string $type, string $component): Response
    {
        $user = $request->user();
        $search = $request->query('search');
        $category = $request->query('category');
        $sort = $request->query('sort', 'date-desc');

        $query = Expense::forUser($user)
            ->with('category')
            ->whereHas('category', fn (Builder $q) => $q->where('type', $type));

        match ($sort) {
            'date-asc' => $query->orderBy('date')->orderBy('id'),
            'amount-desc' => $query->orderByDesc('amount')->orderByDesc('date'),
            'amount-asc' => $query->orderBy('amount')->orderBy('date'),
            default => $query->orderByDesc('date')->orderByDesc('id'),
        };

        if ($category !== null && $category !== '') {
            $query->where('category_id', (int) $category);
        }

        if ($search !== null && $search !== '') {
            $pattern = '%'.addcslashes($search, '\\%_').'%';

            $query->where(function (Builder $q) use ($pattern) {
                $q->whereRaw("description LIKE ? ESCAPE '\\'", [$pattern])
                    ->orWhereRaw("vendor LIKE ? ESCAPE '\\'", [$pattern]);
            });
        }

        $transactions = $query->get()
            ->map(fn (Expense $expense) => $this->shapeTransaction($expense))
            ->values();

        $categories = Category::forUser($user)
            ->where('type', $type)
            ->ordered()
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'color' => $category->color,
                'icon' => $category->icon,
            ])
            ->values();

        return Inertia::render($component, [
            'transactions' => $transactions,
            'categories' => $categories,
            'currency' => 'SAR',
            'filters' => [
                'search' => $search,
                'category' => $category !== '' && $category !== null ? (int) $category : null,
                'sort' => $sort,
            ],
        ]);
    }

    /**
     * Resolve the category for the given validated payload.
     *
     * @param  array<string, mixed>  $data
     */
    private function resolveCategoryId(User $user, array $data, ?Expense $transaction = null): ?int
    {
        $categoryId = $data['category_id'] ?? null;

        if ($categoryId !== null) {
            $this->authorizeCategory($user, $categoryId, $data['type']);

            return $categoryId;
        }

        if ($transaction !== null && $transaction->category?->type === $data['type']) {
            return $transaction->category_id;
        }

        $fallback = Category::forUser($user)
            ->where('type', $data['type'])
            ->where('name', 'أخرى')
            ->where('is_system', true)
            ->whereNull('user_id')
            ->first();

        return $fallback?->id;
    }

    private function authorizeCategory(User $user, int $categoryId, string $type): void
    {
        $category = Category::forUser($user)->find($categoryId);

        if ($category === null || $category->type !== $type) {
            throw ValidationException::withMessages([
                'category_id' => __('The selected category does not match the transaction type.'),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function shapeTransaction(Expense $expense): array
    {
        return [
            'id' => $expense->id,
            'description' => $expense->description,
            'vendor' => $expense->vendor,
            'amount' => (float) $expense->amount,
            'date' => $expense->date->toDateString(),
            'type' => $expense->category?->type,
            'category' => $expense->category !== null ? [
                'id' => $expense->category->id,
                'name' => $expense->category->name,
                'color' => $expense->category->color,
                'icon' => $expense->category->icon,
            ] : null,
        ];
    }
}
