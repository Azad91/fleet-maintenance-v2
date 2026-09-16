<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a quarantine flag to warehouse rows.
 *
 * Quarantine is a parallel "sub-warehouse" within the same garage —
 * the same physical stock, but flagged as unusable (defective,
 * damaged, expired). The flag drives:
 *
 *   - a separate index filter on the warehouse list page
 *   - a separate section in warehouse reports
 *   - exclusion from active stock totals
 *
 * Quarantine rows use a "Q-" prefix on the code (e.g. "Q-FILTER-001")
 * so the existing partial unique index (garage_id, code) keeps
 * working without a schema change.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->boolean('is_quarantine')
                ->default(false)
                ->after('unit');

            $table->index(
                ['garage_id', 'is_quarantine'],
                'warehouses_garage_quarantine_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropIndex('warehouses_garage_quarantine_idx');
            $table->dropColumn('is_quarantine');
        });
    }
};
