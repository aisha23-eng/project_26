<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $now = now();
        $previousMonth = $now->copy()->subMonth();

        $totalIncome = $this->sumByType($user, 'income');
        $totalExpenses = $this->sumByType($user, 'expense');
        $monthlyIncome = $this->sumByType($user, 'income', $now);
        $monthlyExpenses = $this->sumByType($user, 'expense', $now);
        $previousMonthIncome = $this->sumByType($user, 'income', $previousMonth);
        $previousMonthExpenses = $this->sumByType($user, 'expense', $previousMonth);

        $recentTransactions = Expense::forUser($user)
            ->with('category')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(6)
            ->get()
            ->map(fn (Expense $expense) => $this->shapeTransaction($expense))
            ->values();

        $thisMonthExpenses = Expense::forUser($user)
            ->whereHas('category', fn (Builder $q) => $q->where('type', 'expense'))
            ->whereBetween('date', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()]);

        $largestTransaction = $thisMonthExpenses
            ->with('category')
            ->orderByDesc('amount')
            ->first();

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_balance' => $totalIncome - $totalExpenses,
                'total_income' => $totalIncome,
                'total_expenses' => $totalExpenses,
                'monthly_income' => $monthlyIncome,
                'monthly_expenses' => $monthlyExpenses,
                'net_monthly' => $monthlyIncome - $monthlyExpenses,
                'previous_month_income' => $previousMonthIncome,
                'previous_month_expenses' => $previousMonthExpenses,
                'previous_month_net' => $previousMonthIncome - $previousMonthExpenses,
                'currency' => 'SAR',
            ],
            'quick_stats' => [
                'average_daily_expense' => round($monthlyExpenses / max((int) $now->day, 1), 2),
                'expense_count' => (clone $thisMonthExpenses)->count(),
                'largest_transaction' => $largestTransaction !== null ? $this->shapeTransaction($largestTransaction) : null,
            ],
            'recent_transactions' => $recentTransactions,
            'category_breakdown' => $this->categoryBreakdown($user, $now),
            'currency' => 'SAR',
        ]);
    }

    private function sumByType(User $user, string $type, ?CarbonInterface $month = null): float
    {
        $query = Expense::forUser($user)
            ->whereHas('category', fn (Builder $q) => $q->where('type', $type));

        if ($month !== null) {
            $query->whereBetween('date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()]);
        }

        return (float) $query->sum('amount');
    }

    /**
     * @return array<int, array{name: string, color: ?string, amount: float, percentage: float}>
     */
    private function categoryBreakdown(User $user, ?CarbonInterface $month = null): array
    {
        $query = Expense::forUser($user)
            ->join('categories', 'categories.id', '=', 'expenses.category_id')
            ->where('categories.type', 'expense')
            ->whereNull('categories.deleted_at')
            ->selectRaw('categories.name as name, categories.color as color, SUM(expenses.amount) as amount')
            ->groupBy('categories.id', 'categories.name', 'categories.color');

        if ($month !== null) {
            $query->whereBetween('expenses.date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()]);
        }

        $rows = $query->orderByDesc('amount')->get();
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
