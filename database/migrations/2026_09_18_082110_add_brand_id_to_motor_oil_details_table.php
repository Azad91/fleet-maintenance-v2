<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Motor oil catalog becomes brand-scoped.
 *
 * Each manufacturer has its own service intervals (BMC 15 000 km,
 * Yutong 20 000 km, ...). The catalog must be partitioned so that
 * one brand's schedule never bleeds into another.
 *
 * Nullable for backward compatibility: existing rows (if any) remain
 * unassigned. New imports always require an explicit brand selection.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('motor_oil_details', function (Blueprint $table) {
            $table->foreignId('brand_id')
                ->nullable()
                ->after('company_id')
                ->constrained('bus_brands')
                ->restrictOnDelete();

            $table->index(['garage_id', 'brand_id']);
        });
    }

    public function down(): void
    {
        Schema::table('motor_oil_details', function (Blueprint $table) {
            $table->dropIndex(['garage_id', 'brand_id']);
            $table->dropConstrainedForeignId('brand_id');
        });
    }
};
