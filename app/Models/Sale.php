<?php

namespace App\Models;

use App\Models\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'user_id', 'client_id', 'category', 'item_name', 'description',
    'serial_number', 'warranty', 'quantity', 'unit_price', 'total_amount',
    'sale_date', 'status', 'paid_at', 'bank_account_id', 'payment_method', 'notes'
])]
class Sale extends Model
{
    use BelongsToUser;

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'equipo'    => 'Equipo / Hardware',
            'accesorio' => 'Accesorio / Suministro',
            'servicio'  => 'Servicio / Instalación',
            default     => 'Otro',
        };
    }

    public function getCategoryBadgeColorAttribute(): string
    {
        return match ($this->category) {
            'equipo'    => '#6366f1',
            'accesorio' => '#06b6d4',
            'servicio'  => '#f59e0b',
            default     => '#8b5cf6',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pagado'    => 'Pagado',
            'pendiente' => 'Pendiente',
            default     => ucfirst($this->status),
        };
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'efectivo'      => 'Efectivo',
            'nequi'         => 'Nequi',
            'bancolombia'   => 'Bancolombia',
            'transferencia' => 'Transferencia',
            default         => ucfirst((string) $this->payment_method),
        };
    }
}
