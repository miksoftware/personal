<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Support\Facades\Auth;

#[Fillable([
    'user_id',
    'client_id',
    'url',
    'block_token',
    'status',
    'billing_cycle',
    'monthly_fee',
    'setup_fee',
    'next_billing_date',
    'next_setup_billing_date',
    'standard_billing_cycle',
    'standard_monthly_fee',
    'standard_setup_fee',
    'standard_next_setup_billing_date',
    'is_free',
    'contract_path',
    'contract_original_name',
    'contract_uploaded_at',
])]
class License extends Model
{
    protected $casts = [
        'is_free' => 'boolean',
        'contract_uploaded_at' => 'datetime',
    ];

    /**
     * Scope licenses to owner records plus licenses belonging to the user's linked SaaS client.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('user_id', function (Builder $builder) {
            if (Auth::check()) {
                $user = Auth::user();
                $table = $builder->getModel()->getTable();

                if (!empty($user->linked_client_id)) {
                    $linkedClientId = (int) $user->linked_client_id;
                    $builder->where(function (Builder $query) use ($table, $user, $linkedClientId) {
                        $query->where("{$table}.user_id", $user->id)
                            ->orWhere("{$table}.client_id", $linkedClientId);
                    });
                } else {
                    $builder->where("{$table}.user_id", $user->id);
                }
            }
        });

        static::creating(function ($model) {
            if (empty($model->user_id) && Auth::check()) {
                $model->user_id = Auth::id();
            }
        });
    }

    /**
     * Get the user that owns this license.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the client that owns the license (bypasses tenant scope for shared reseller views).
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withoutGlobalScope('user_id');
    }

    /**
     * Per-user custom client display name overrides.
     */
    public function userOverrides(): HasMany
    {
        return $this->hasMany(LicenseUserOverride::class);
    }

    /**
     * Determine whether the given user owns this license directly.
     */
    public function isOwnedBy(?User $user): bool
    {
        return $user !== null && (int) $this->user_id === (int) $user->id;
    }

    /**
     * Determine whether this license is shared with the given linked SaaS user.
     */
    public function isSharedForUser(?User $user): bool
    {
        return $user !== null
            && !$this->isOwnedBy($user)
            && !empty($user->linked_client_id)
            && (int) $this->client_id === (int) $user->linked_client_id;
    }

    /**
     * Determine whether the given user can view this license (owner or linked SaaS user).
     */
    public function canViewBy(?User $user): bool
    {
        return $this->isOwnedBy($user) || $this->isSharedForUser($user);
    }

    /**
     * Resolve the client name to display for the given user (supports per-user custom rename).
     */
    public function displayClientNameFor(?User $user): string
    {
        if ($user) {
            $override = $this->relationLoaded('userOverrides')
                ? $this->userOverrides->firstWhere('user_id', $user->id)
                : $this->userOverrides()->where('user_id', $user->id)->first();

            if ($override && trim((string) $override->custom_client_name) !== '') {
                return $override->custom_client_name;
            }
        }

        return $this->client->name ?? '—';
    }

    /**
     * Snapshot standard plan fields and switch this license to flat Pago Anual Total.
     */
    public function switchToAnnualTotal(): void
    {
        if ($this->isAnnualTotal()) {
            return;
        }

        $this->standard_billing_cycle = $this->billing_cycle;
        $this->standard_monthly_fee = $this->monthly_fee;
        $this->standard_setup_fee = $this->setup_fee;
        $this->standard_next_setup_billing_date = $this->next_setup_billing_date;

        $this->billing_cycle = 'anual_total';
        $this->monthly_fee = $this->is_free
            ? 0.00
            : round(((float) $this->monthly_fee * 12) + (float) $this->setup_fee, 2);
        $this->setup_fee = 0.00;
        $this->next_setup_billing_date = null;
    }

    /**
     * Restore the standard plan fields when switching back from Pago Anual Total.
     */
    public function restoreStandardPlan(): void
    {
        if (!$this->isAnnualTotal()) {
            return;
        }

        if (!empty($this->standard_billing_cycle)) {
            $this->billing_cycle = $this->standard_billing_cycle;
            $this->monthly_fee = $this->is_free ? 0.00 : (float) ($this->standard_monthly_fee ?? 0);
            $this->setup_fee = (float) ($this->standard_setup_fee ?? 0);
            $this->next_setup_billing_date = $this->standard_next_setup_billing_date;
            return;
        }

        $this->billing_cycle = 'anual';
        $this->monthly_fee = $this->is_free ? 0.00 : round(((float) $this->monthly_fee) / 12, 2);
        $this->setup_fee = 0.00;
        $this->next_setup_billing_date = null;
    }

