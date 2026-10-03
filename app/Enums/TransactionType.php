<?php

namespace App\Enums;

enum TransactionType: string
{
    case Income  = 'income';   // money entering the office
    case Expense = 'expense';  // money leaving the office

    public function label(): string
    {
        return match ($this) {
            self::Income  => 'Income',
            self::Expense => 'Expense',
        };
    }
}