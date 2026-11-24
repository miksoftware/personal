<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;

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
    'is_free',
    'contract_path',
    'contract_original_name',
    'contract_uploaded_at',
])]
class License extends Model
{
    use BelongsToUser;

    protected $casts = [
        'is_free' => 'boolean',
        'contract_uploaded_at' => 'datetime',
    ];

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
