<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['client_id', 'development_id', 'amount', 'method', 'payment_date', 'reference', 'notes'])]
class Payment extends Model
{
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function development(): BelongsTo
    {
        return $this->belongsTo(Development::class);
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

    public function getDestinationLabelAttribute(): string
    {
        return $this->development
            ? $this->development->title
            : 'Cuenta Global';
    }
}
