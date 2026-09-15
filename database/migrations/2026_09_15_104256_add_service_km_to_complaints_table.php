<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the `service_km` column to complaints.
     *
     * A maintenance complaint is tied to a motor-oil interval (e.g.
     * 288000 km). The interval value is what identifies which
     * motor_oil_details rows were used, so it must be persisted on
     * the complaint itself. `service_template_id` alone is not enough
     * because the service templates table was purged and intervals
     * now come straight from motor_oil_details.
     *
     * Nullable: only maintenance complaints carry a value.
     */
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->unsignedInteger('service_km')
                ->nullable()
                ->after('service_template_id');
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropColumn('service_km');
        });
    }
};
