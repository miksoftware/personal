<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['credit_id', 'amount', 'concept', 'method', 'payment_date', 'reference', 'notes'])]
class CreditPayment extends Model
{
    public function credit(): BelongsTo
    {
        return $this->belongsTo(Credit::class);
    }

    public function getMethodLabelAttribute(): string
    {
        return match ($this->method) {
            'efectivo'    => 'Efectivo',
            'nequi'       => 'Nequi',
            'bancolombia' => 'Bancolombia',
            default       => ucfirst($this->method),
        };
    }
}
