<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['client_id', 'creditor_name', 'description', 'total_amount', 'status', 'credit_date', 'notes'])]
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

    // ── Computed Accessors ──────────────────────────────────

    public function getTotalPaidAttribute(): float
    {
        if (array_key_exists('payments_sum_amount', $this->attributes)) {
            return (float) $this->attributes['payments_sum_amount'];
        }
        if ($this->relationLoaded('payments')) {
            return (float) $this->payments->sum('amount');
        }
        return (float) $this->payments()->sum('amount');
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

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'activo'    => 'Activo',
            'pagado'    => 'Pagado',
            'cancelado' => 'Cancelado',
            default     => ucfirst($this->status),
        };
    }
}
