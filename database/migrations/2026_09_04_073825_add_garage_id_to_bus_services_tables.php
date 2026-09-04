<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tables = ['bus_service_intervals', 'bus_service_history'];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('garage_id')->nullable()->constrained('garages')->cascadeOnDelete();
                $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            });
        }

        // Mövcud məlumatların itməməsi üçün (avtobusların qaraj ID-lərini bura kopyalayırıq)
        $buses = DB::table('buses')->get();
        foreach($buses as $bus) {
            DB::table('bus_service_intervals')->where('bus_id', $bus->id)
                ->update(['garage_id' => $bus->garage_id, 'company_id' => $bus->company_id]);

            DB::table('bus_service_history')->where('bus_id', $bus->id)
                ->update(['garage_id' => $bus->garage_id, 'company_id' => $bus->company_id]);
        }
    }

    public function down(): void
    {
        $tables = ['bus_service_intervals', 'bus_service_history'];
        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['garage_id']);
                $table->dropForeign(['company_id']);
                $table->dropColumn(['garage_id', 'company_id']);
            });
        }
    }
};
