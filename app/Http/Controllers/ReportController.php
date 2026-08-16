<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    private const MONTH_LABELS = [
        'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو',
        'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر',
    ];

    public function index(Request $request): Response
    {
        $user = $request->user();

        $months = collect(range(5, 0))
            ->map(fn (int $offset) => now()->copy()->startOfMonth()->subMonths($offset))
            ->values();

        $monthlySeries = $months->map(function (CarbonInterface $month) use ($user) {
            return [
                'label' => self::MONTH_LABELS[$month->month - 1],
                'expenses' => $this->sumBetween($user, 'expense', $month),
                'income' => $this->sumBetween($user, 'income', $month),
            ];
        })->values();

        $totalExpenses = $this->sumAll($user, 'expense');
        $totalIncome = $this->sumAll($user, 'income');

        $avgMonthlyExpenses = $months->isNotEmpty()
            ? round($monthlySeries->sum('expenses') / $months->count(), 2)
            : 0.0;

        return Inertia::render('Reports', [
            'monthly_series' => $monthlySeries->all(),
            'category_breakdown' => $this->breakdown($user, 'expense'),
            'income_breakdown' => $this->breakdown($user, 'income'),
            'totals' => [
                'total_expenses' => $totalExpenses,
                'total_income' => $totalIncome,
                'net' => $totalIncome - $totalExpenses,
                'avg_monthly_expenses' => $avgMonthlyExpenses,
            ],
            'currency' => 'SAR',
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $user = $request->user();

        $transactions = Expense::forUser($user)
            ->with('category')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get()
            ->map(function (Expense $expense): array {
                return [
                    'id' => $expense->id,
                    'type' => $expense->category?->type ?? 'expense',
                    'date' => $expense->date->toDateString(),
                    'description' => $expense->description,
                    'vendor' => $expense->vendor ?? '',
                    'category' => $expense->category?->name ?? '',
                    'amount' => number_format((float) $expense->amount, 2, '.', ''),
                    'currency' => $expense->currency,
                ];
            });

        $headers = ['id', 'type', 'date', 'description', 'vendor', 'category', 'amount', 'currency'];
        $filename = 'reports-'.now()->toDateString().'.csv';

        return response()->streamDownload(function () use ($transactions, $headers): void {
            $handle = fopen('php://output', 'wb');

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers);

            foreach ($transactions as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function sumAll(User $user, string $type): float
    {
        return (float) Expense::forUser($user)
            ->whereHas('category', fn (Builder $q) => $q->where('type', $type))
            ->sum('amount');
    }

    private function sumBetween(User $user, string $type, CarbonInterface $month): float
    {
        return (float) Expense::forUser($user)
            ->whereBetween('date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->whereHas('category', fn (Builder $q) => $q->where('type', $type))
            ->sum('amount');
    }

    /**
     * @return array<int, array{name: string, color: ?string, amount: float, percentage: float}>
     */
    private function breakdown(User $user, string $type): array
    {
        $rows = Expense::forUser($user)
            ->join('categories', 'categories.id', '=', 'expenses.category_id')
            ->where('categories.type', $type)
            ->whereNull('categories.deleted_at')
            ->selectRaw('categories.name as name, categories.color as color, SUM(expenses.amount) as amount')
            ->groupBy('categories.id', 'categories.name', 'categories.color')
            ->orderByDesc('amount')
            ->get();

        $total = (float) $rows->sum('amount');

        return $rows
            ->map(fn ($row) => [
                'name' => $row->name,
                'color' => $row->color,
                'amount' => (float) $row->amount,
                'percentage' => $total > 0 ? round(((float) $row->amount / $total) * 100, 2) : 0.0,
            ])
            ->values()
            ->all();
    }
}
