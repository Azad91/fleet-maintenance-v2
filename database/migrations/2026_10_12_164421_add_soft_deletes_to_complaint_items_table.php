<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // database/migrations/xxxx_add_soft_deletes_to_complaint_items_table.php
    public function up(): void
    {
        Schema::table('complaint_items', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('complaint_items', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
