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
     * Audit loglarına yazılmayan sahələr.
     *
     * `stock_quantity` — bu, anbar snapshot-ıdır; hər detalların
     * istifadəsi nəticəsində avtomatik dəyişir və audit üçün maraqlı
     * deyil. İstifadəçi bunun yerinə `used_quantity`-i görmək istəyir.
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
