<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'garage_id', 'company_id', 'auditable_type', 'auditable_id',
        'event', 'old_values', 'new_values',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    // ==================== RELATIONSHIPS ====================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function garage()
    {
        return $this->belongsTo(Garage::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function auditable()
    {
        return $this->morphTo();
    }

    // ==================== SCOPES ====================

    public function scopeForModel($query, string $model, ?int $id = null)
    {
        $query->where('auditable_type', $model);
        if ($id) {
            $query->where('auditable_id', $id);
        }

        return $query;
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForGarage($query, int $garageId)
    {
        return $query->where('garage_id', $garageId);
    }

    public function scopeForEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    public function scopeDateRange($query, string $from, string $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }
}
