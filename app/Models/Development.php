<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'parent_id', 'client_id', 'license_id', 'type',
    'title', 'description', 'amount', 'monthly_fee', 'contract_months', 'billing_cycle',
    'status', 'delivered_at', 'paid_at', 'started_at', 'estimated_end_at',
])]
class Development extends Model
{
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Development::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Development::class, 'parent_id');
    }

    public function getStatusLabelAttribute(): string
    {
        $status = $this->status;

        // Dynamic status check for improvements
        if ($this->type === 'mejora' && $status === 'pendiente' && isset($this->is_dynamically_paid) && $this->is_dynamically_paid) {
            $status = 'pagado';
        }

        if ($this->type === 'proyecto') {
            return match ($status) {
                'pendiente'  => 'En Proceso',
                'completado' => 'Completado',
                default      => ucfirst($status),
            };
        }

        if ($this->type === 'soporte') {
            return match ($status) {
                'pendiente'  => 'Activo',
                'completado' => 'Finalizado',
                default      => ucfirst($status),
            };
        }

        return match ($status) {
            'pendiente' => 'Pendiente de pago',
            'pagado'    => 'Pagado',
            default     => ucfirst($status),
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'mejora'   => 'Mejora',
            'proyecto' => 'Proyecto',
            'soporte'  => 'Soporte',
            default    => ucfirst($this->type),
        };
    }

    public function getBillingCycleLabelAttribute(): string
    {
        return match ($this->billing_cycle) {
            'mensual'    => 'Mensual',
            'bimestral'  => 'Bimestral',
            'trimestral' => 'Trimestral',
            'semestral'  => 'Semestral',
            'anual'      => 'Anual',
            default      => ucfirst((string) $this->billing_cycle),
        };
    }
}
