<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'parent_id', 'name', 'type', 'icon',
        'color', 'description', 'is_system', 'sort_order',
    ];

    protected $attributes = [
        'sort_order' => 0,
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function scopeExpenses(Builder $query): void
    {
        $query->where('type', 'expense');
    }

    public function scopeIncome(Builder $query): void
    {
        $query->where('type', 'income');
    }

    public function scopeSystem(Builder $query): void
    {
        $query->where('is_system', true);
    }

    public function scopeCustom(Builder $query): void
    {
        $query->where('is_system', false);
    }

    public function scopeForUser(Builder $query, User $user): void
    {
        $query->where(fn ($q) => $q->where('user_id', $user->id)->orWhereNull('user_id'));
    }

    public function scopeParents(Builder $query): void
    {
        $query->whereNull('parent_id');
    }

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('name');
    }
}
