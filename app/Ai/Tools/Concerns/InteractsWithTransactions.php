<?php

namespace App\Ai\Tools\Concerns;

use App\Http\Requests\TransactionRequest;
use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

trait InteractsWithTransactions
{
    protected User $user;

    /**
     * Reuse the exact validation rules from the application's form request.
     *
     * @return array<string, mixed>
     */
    protected function transactionRules(): array
    {
        return (new TransactionRequest)->rules();
    }

    /**
     * Validate a single transaction payload against the shared rules.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function validateTransaction(array $data): array
    {
        return Validator::make($data, $this->transactionRules())->validate();
    }

    /**
     * Resolve the category for the given payload, mirroring the controller.
     *
     * @param  array<string, mixed>  $data
     */
    protected function resolveCategoryId(array $data, ?Expense $transaction = null): ?int
    {
        $type = $data['type'] ?? ($transaction?->category?->type ?? 'expense');

        $categoryId = $data['category_id'] ?? null;

        if ($categoryId === null && isset($data['category']) && is_string($data['category'])) {
            $categoryId = Category::forUser($this->user)
                ->where('type', $type)
                ->where('name', $data['category'])
                ->value('id');
        }

        if ($categoryId !== null) {
            $this->authorizeCategory((int) $categoryId, $type);

            return (int) $categoryId;
        }

        if ($transaction !== null && $transaction->category?->type === $type) {
            return $transaction->category_id;
        }

        return Category::forUser($this->user)
            ->where('type', $type)
            ->where('name', 'أخرى')
            ->where('is_system', true)
            ->whereNull('user_id')
            ->value('id');
    }

    /**
     * Ensure the given category belongs to the user and matches the type.
     */
    protected function authorizeCategory(int $categoryId, string $type): void
    {
        $category = Category::forUser($this->user)->find($categoryId);

        if ($category === null || $category->type !== $type) {
            throw ValidationException::withMessages([
                'category_id' => __('The selected category does not match the transaction type.'),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function shapeTransaction(Expense $expense): array
    {
        return [
            'id' => $expense->id,
            'date' => $expense->date->toDateString(),
            'type' => $expense->category?->type,
            'category' => $expense->category?->name,
            'amount' => (float) $expense->amount,
            'description' => $expense->description,
            'vendor' => $expense->vendor,
        ];
    }

    /**
     * Determine which fields the model actually sent for a partial update.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function presentFields(array $data): array
    {
        return Arr::only($data, ['description', 'amount', 'date', 'vendor', 'category_id', 'category', 'type']);
    }
}
