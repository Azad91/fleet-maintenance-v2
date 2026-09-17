<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One line of a warehouse transfer — "N units of item X".
 *
 * No HasGarageScope here: items are always accessed through their
 * parent transfer, which already carries the garage context. Adding
 * a second scope on the item would require denormalising garage_id
 * for no benefit and risk of divergence.
 */
class WarehouseTransferItem extends Model
{
    protected $fillable = [
        'transfer_id',
        'warehouse_id',
        'declared_quantity',
        'received_quantity',
        'notes',
    ];

    protected $casts = [
        'declared_quantity' => 'integer',
        'received_quantity' => 'integer',
    ];

    public function transfer()
    {
        return $this->belongsTo(WarehouseTransfer::class, 'transfer_id');
    }

    /**
     * The source warehouse row. Use withoutGlobalScopes() when
     * reading from a transfer that may belong to a different garage
     * than the current context.
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Difference between what was declared and what was received.
     * Returns null when the transfer has not been received yet.
     */
    public function getDifferenceAttribute(): ?int
    {
        if ($this->received_quantity === null) {
            return null;
        }

        return $this->received_quantity - $this->declared_quantity;
    }

    public function hasDiscrepancy(): bool
    {
        return $this->received_quantity !== null
            && $this->received_quantity !== $this->declared_quantity;
    }
}
