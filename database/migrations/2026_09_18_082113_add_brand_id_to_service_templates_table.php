<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Service templates become brand-scoped.
 *
 * Service templates are derived from the motor oil catalog, so they
 * inherit the same brand partition. This keeps a template like
 * "BMC Motor Oil 15 000 km" separate from "Yutong Motor Oil 20 000 km".
 *
 * Nullable for backward compatibility; new rows always require a brand.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_templates', function (Blueprint $table) {
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
        Schema::table('service_templates', function (Blueprint $table) {
            $table->dropIndex(['garage_id', 'brand_id']);
            $table->dropConstrainedForeignId('brand_id');
        });
    }
};
