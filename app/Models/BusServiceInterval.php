<?php

namespace App\Models;

use App\Models\Traits\HasGarageScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusServiceInterval extends Model
{
    use HasFactory, HasGarageScope;

    // ✅ garage_id və company_id əlavə edildi
    protected $fillable = ['bus_id', 'service_template_id', 'custom_km_interval', 'garage_id', 'company_id'];

    public function bus()
    {
        return $this->belongsTo(Bus::class);
    }

    public function serviceTemplate()
    {
        return $this->belongsTo(ServiceTemplate::class);
    }
}
