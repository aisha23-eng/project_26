<?php

namespace App\Ai\Tools;

use App\Ai\Tools\Concerns\InteractsWithTransactions;
use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ListTransactions implements Tool
{
    use InteractsWithTransactions;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'List financial transactions for the current user with optional filters (date range, type, category, amount, search, sort, limit). Use this tool whenever you need to look up, inspect, count, or sum the user\'s transactions before answering a question or before updating/deleting specific records. Do not guess transaction IDs or amounts; always retrieve them through this tool. Returned entries contain id, date, type, category, amount, and description, plus total_count and sum_amount.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $limit = min((int) ($request['limit'] ?? 25), 100);

        $query = Expense::forUser($this->user)->with('category');

        if ($from = $request['date_from'] ?? null) {
            $query->where('date', '>=', $from);
        }

        if ($to = $request['date_to'] ?? null) {
            $query->where('date', '<=', $to);
        }

        if ($type = $request['type'] ?? null) {
            $query->whereHas('category', fn (Builder $q) => $q->where('type', $type));
        }

        if ($category = $request['category'] ?? null) {
            $names = is_array($category) ? $category : [$category];
            $ids = Category::forUser($this->user)->whereIn('name', $names)->pluck('id');
            $query->whereIn('category_id', $ids);
        }

        if ($min = $request['min_amount'] ?? null) {
            $query->where('amount', '>=', (float) $min);
        }

        if ($max = $request['max_amount'] ?? null) {
            $query->where('amount', '<=', (float) $max);
        }

        if ($search = $request['search'] ?? null) {
            $pattern = '%'.addcslashes($search, '\\%_').'%';
            $query->where(function (Builder $q) use ($pattern) {
                $q->whereRaw("description LIKE ? ESCAPE '\\'", [$pattern])
                    ->orWhereRaw("vendor LIKE ? ESCAPE '\\'", [$pattern]);
            });
        }

        $sort = $request['sort'] ?? 'date_desc';
        match ($sort) {
            'date_asc' => $query->orderBy('date')->orderBy('id'),
            'amount_desc' => $query->orderByDesc('amount')->orderByDesc('date'),
            'amount_asc' => $query->orderBy('amount')->orderBy('date'),
            default => $query->orderByDesc('date')->orderByDesc('id'),
        };

        $totalCount = (clone $query)->count();
        $sumAmount = (float) (clone $query)->sum('amount');

        $rows = $query->limit($limit)->get()->map(fn (Expense $expense) => $this->shapeTransaction($expense))->values();

        $truncated = $totalCount > $limit;

        return json_encode([
            'ok' => true,
            'summary' => sprintf('Returned %d of %d matching transactions (total amount: %.2f).', $rows->count(), $totalCount, $sumAmount).($truncated ? ' More results exist; narrow the filters or paginate to see them.' : ''),
            'data' => [
                'transactions' => $rows,
                'total_count' => $totalCount,
                'sum_amount' => $sumAmount,
                'truncated' => $truncated,
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'date_from' => $schema->string()->format('date')->nullable()->description('Start date (Y-m-d)'),
            'date_to' => $schema->string()->format('date')->nullable()->description('End date (Y-m-d)'),
            'type' => $schema->string()->enum(['expense', 'income'])->nullable()->description('Transaction type'),
            'category' => $schema->union(['string', 'array'])->nullable()->description('Category name or array of category names'),
            'min_amount' => $schema->number()->min(0)->nullable()->description('Minimum amount'),
            'max_amount' => $schema->number()->min(0)->nullable()->description('Maximum amount'),
            'search' => $schema->string()->nullable()->description('Search in description or vendor'),
            'sort' => $schema->string()->enum(['date_desc', 'date_asc', 'amount_desc', 'amount_asc'])->default('date_desc'),
            'limit' => $schema->integer()->min(1)->max(100)->default(25),
        ];
    }
}
