<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['client_id', 'type', 'creditor_name', 'description', 'total_amount', 'installment_value', 'total_installments', 'status', 'credit_date', 'notes'])]
class Credit extends Model
{
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CreditPayment::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type === 'proveedor' ? 'Proveedor (Canje)' : 'Personal (Cuotas)';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'activo'    => 'Activo',
            'pagado'    => 'Pagado',
            'cancelado' => 'Cancelado',
            default     => ucfirst($this->status),
        };
    }

    public function getTotalPaidAttribute(): float
    {
        return (float) $this->payments_sum_amount;
    }

    public function getBalanceAttribute(): float
    {
        return (float) $this->total_amount - $this->total_paid;
    }

    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_amount <= 0) return 0;
        return min(100, round(($this->total_paid / $this->total_amount) * 100, 1));
    }

    /**
     * Get the number of installments paid based on total paid and installment value.
     */
    public function getInstallmentsPaidAttribute(): int
    {
        if ($this->installment_value <= 0) return 0;
        return (int) floor($this->total_paid / $this->installment_value);
    }

    /**
     * Get the next installment number.
     */
    public function getNextInstallmentNumberAttribute(): int
    {
        return $this->installments_paid + 1;
    }
}
