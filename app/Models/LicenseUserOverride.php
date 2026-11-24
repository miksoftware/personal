<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['license_id', 'user_id', 'custom_client_name'])]
class LicenseUserOverride extends Model
{
    /**
     * Get the license this override belongs to.
     */
    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    /**
     * Get the SaaS user that owns this custom client name.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
