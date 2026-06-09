<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['bank_account_id', 'balance_before', 'balance_after', 'amount', 'adjustment_date', 'reason', 'notes'])]
class BankAccountAdjustment extends Model
{
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }
}
