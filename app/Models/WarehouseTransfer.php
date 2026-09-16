<?php

namespace App\Models;

use App\Enums\TransferStatus;
use App\Enums\TransferType;
use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A warehouse transfer between two locations.
 *
 * IMPORTANT — NOT using HasGarageScope
 * ------------------------------------
 * A transfer involves TWO garages (source and destination). The
 * global scope would filter by a single `garage_id` column, which
 * does not exist here. Instead, use one of the explicit scopes below:
 *
 *   - ->visibleToGarage($id)  → both inbound and outbound
 *   - ->outbound($id)         → only where I am the source
 *   - ->inbound($id)          → only where I am the destination
 *
 * The controller and services must use one of these explicitly.
 */
class WarehouseTransfer extends Model
{
    use Auditable, SoftDeletes;

    protected $fillable = [
        'company_id',
        'from_garage_id',
        'to_garage_id',
        'to_service_vehicle_id',
        'type',
        'status',
        'declared_total',
        'received_total',
        'notes',
        'discrepancy_notes',
        'resolution',
        'dispatched_by',
        'dispatched_at',
        'received_by',
        'received_at',
        'resolved_by',
        'resolved_at',
        'created_by',
    ];

    protected $casts = [
        'type'          => TransferType::class,
        'status'        => TransferStatus::class,
        'dispatched_at' => 'datetime',
        'received_at'   => 'datetime',
        'resolved_at'   => 'datetime',
    ];

    // ==================== RELATIONS ====================

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function fromGarage()
    {
        return $this->belongsTo(Garage::class, 'from_garage_id');
    }

    public function toGarage()
    {
        return $this->belongsTo(Garage::class, 'to_garage_id');
    }

    public function toServiceVehicle()
    {
        return $this->belongsTo(ServiceVehicle::class, 'to_service_vehicle_id');
    }

    public function items()
    {
        return $this->hasMany(WarehouseTransferItem::class, 'transfer_id');
    }

    public function dispatchedBy()
    {
        return $this->belongsTo(User::class, 'dispatched_by');
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ==================== SCOPES ====================

    /**
     * Transfers where the given garage is either the source OR the
     * destination. This is the canonical "show me my transfers" query.
     */
    public function scopeVisibleToGarage(Builder $query, int $garageId): Builder
    {
        return $query->where(function ($q) use ($garageId) {
            $q->where('from_garage_id', $garageId)
              ->orWhere('to_garage_id', $garageId);
        });
    }

    public function scopeOutbound(Builder $query, int $garageId): Builder
    {
        return $query->where('from_garage_id', $garageId);
    }

    public function scopeInbound(Builder $query, int $garageId): Builder
    {
        return $query->where('to_garage_id', $garageId);
    }

    /**
     * Transfers that still require action from one of the two
     * garages. Used by the dashboard badge counter.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', [
            TransferStatus::Draft->value,
            TransferStatus::Dispatched->value,
            TransferStatus::Disputed->value,
        ]);
    }

    public function scopeByType(Builder $query, TransferType|string $type): Builder
    {
        $value = $type instanceof TransferType ? $type->value : $type;

        return $query->where('type', $value);
    }

    // ==================== HELPERS ====================

    /**
     * True when the given garage owns the destination side of this
     * transfer. Used by policies and controllers to decide who may
     * receive or reject.
     */
    public function isDestination(int $garageId): bool
    {
        // return_to_quarantine has no external destination — the
        // source garage is always "in charge".
        if ($this->type instanceof TransferType && $this->type->isReturnToQuarantine()) {
            return false;
        }

        return $this->to_garage_id === $garageId;
    }

    public function isSource(int $garageId): bool
    {
        return $this->from_garage_id === $garageId;
    }

    /**
     * Human-readable destination label — a garage name or a service
     * vehicle name depending on the transfer type.
     */
    public function getDestinationLabelAttribute(): string
    {
        if ($this->to_garage_id && $this->toGarage) {
            return $this->toGarage->name;
        }

        if ($this->to_service_vehicle_id && $this->toServiceVehicle) {
            return $this->toServiceVehicle->name;
        }

        return '—';
    }
    /**
     * Human-readable destination, including quarantine.
     */
    public function getDestinationLabelFullAttribute(): string
    {
        if ($this->type instanceof TransferType && $this->type->isReturnToQuarantine()) {
            return __('messages.transfers.destination_quarantine');
        }

        return $this->destination_label;
    }

    /**
     * True for transfers that do NOT go through the full
     * draft → dispatch → receive workflow.
     */
    public function isImmediate(): bool
    {
        return $this->type instanceof TransferType
            && ! $this->type->requiresWorkflow();
    }
}