    /**
     * Preview the equivalent annual total and standard cycle labels for UI clarity.
     */
    public function getProjectedAnnualTotalAttribute(): float
    {
        if ($this->is_free) {
            return 0.0;
        }

        if ($this->isAnnualTotal()) {
            return round((float) $this->monthly_fee, 2);
        }

        return round(((float) $this->monthly_fee * 12) + (float) $this->setup_fee, 2);
    }

    /**
     * Human-readable summary of the standard (non-annual-total) plan.
     */
    public function getStandardPlanSummaryAttribute(): string
    {
        if ($this->is_free) {
            return 'Gratuita ($0.00)';
        }

        $cycle = $this->isAnnualTotal()
            ? ($this->standard_billing_cycle ?: 'anual')
            : $this->billing_cycle;

        $monthly = $this->isAnnualTotal()
            ? (float) ($this->standard_monthly_fee ?? round(((float) $this->monthly_fee) / 12, 2))
            : (float) $this->monthly_fee;

        $setup = $this->isAnnualTotal()
            ? (float) ($this->standard_setup_fee ?? 0)
            : (float) $this->setup_fee;

        $months = match ($cycle) {
            'trimestral' => 3,
            'semestral' => 6,
            'anual' => 12,
            default => 1,
        };

        $cycleLabel = match ($cycle) {
            'trimestral' => 'Trimestral',
            'semestral' => 'Semestral',
            'anual' => 'Anual',
            default => 'Mensual',
        };

        $servicePart = '$' . number_format($monthly * $months, 0, '.', ',');
        if ($setup > 0) {
            return "{$cycleLabel}: {$servicePart} + Anualidad $" . number_format($setup, 0, '.', ',');
        }

        return "{$cycleLabel}: {$servicePart}";
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
            'anual_total' => 'Anual Total',
            default => ucfirst($this->billing_cycle),
        };
    }

    /**
     * Whether this license uses a single flat annual total fee.
     */
    public function isAnnualTotal(): bool
    {
        return $this->billing_cycle === 'anual_total';
    }

    /**
     * Whether a PDF contract is attached to this license.
     */
    public function hasContract(): bool
    {
        return !empty($this->contract_path);
    }

    /**
     * Number of months per service billing cycle.
     */
    public function getCycleMonthsAttribute(): int
    {
        return match ($this->billing_cycle) {
            'trimestral' => 3,
            'semestral' => 6,
            'anual', 'anual_total' => 12,
            default => 1,
        };
    }

    /**
     * Service fee billed per cycle.
     * For 'anual_total', monthly_fee stores the flat annual total directly without multiplying by 12.
     */
    public function getServiceAmountAttribute(): float
    {
        if ($this->is_free) {
            return 0.0;
        }

        if ($this->isAnnualTotal()) {
            return round((float) $this->monthly_fee, 2);
        }

        return round((float) $this->monthly_fee * $this->cycle_months, 2);
    }

    /**
     * Separate annual/setup fee (not applicable to free or anual_total licenses).
     */
    public function getAnnualFeeAttribute(): float
    {
        if ($this->is_free || $this->isAnnualTotal()) {
            return 0.0;
        }

        return round((float) $this->setup_fee, 2);
    }

    /**
     * Centralized billing breakdown for table clarity, KPI summaries, and payment modals.
     */
    public function getBillingBreakdownAttribute(): array
    {
        $today = Carbon::now()->startOfDay();
        $soonThreshold = $today->copy()->addDays(15);

        $serviceAmount = $this->service_amount;
        $annualFee = $this->annual_fee;

        if ($this->is_free) {
            return [
                'service_amount' => 0.0,
                'annual_fee' => 0.0,
                'service_due' => false,
                'service_overdue' => false,
                'service_due_today' => false,
                'service_soon' => false,
                'annual_due' => false,
                'annual_overdue' => false,
                'annual_due_today' => false,
                'annual_soon' => false,
                'annual_missing_date' => false,
                'due_now_amount' => 0.0,
                'display_amount' => 0.0,
                'display_state' => 'free',
                'display_badge' => 'Gratuita',
                'display_caption' => 'Sin costo (5.ª licencia)',
                'due_parts' => [],
            ];
        }

        $serviceDate = $this->next_billing_date ? Carbon::parse($this->next_billing_date)->startOfDay() : null;
        $annualDate = ($annualFee > 0 && $this->next_setup_billing_date)
            ? Carbon::parse($this->next_setup_billing_date)->startOfDay()
            : null;

        $serviceOverdue = $serviceDate !== null && $serviceDate->lt($today);
        $serviceDueToday = $serviceDate !== null && $serviceDate->equalTo($today);
        $serviceDue = $serviceOverdue || $serviceDueToday;
        $serviceSoon = !$serviceDue && $serviceDate !== null && $serviceDate->lte($soonThreshold);

        $annualOverdue = $annualFee > 0 && $annualDate !== null && $annualDate->lt($today);
        $annualDueToday = $annualFee > 0 && $annualDate !== null && $annualDate->equalTo($today);
        $annualDue = $annualOverdue || $annualDueToday;
        $annualSoon = $annualFee > 0 && !$annualDue && $annualDate !== null && $annualDate->lte($soonThreshold);
        $annualMissingDate = $annualFee > 0 && $annualDate === null;

        $dueNowAmount = 0.0;
        $dueParts = [];

        if ($serviceDue && $serviceAmount > 0) {
            $dueNowAmount += $serviceAmount;
            $label = $this->isAnnualTotal() ? 'Anual Total' : 'Servicio';
            $dueParts[] = "{$label} ($" . number_format($serviceAmount, 0, '.', ',') . ')';
        }

        if ($annualDue && $annualFee > 0) {
            $dueNowAmount += $annualFee;
            $dueParts[] = 'Anualidad ($' . number_format($annualFee, 0, '.', ',') . ')';
        }

        $dueNowAmount = round($dueNowAmount, 2);

        if ($dueNowAmount > 0) {
            $isOverdue = $serviceOverdue || $annualOverdue;
            $displayAmount = $dueNowAmount;
            $displayState = $isOverdue ? 'overdue' : 'due_today';
            $displayBadge = $isOverdue ? 'Vencido por cobrar' : 'Vence hoy';
            $displayCaption = implode(' + ', $dueParts);
        } elseif ($this->isAnnualTotal()) {
            $displayAmount = $serviceAmount;
            $displayState = $serviceSoon ? 'upcoming_soon' : 'up_to_date';
            $displayBadge = $serviceSoon ? 'Renueva pronto' : 'Al día';
            $displayCaption = 'Próx: Anual Total' . ($serviceDate ? ' (' . $serviceDate->format('d M Y') . ')' : '');
        } elseif ($annualFee > 0 && $annualDate !== null && $serviceDate !== null && $annualDate->equalTo($serviceDate)) {
            $displayAmount = round($serviceAmount + $annualFee, 2);
            $displayState = $serviceSoon ? 'upcoming_soon' : 'up_to_date';
            $displayBadge = $serviceSoon ? 'Vence pronto' : 'Al día';
            $displayCaption = 'Próx: Servicio ($' . number_format($serviceAmount, 0, '.', ',') . ') + Anualidad ($' . number_format($annualFee, 0, '.', ',') . ')';
        } elseif ($annualFee > 0 && $annualDate !== null && ($serviceDate === null || $annualDate->lt($serviceDate))) {
            $displayAmount = $annualFee;
            $displayState = $annualSoon ? 'upcoming_soon' : 'up_to_date';
            $displayBadge = $annualSoon ? 'Anualidad próxima' : 'Al día';
            $displayCaption = 'Próx: Anualidad (' . $annualDate->format('d M Y') . ')';
        } else {
            $displayAmount = $serviceAmount;
            $displayState = $serviceSoon ? 'upcoming_soon' : 'up_to_date';
            $displayBadge = $serviceSoon ? 'Servicio próximo' : 'Al día';
            $displayCaption = 'Próx: Servicio' . ($serviceDate ? ' (' . $serviceDate->format('d M Y') . ')' : '');
        }

        return [
            'service_amount' => $serviceAmount,
            'annual_fee' => $annualFee,
            'service_due' => $serviceDue,
            'service_overdue' => $serviceOverdue,
            'service_due_today' => $serviceDueToday,
            'service_soon' => $serviceSoon,
            'annual_due' => $annualDue,
            'annual_overdue' => $annualOverdue,
            'annual_due_today' => $annualDueToday,
            'annual_soon' => $annualSoon,
            'annual_missing_date' => $annualMissingDate,
            'due_now_amount' => $dueNowAmount,
            'display_amount' => $displayAmount,
            'display_state' => $displayState,
            'display_badge' => $displayBadge,
            'display_caption' => $displayCaption,
            'due_parts' => $dueParts,
        ];
    }

    /**
     * Scope to count active licenses for a client.
     */
    public static function countForClient(int $clientId): int
    {
        return self::where('client_id', $clientId)->count();
    }
}
