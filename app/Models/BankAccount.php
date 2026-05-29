<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['name', 'current_balance', 'account_number', 'is_active'])]
class BankAccount extends Model
{
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
