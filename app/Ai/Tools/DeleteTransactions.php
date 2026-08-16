<?php

namespace App\Ai\Tools;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class DeleteTransactions implements Tool
{
    public function __construct(private readonly User $user)
    {
        //
    }

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Delete one or more existing transactions for the current user by explicit ids (up to 50 per call). There is no "delete all" operation: you must always pass concrete transaction ids. IDs are validated against the current user, so any id the user does not own is reported in not_found and ignored. Call ListTransactions first to resolve the exact ids the user is referring to before deleting. Returns deleted_count, deleted_ids, and not_found.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $ids = $request['ids'] ?? [];

        if (! is_array($ids) || $ids === []) {
            return json_encode(['ok' => false, 'summary' => 'You must provide at least one transaction id to delete.', 'data' => []]);
        }

        $ids = array_values(array_unique(array_map('intval', $ids)));

        if (count($ids) > 50) {
            return json_encode(['ok' => false, 'summary' => 'A maximum of 50 transactions may be deleted in a single call.', 'data' => []]);
        }

        try {
            $result = DB::transaction(function () use ($ids) {
                $owned = Expense::forUser($this->user)
                    ->whereIn('id', $ids)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                $notFound = array_values(array_diff($ids, $owned));

                if ($owned !== []) {
                    Expense::forUser($this->user)->whereIn('id', $owned)->delete();
                }

                return [
                    'deleted_ids' => $owned,
                    'not_found' => $notFound,
                ];
            });
        } catch (\Throwable $e) {
            return json_encode(['ok' => false, 'summary' => 'Failed to delete transactions: '.$e->getMessage().'.', 'data' => []]);
        }

        return json_encode([
            'ok' => true,
            'summary' => sprintf('Deleted %d transaction(s).', count($result['deleted_ids'])),
            'data' => [
                'deleted_count' => count($result['deleted_ids']),
                'deleted_ids' => $result['deleted_ids'],
                'not_found' => $result['not_found'],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'ids' => $schema->array()->min(1)->max(50)->items($schema->integer()->description('Transaction id'))->required(),
        ];
    }
}
