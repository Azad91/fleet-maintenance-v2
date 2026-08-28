<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            if (!Schema::hasColumn('warehouses', 'garage_id')) {
                $table->foreignId('garage_id')->after('id')->nullable()->constrained('garages')->onDelete('cascade');
            }
            if (!Schema::hasColumn('warehouses', 'company_id')) {
                $table->foreignId('company_id')->after('garage_id')->nullable()->constrained('companies')->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropForeign(['garage_id']);
            $table->dropForeign(['company_id']);
            $table->dropColumn(['garage_id', 'company_id']);
        });
    }
};
