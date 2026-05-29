<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['bank_account_id', 'description', 'category', 'amount', 'method', 'expense_date', 'reference', 'notes'])]
class Expense extends Model
{
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function getMethodLabelAttribute(): string
    {
        return match ($this->method) {
            'efectivo'    => 'Efectivo',
            'nequi'       => 'Nequi',
            'bancolombia' => 'Bancolombia',
            'daviplata'   => 'Daviplata',
            default       => ucfirst($this->method),
        };
    }
}
