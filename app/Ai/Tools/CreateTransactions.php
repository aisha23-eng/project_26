<?php

namespace App\Ai\Tools;

use App\Ai\Tools\Concerns\InteractsWithTransactions;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class CreateTransactions implements Tool
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
        return 'Create one or more financial transactions for the current user. Accepts an array of transactions (up to 50 per call); pass multiple items in a single call instead of calling once per item. Each transaction requires a description, amount, date, and type (expense|income). Optionally accept category_id or category name from the known category list. The whole batch is committed atomically. Returns the created IDs. If any item is invalid, nothing is created.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $items = $request['transactions'] ?? [];

        if (! is_array($items) || $items === []) {
            return json_encode(['ok' => false, 'summary' => 'You must provide at least one transaction.', 'data' => []]);
        }

        if (count($items) > 50) {
            return json_encode(['ok' => false, 'summary' => 'A maximum of 50 transactions may be created in a single call.', 'data' => []]);
        }

        $validated = [];

        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                return json_encode(['ok' => false, 'summary' => "Item at index {$index} is not a valid object.", 'data' => []]);
            }

            try {
                $data = $this->validateTransaction($item);
            } catch (\Throwable $e) {
                return json_encode(['ok' => false, 'summary' => "Item at index {$index} is invalid: ".implode('; ', $this->flattenErrors($e)).'. Nothing was created.', 'data' => []]);
            }

            if (isset($item['category'])) {
                $data['category'] = $item['category'];
            }

            $validated[] = $data;
        }

        try {
            $created = DB::transaction(function () use ($validated) {
                $ids = [];

                foreach ($validated as $data) {
                    $categoryId = $this->resolveCategoryId($data);

                    $expense = Expense::create([
                        'user_id' => $this->user->id,
                        'category_id' => $categoryId,
                        'amount' => $data['amount'],
                        'currency' => 'SAR',
                        'description' => $data['description'],
                        'vendor' => $data['vendor'] ?? null,
                        'date' => $data['date'],
                    ]);

                    $ids[] = $expense->id;
                }

                return $ids;
            });
        } catch (\Throwable $e) {
            return json_encode(['ok' => false, 'summary' => 'Failed to create transactions: '.$e->getMessage().'. Nothing was created.', 'data' => []]);
        }

        return json_encode([
            'ok' => true,
            'summary' => sprintf('Created %d transaction(s).', count($created)),
            'data' => ['ids' => $created],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @return array<int, string>
     */
    private function flattenErrors(\Throwable $e): array
    {
        if ($e instanceof ValidationException) {
            $messages = [];

            foreach ($e->errors() as $field => $fieldMessages) {
                foreach ($fieldMessages as $message) {
                    $messages[] = $field.': '.$message;
                }
            }

            return $messages;
        }

        return [$e->getMessage()];
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'transactions' => $schema->array()
                ->min(1)
                ->max(50)
                ->items($schema->object([
                    'description' => $schema->string()->max(255)->required()->description('Short description of the transaction'),
                    'amount' => $schema->number()->min(0.01)->required()->description('Amount in SAR'),
                    'date' => $schema->string()->format('date')->required()->description('Transaction date (Y-m-d)'),
                    'type' => $schema->string()->enum(['expense', 'income'])->required()->description('Whether this is an expense or income'),
                    'vendor' => $schema->string()->max(255)->nullable()->description('Optional vendor/merchant'),
                    'category_id' => $schema->integer()->nullable()->description('Optional known category id'),
                    'category' => $schema->string()->nullable()->description('Optional known category name'),
                ])),
        ];
    }
}
