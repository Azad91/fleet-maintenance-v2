<?php

namespace App\Models;

use App\Models\Traits\HasGarageScope;
use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    use HasGarageScope;

    protected $fillable = [
        'garage_id',
        'company_id',
        'bus_id',
        'yer',
        'surucu_adi',
        'shikayet',
        'sikayet_tipi',
        'status',
        'detallar',
        'km',
        'bildirilme_tarix',
        'bildirilme_saat',
        'is_baslama_tarix',
        'is_baslama_saat',
        'is_bitme_tarix',
        'is_bitme_saat',
        'kim_is_gorub',
        'employee_id',
        'service_template_id',
        'service_km',
        'qeyd',
    ];

    protected $casts = [
        'detallar' => 'array',
        'bildirilme_tarix' => 'date',
        'is_baslama_tarix' => 'date',
        'is_bitme_tarix' => 'date',
    ];

    public function bus()
    {
        return $this->belongsTo(Bus::class);
    }
}
