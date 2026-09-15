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
}