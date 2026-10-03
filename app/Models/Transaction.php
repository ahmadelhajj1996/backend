<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'type',
        'amount',
        'currency',
        'description',
        'notes',
        'occurred_at',
    ];


    protected function casts(): array
    {
        return [
            'type'        => TransactionType::class,
            'amount'      => MoneyCast::class,
            'occurred_at' => 'datetime',
        ];
    }

    public function scopeIncome(Builder $q): Builder
    {
        return $q->where('type', TransactionType::Income);
    }

    public function scopeExpense(Builder $q): Builder
    {
        return $q->where('type', TransactionType::Expense);
    }

    public function scopeFilter(Builder $q, array $f): Builder
    {
        return $q
            ->when($f['type'] ?? null, fn ($q, $v) => $q->where('type', $v))
            ->when($f['from'] ?? null, fn ($q, $v) => $q->where('occurred_at', '>=', $v))
            ->when($f['to'] ?? null, fn ($q, $v) => $q->where('occurred_at', '<=', $v))
            ->when($f['search'] ?? null, fn ($q, $v) => $q->where(function ($q) use ($v) {
                $q->where('description', 'like', "%{$v}%")
                  ->orWhere('notes', 'like', "%{$v}%");
            }));
    }
}