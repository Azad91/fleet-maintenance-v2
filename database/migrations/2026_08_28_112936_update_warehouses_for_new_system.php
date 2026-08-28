<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            // Köhnə sahələri sil (artıq stock_balances-də saxlanılacaq)
            $table->dropColumn(['miqdar', 'minimum_miqdar', 'qiymet', 'tedarikci']);

            // Yeni sahələr
            $table->string('code')->unique()->after('id');
            $table->string('type')->default('garage')->after('name'); // central, garage, mobile
            $table->boolean('is_active')->default(true)->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->integer('miqdar')->default(0);
            $table->integer('minimum_miqdar')->default(0);
            $table->decimal('qiymet', 10, 2)->nullable();
            $table->string('tedarikci')->nullable();
            $table->dropColumn(['code', 'type', 'is_active']);
        });
    }
};
