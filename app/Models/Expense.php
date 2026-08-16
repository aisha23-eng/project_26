<?php

namespace App\Models;

use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Expense extends Model
{
    /** @use HasFactory<ExpenseFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'category_id', 'amount', 'currency',
        'description', 'vendor', 'date', 'receipt_path',
        'is_recurring', 'recurring_frequency', 'recurring_interval',
        'recurring_start_date', 'recurring_end_date', 'recurring_next_date',
        'parent_expense_id',
    ];

    protected $attributes = [
        'currency' => 'SAR',
        'is_recurring' => false,
        'recurring_interval' => 1,
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date' => 'date',
            'is_recurring' => 'boolean',
            'recurring_interval' => 'integer',
            'recurring_start_date' => 'date',
            'recurring_end_date' => 'date',
            'recurring_next_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function parentExpense(): BelongsTo
    {
        return $this->belongsTo(Expense::class, 'parent_expense_id');
    }

    public function childExpenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'parent_expense_id');
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->whereBelongsTo($user);
    }

    public function scopeRecurring(Builder $query): Builder
    {
        return $query->where('is_recurring', true);
    }

    public function scopeDueSoon(Builder $query, ?Carbon $date = null): Builder
    {
        return $query->recurring()->where('recurring_next_date', '<=', $date ?? now());
    }

    public function scopeBetweenDates(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->whereBetween('date', [$start, $end]);
    }
}
