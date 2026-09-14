<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Models\Traits\HasGarageScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComplaintType extends Model
{
    use Auditable, HasFactory, HasGarageScope;

    protected $fillable = [
        'name',
        'garage_id',
        'company_id',
    ];

    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'ILIKE', "%{$search}%");
    }
}
