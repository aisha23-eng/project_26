<?php

namespace App\Models;

use Database\Factories\BudgetFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Budget extends Model
{
    /** @use HasFactory<BudgetFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id', 'category_id', 'amount', 'month', 'currency',
    ];

    protected $attributes = [
        'currency' => 'SAR',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'month' => 'date',
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

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->whereBelongsTo($user);
    }

    public function scopeForMonth(Builder $query, Carbon $month): Builder
    {
        return $query->where('month', $month->copy()->startOfMonth());
    }
}
