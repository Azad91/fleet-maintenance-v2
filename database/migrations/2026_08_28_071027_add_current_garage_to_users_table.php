<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('current_garage_id')->nullable()->constrained('garages')->onDelete('set null');
            $table->foreignId('current_company_id')->nullable()->constrained('companies')->onDelete('set null');
            $table->timestamp('last_selected_garage_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['current_garage_id']);
            $table->dropForeign(['current_company_id']);
            $table->dropColumn(['current_garage_id', 'current_company_id', 'last_selected_garage_at']);
        });
    }
};
