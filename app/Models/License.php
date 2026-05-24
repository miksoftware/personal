<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['client_id', 'url', 'block_token', 'status', 'billing_cycle', 'monthly_fee', 'next_billing_date', 'is_free'])]
class License extends Model
{
    /**
     * Get the client that owns the license.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get user-friendly status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'activa' => 'Activa',
            'suspendida' => 'Suspendida',
            'vencida' => 'Vencida',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get user-friendly billing cycle label.
     */
    public function getBillingCycleLabelAttribute(): string
    {
        return match ($this->billing_cycle) {
            'mensual' => 'Mensual',
            'trimestral' => 'Trimestral',
            'semestral' => 'Semestral',
            'anual' => 'Anual',
            default => ucfirst($this->billing_cycle),
        };
    }

    /**
     * Scope to count active licenses for a client.
     */
    public static function countForClient(int $clientId): int
    {
        return self::where('client_id', $clientId)->count();
    }
}
