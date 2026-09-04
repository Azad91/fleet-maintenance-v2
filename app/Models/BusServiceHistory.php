<?php

namespace App\Models;

use App\Models\Traits\HasGarageScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusServiceHistory extends Model
{
    use HasFactory, HasGarageScope;

    protected $table = 'bus_service_history';
    protected $fillable = ['bus_id', 'service_template_id', 'km', 'date', 'garage_id', 'company_id'];

    public function bus()
    {
        return $this->belongsTo(Bus::class);
    }

    public function serviceTemplate()
    {
        return $this->belongsTo(ServiceTemplate::class);
    }
}
