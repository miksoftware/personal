<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['client_id', 'bank_account_id', 'development_id', 'license_id', 'license_payment_type', 'amount', 'method', 'payment_date', 'reference', 'notes'])]
class Payment extends Model
{
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function development(): BelongsTo
    {
        return $this->belongsTo(Development::class);
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    public function getMethodLabelAttribute(): string
    {
        return match ($this->method) {
            'efectivo'    => 'Efectivo',
            'nequi'       => 'Nequi',
            'bancolombia' => 'Bancolombia',
            'transferencia' => 'Transferencia',
            default       => ucfirst($this->method),
        };
    }

    public function getDestinationLabelAttribute(): string
    {
        if ($this->development) {
            return $this->development->title;
        }

        if ($this->license) {
            $type = $this->license_payment_type === 'instalacion' ? 'Instalación' : 'Mensualidad';
            return "Licencia: {$this->license->url} ({$type})";
        }

        return 'Cuenta Global';
    }
}
