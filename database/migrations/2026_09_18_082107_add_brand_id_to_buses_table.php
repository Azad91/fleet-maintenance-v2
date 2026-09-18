<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link each bus to its manufacturer.
 *
 * Nullable by design:
 *   - Legacy buses may not have a brand assigned.
 *   - Import without a selected brand leaves brand_id NULL.
 *
 * RESTRICT on delete: a brand cannot be removed while buses are
 * still attached to it — the admin must reassign the buses first.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buses', function (Blueprint $table) {
            $table->foreignId('brand_id')
                ->nullable()
                ->after('company_id')
                ->constrained('bus_brands')
                ->restrictOnDelete();

            // Composite index for the most common filter path:
            // "show me all buses of brand X in my current garage".
            $table->index(['garage_id', 'brand_id']);
        });
    }

    public function down(): void
    {
        Schema::table('buses', function (Blueprint $table) {
            $table->dropIndex(['garage_id', 'brand_id']);
            $table->dropConstrainedForeignId('brand_id');
        });
    }
};
