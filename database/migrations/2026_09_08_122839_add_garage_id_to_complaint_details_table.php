<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaint_details', function (Blueprint $table) {
            $table->foreignId('garage_id')->nullable()->constrained('garages')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('complaint_details', function (Blueprint $table) {
            $table->dropForeign(['garage_id']);
            $table->dropForeign(['company_id']);
            $table->dropColumn(['garage_id', 'company_id']);
        });
    }
};
