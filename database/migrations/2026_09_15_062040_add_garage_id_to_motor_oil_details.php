<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add garage/company scoping to motor_oil_details.
     *
     * Previously this table was a global catalog shared by every
     * tenant. That was wrong: each garage manages its own motor oil
     * schedule, because different garages operate different bus
     * fleets (e.g. BMC gas vs YUTONG diesel vs BYD electric) with
     * completely different service intervals.
     */
    public function up(): void
    {
        Schema::table('motor_oil_details', function (Blueprint $table) {
            $table->foreignId('garage_id')
                ->nullable()
                ->after('id')
                ->constrained('garages')
                ->nullOnDelete();

            $table->foreignId('company_id')
                ->nullable()
                ->after('garage_id')
                ->constrained('companies')
                ->nullOnDelete();

            $table->index(['garage_id', 'part_code']);
        });
    }

    public function down(): void
    {
        Schema::table('motor_oil_details', function (Blueprint $table) {
            $table->dropIndex(['garage_id', 'part_code']);
            $table->dropConstrainedForeignId('garage_id');
            $table->dropConstrainedForeignId('company_id');
        });
    }
};
