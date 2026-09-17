<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Models\Traits\HasGarageScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ComplaintDetail extends Model
{
    use Auditable, HasGarageScope, SoftDeletes;

    /**
     * Fields excluded from audit logging.
     *
     * `stock_quantity` — this is a snapshot of the warehouse quantity
     * at the time the detail was created. It changes automatically
     * whenever a part is used and is not meaningful for auditing.
     * Operators care about `used_quantity`, not the running balance.
     */
    protected static array $auditExcluded = [
        'stock_quantity',
    ];

    protected $fillable = [
        'complaint_id',
        'shikayet_index',
        'code',
        'name',
        'stock_quantity',
        'used_quantity',
        'source_type',
        'employee_id',
        'notes',
        'garage_id',
        'company_id',
    ];

    public function complaint()
    {
        return $this->belongsTo(Complaint::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * True when the stock for this detail came from a service
     * vehicle instead of the garage warehouse.
     */
    public function isFromServiceVehicle(): bool
    {
        return $this->source_type === 'service_vehicle';
    }

    public function isFromWarehouse(): bool
    {
        return $this->source_type === 'warehouse';
    }

    /**
     * True when this detail was imported as historical data — it never
     * touched stock, and delete/update operations must leave stock
     * untouched.
     */
    public function isHistorical(): bool
    {
        return $this->source_type === 'historical';
    }

    /**
     * True when this detail represents an inspection or repair that
     * did NOT consume any stock (used_quantity = 0). The row is kept
     * for documentation so future operators can see that the part was
     * looked at, without polluting the stock ledger.
     */
    public function isInspection(): bool
    {
        return $this->source_type === 'inspection';
    }

    /**
     * True when this detail actually consumed stock. Used by report
     * queries that need to exclude inspection/historical rows.
     */
    public function consumedStock(): bool
    {
        return in_array($this->source_type, ['warehouse', 'service_vehicle'], true)
            && $this->used_quantity > 0;
    }
}