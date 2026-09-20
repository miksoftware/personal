<?php

namespace App\Models;

use App\Models\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['user_id', 'bank_account_id', 'balance_before', 'balance_after', 'amount', 'adjustment_date', 'reason', 'notes'])]
class BankAccountAdjustment extends Model
{
    use BelongsToUser;

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }
}
