<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ==================== BUSES ====================
        Schema::table('buses', function (Blueprint $table) {
            $table->renameColumn('xett_no', 'route_number');
            $table->renameColumn('motor_no', 'engine_number');
            $table->renameColumn('tarix', 'date');
            $table->renameColumn('aktiv', 'is_active');
        });

        // ==================== COMPLAINTS ====================
        Schema::table('complaints', function (Blueprint $table) {
            $table->renameColumn('surucu_adi', 'driver_name');
            $table->renameColumn('sikayet_tipi', 'complaint_type');
            $table->renameColumn('bildirilme_tarix', 'reported_date');
            $table->renameColumn('bildirilme_saat', 'reported_time');
            $table->renameColumn('is_baslama_tarix', 'start_date');
            $table->renameColumn('is_baslama_saat', 'start_time');
            $table->renameColumn('is_bitme_tarix', 'end_date');
            $table->renameColumn('is_bitme_saat', 'end_time');
            $table->renameColumn('kim_is_gorub', 'work_done_by');
            $table->renameColumn('qeyd', 'notes');
            // 'yer' – sonra dəyişərik (location)
        });

        // ==================== DAILY_KM_RECORDS ====================
        Schema::table('daily_km_records', function (Blueprint $table) {
            $table->renameColumn('tarix', 'date');
            $table->renameColumn('qeyd', 'notes');
        });

        // ==================== BUS_DAILY_STATUSES ====================
        Schema::table('bus_daily_statuses', function (Blueprint $table) {
            $table->renameColumn('tarix', 'date');
            $table->renameColumn('qeyd', 'notes');
        });

        // ==================== EMPLOYEES ====================
        Schema::table('employees', function (Blueprint $table) {
            $table->renameColumn('ad', 'first_name');
            $table->renameColumn('soyad', 'last_name');
            $table->renameColumn('vezifesi', 'position');
            $table->renameColumn('qeyd', 'notes');
            $table->renameColumn('aktiv', 'is_active');
        });

        // ==================== DRIVERS ====================
        Schema::table('drivers', function (Blueprint $table) {
            $table->renameColumn('kodu', 'code');
            $table->renameColumn('ad', 'first_name');
            $table->renameColumn('soyad', 'last_name');
            $table->renameColumn('vezifesi', 'position');
            $table->renameColumn('qeyd', 'notes');
            $table->renameColumn('aktiv', 'is_active');
            $table->renameColumn('telefon', 'phone');
        });

        // ==================== WAREHOUSES ====================
        Schema::table('warehouses', function (Blueprint $table) {
            $table->renameColumn('kod', 'code');
            $table->renameColumn('ad', 'name');
            $table->renameColumn('kateqoriya', 'category');
            $table->renameColumn('olcu_vahidi', 'unit');
            $table->renameColumn('miqdar', 'quantity');
            $table->renameColumn('minimum_miqdar', 'minimum_quantity');
            $table->renameColumn('qiymet', 'price');
            $table->renameColumn('tedarikci', 'supplier');
            $table->renameColumn('qeyd', 'notes');
        });

        // ==================== COMPLAINT_DETAILS ====================
        Schema::table('complaint_details', function (Blueprint $table) {
            $table->renameColumn('kodu', 'code');
            $table->renameColumn('adi', 'name');
            $table->renameColumn('depo_miqdari', 'stock_quantity');
            $table->renameColumn('islenen_miqdar', 'used_quantity');
            $table->renameColumn('qeyd', 'notes');
        });

        // ==================== MOTOR_OIL_DETAILS ====================
        Schema::table('motor_oil_details', function (Blueprint $table) {
            $table->renameColumn('detal_kodu', 'part_code');
            $table->renameColumn('detal_adi', 'part_name');
            $table->renameColumn('olcu_vahidi', 'unit');
            $table->renameColumn('miqdar', 'quantity');
            $table->renameColumn('say', 'count');
        });
    }

    public function down(): void
    {
        // ==================== BUSES ====================
        Schema::table('buses', function (Blueprint $table) {
            $table->renameColumn('route_number', 'xett_no');
            $table->renameColumn('engine_number', 'motor_no');
            $table->renameColumn('date', 'tarix');
            $table->renameColumn('is_active', 'aktiv');
        });

        // ==================== COMPLAINTS ====================
        Schema::table('complaints', function (Blueprint $table) {
            $table->renameColumn('driver_name', 'surucu_adi');
            $table->renameColumn('complaint_type', 'sikayet_tipi');
            $table->renameColumn('reported_date', 'bildirilme_tarix');
            $table->renameColumn('reported_time', 'bildirilme_saat');
            $table->renameColumn('start_date', 'is_baslama_tarix');
            $table->renameColumn('start_time', 'is_baslama_saat');
            $table->renameColumn('end_date', 'is_bitme_tarix');
            $table->renameColumn('end_time', 'is_bitme_saat');
            $table->renameColumn('work_done_by', 'kim_is_gorub');
            $table->renameColumn('notes', 'qeyd');
        });

        // ==================== DAILY_KM_RECORDS ====================
        Schema::table('daily_km_records', function (Blueprint $table) {
            $table->renameColumn('date', 'tarix');
            $table->renameColumn('notes', 'qeyd');
        });

        // ==================== BUS_DAILY_STATUSES ====================
        Schema::table('bus_daily_statuses', function (Blueprint $table) {
            $table->renameColumn('date', 'tarix');
            $table->renameColumn('notes', 'qeyd');
        });

        // ==================== EMPLOYEES ====================
        Schema::table('employees', function (Blueprint $table) {
            $table->renameColumn('first_name', 'ad');
            $table->renameColumn('last_name', 'soyad');
            $table->renameColumn('position', 'vezifesi');
            $table->renameColumn('notes', 'qeyd');
            $table->renameColumn('is_active', 'aktiv');
        });

        // ==================== DRIVERS ====================
        Schema::table('drivers', function (Blueprint $table) {
            $table->renameColumn('code', 'kodu');
            $table->renameColumn('first_name', 'ad');
            $table->renameColumn('last_name', 'soyad');
            $table->renameColumn('position', 'vezifesi');
            $table->renameColumn('notes', 'qeyd');
            $table->renameColumn('is_active', 'aktiv');
            $table->renameColumn('phone', 'telefon');
        });

        // ==================== WAREHOUSES ====================
        Schema::table('warehouses', function (Blueprint $table) {
            $table->renameColumn('code', 'kod');
            $table->renameColumn('name', 'ad');
            $table->renameColumn('category', 'kateqoriya');
            $table->renameColumn('unit', 'olcu_vahidi');
            $table->renameColumn('quantity', 'miqdar');
            $table->renameColumn('minimum_quantity', 'minimum_miqdar');
            $table->renameColumn('price', 'qiymet');
            $table->renameColumn('supplier', 'tedarikci');
            $table->renameColumn('notes', 'qeyd');
        });

        // ==================== COMPLAINT_DETAILS ====================
        Schema::table('complaint_details', function (Blueprint $table) {
            $table->renameColumn('code', 'kodu');
            $table->renameColumn('name', 'adi');
            $table->renameColumn('stock_quantity', 'depo_miqdari');
            $table->renameColumn('used_quantity', 'islenen_miqdar');
            $table->renameColumn('notes', 'qeyd');
        });

        // ==================== MOTOR_OIL_DETAILS ====================
        Schema::table('motor_oil_details', function (Blueprint $table) {
            $table->renameColumn('part_code', 'detal_kodu');
            $table->renameColumn('part_name', 'detal_adi');
            $table->renameColumn('unit', 'olcu_vahidi');
            $table->renameColumn('quantity', 'miqdar');
            $table->renameColumn('count', 'say');
        });
    }
};
