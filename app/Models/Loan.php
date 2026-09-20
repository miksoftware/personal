<?php

namespace App\Models;

use App\Models\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['user_id', 'client_id', 'type', 'description', 'amount', 'loan_date', 'status', 'notes'])]
class Loan extends Model
{
    use BelongsToUser;

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type === 'recibido' ? 'Recibido (Me prestaron)' : 'Entregado (Yo presté)';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pendiente' => 'Pendiente',
            'devuelto'  => 'Devuelto',
            'canjeado'  => 'Canjeado',
            default     => ucfirst($this->status),
        };
    }
}
