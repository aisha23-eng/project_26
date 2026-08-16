<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AlertController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $start = now()->startOfMonth();
        $end = now()->endOfMonth();

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

        $budgetAlerts = $budgets
            ->filter(function (Budget $budget) use ($spentByCategory): bool {
                $amount = (float) $budget->amount;
                $spent = (float) ($spentByCategory[$budget->category_id] ?? 0);

                return $amount > 0 && $spent >= $amount * 0.8;
            })
            ->map(function (Budget $budget) use ($spentByCategory): array {
                $amount = (float) $budget->amount;
                $spent = (float) ($spentByCategory[$budget->category_id] ?? 0);

                return [
                    'id' => $budget->id,
                    'type' => $spent > $amount ? 'over_budget' : 'near_budget',
                    'category' => $budget->category
                        ? [
                            'id' => $budget->category->id,
                            'name' => $budget->category->name,
                            'color' => $budget->category->color,
                            'icon' => $budget->category->icon,
                        ]
                        : null,
                    'spent' => $spent,
                    'amount' => $amount,
                    'remaining' => max($amount - $spent, 0),
                    'percentage' => round(($spent / $amount) * 100, 1),
                    'currency' => $budget->currency,
                ];
            })
            ->values();

        $upcomingStart = now()->copy()->startOfDay();
        $upcomingEnd = now()->copy()->addDays(7)->endOfDay();

        $billReminders = Expense::forUser($user)
            ->recurring()
            ->with('category:id,name,color,icon')
            ->whereNotNull('recurring_next_date')
            ->where('recurring_next_date', '<=', $upcomingEnd)
            ->orderBy('recurring_next_date')
            ->get()
            ->map(function (Expense $expense): array {
                return [
                    'id' => $expense->id,
                    'description' => $expense->description,
                    'vendor' => $expense->vendor,
                    'amount' => (float) $expense->amount,
                    'currency' => $expense->currency,
                    'due_date' => $expense->recurring_next_date?->toDateString(),
                    'due_in_days' => $expense->recurring_next_date
                        ? max(0, $expense->recurring_next_date->diffInDays(now()->startOfDay()))
                        : null,
                    'overdue' => $expense->recurring_next_date?->isBefore(now()->startOfDay()) ?? false,
                    'category' => $expense->category
                        ? [
                            'name' => $expense->category->name,
                            'color' => $expense->category->color,
                        ]
                        : null,
                ];
            })
            ->values();

        $count = $budgetAlerts->count() + $billReminders->count();

        return Inertia::render('Alerts', [
            'budget_alerts' => $budgetAlerts,
            'bill_reminders' => $billReminders,
            'count' => $count,
            'currency' => 'SAR',
        ]);
    }
}
