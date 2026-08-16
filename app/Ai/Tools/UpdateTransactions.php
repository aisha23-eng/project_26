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

class UpdateTransactions implements Tool
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
        return 'Update one or more existing transactions for the current user. Accepts an array of updates (up to 50 per call). Each update provides the transaction id plus only the fields to change (description, amount, date, type, category, vendor). Unspecified fields are left unchanged. IDs are validated against the current user, so any id the user does not own is reported in not_found and ignored. Returns updated[] and not_found[]. Call ListTransactions first whenever the user describes a transaction vaguely, so you use the correct id.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $updates = $request['updates'] ?? [];

        if (! is_array($updates) || $updates === []) {
            return json_encode(['ok' => false, 'summary' => 'You must provide at least one update.', 'data' => []]);
        }

        if (count($updates) > 50) {
            return json_encode(['ok' => false, 'summary' => 'A maximum of 50 updates may be applied in a single call.', 'data' => []]);
        }

        $updated = [];
        $notFound = [];

        try {
            DB::transaction(function () use ($updates, &$updated, &$notFound) {
                foreach ($updates as $index => $update) {
                    if (! is_array($update) || ! isset($update['id'])) {
                        $notFound[] = ['index' => $index, 'reason' => 'Missing id'];

                        continue;
                    }

                    $id = (int) $update['id'];

                    $transaction = Expense::forUser($this->user)->find($id);

                    if ($transaction === null) {
                        $notFound[] = ['id' => $id, 'reason' => 'not_found'];

                        continue;
                    }

                    $data = $this->presentFields($update);

                    if ($data === []) {
                        $notFound[] = ['id' => $id, 'reason' => 'No updatable fields provided'];

                        continue;
                    }

                    try {
                        $validated = Validator($data, $this->partialRules($data))->validate();
                    } catch (ValidationException $e) {
                        $notFound[] = ['id' => $id, 'reason' => $this->flattenErrors($e)];

                        continue;
                    }

                    if (isset($data['category'])) {
                        $validated['category'] = $data['category'];
                    }

                    $categoryId = $transaction->category_id;

                    if (array_key_exists('category_id', $validated) || isset($validated['category'])) {
                        $categoryId = $this->resolveCategoryId($validated, $transaction);
                    } elseif (array_key_exists('type', $validated) && $transaction->category?->type !== $validated['type']) {
                        $categoryId = $this->resolveCategoryId($validated, $transaction);
                    }

                    $transaction->update([
                        'category_id' => $categoryId,
                        'amount' => $validated['amount'] ?? $transaction->amount,
                        'description' => $validated['description'] ?? $transaction->description,
                        'vendor' => array_key_exists('vendor', $validated) ? $validated['vendor'] : $transaction->vendor,
                        'date' => $validated['date'] ?? $transaction->date,
                    ]);

                    $updated[] = $this->shapeTransaction($transaction->fresh());
                }
            });
        } catch (\Throwable $e) {
            return json_encode(['ok' => false, 'summary' => 'Failed to apply updates: '.$e->getMessage().'.', 'data' => []]);
        }

        return json_encode([
            'ok' => true,
            'summary' => sprintf('Updated %d transaction(s); %d not found or skipped.', count($updated), count($notFound)),
            'data' => ['updated' => $updated, 'not_found' => $notFound],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Build rules for a partial update, reusing the shared rules.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function partialRules(array $data): array
    {
        $all = $this->transactionRules();

        $present = [];

        foreach ($data as $key => $value) {
            if ($key === 'category') {
                continue;
            }

            if (array_key_exists($key, $all)) {
                $present[$key] = $all[$key];
            }
        }

        return $present;
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
            'updates' => $schema->array()
                ->min(1)
                ->max(50)
                ->items($schema->object([
                    'id' => $schema->integer()->required()->description('Id of an existing transaction'),
                    'description' => $schema->string()->max(255)->nullable(),
                    'amount' => $schema->number()->min(0.01)->nullable(),
                    'date' => $schema->string()->format('date')->nullable(),
                    'type' => $schema->string()->enum(['expense', 'income'])->nullable(),
                    'category' => $schema->string()->nullable()->description('Optional known category name'),
                    'category_id' => $schema->integer()->nullable(),
                    'vendor' => $schema->string()->max(255)->nullable(),
                ])),
        ];
    }
}
