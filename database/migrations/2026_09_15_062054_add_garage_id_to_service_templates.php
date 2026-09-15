<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add garage/company scoping to service_templates.
     *
     * Service templates are derived from motor_oil_details (each KM
     * interval becomes a template). Since the source catalog is now
     * garage-scoped, the templates must be too — otherwise one
     * garage's template list would leak into another garage.
     */
    public function up(): void
    {
        Schema::table('service_templates', function (Blueprint $table) {
            $table->foreignId('garage_id')
                ->nullable()
                ->after('id')
                ->constrained('garages')
                ->cascadeOnDelete();

            $table->foreignId('company_id')
                ->nullable()
                ->after('garage_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            $table->index(['garage_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('service_templates', function (Blueprint $table) {
            $table->dropIndex(['garage_id', 'name']);
            $table->dropConstrainedForeignId('garage_id');
            $table->dropConstrainedForeignId('company_id');
        });
    }
};
