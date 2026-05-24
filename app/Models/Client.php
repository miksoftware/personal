<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['type', 'name', 'model', 'phone'])]
class Client extends Model
{
    public function getTypeLabelAttribute(): string
    {
        return $this->type === 'persona' ? 'Persona' : 'Empresa';
    }

    public function getModelLabelAttribute(): string
    {
        return $this->model === 'revendedor' ? 'Revendedor' : 'Cliente Final';
    }

    public function developments(): HasMany
    {
        return $this->hasMany(Development::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
