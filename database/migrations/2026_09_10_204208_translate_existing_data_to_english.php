<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ==================== EMPLOYEES.POSITION ====================
        DB::table('employees')->where('position', 'usta')->update(['position' => 'master']);
        DB::table('employees')->where('position', 'mexanik')->update(['position' => 'mechanic']);
        DB::table('employees')->where('position', 'sürücü')->update(['position' => 'driver']);
        DB::table('employees')->where('position', 'elektrik')->update(['position' => 'electrician']);
        DB::table('employees')->where('position', 'qaynaqci')->update(['position' => 'welder']);
        DB::table('employees')->where('position', 'boyakar')->update(['position' => 'painter']);
        DB::table('employees')->where('position', 'digər')->update(['position' => 'other']);

        // ==================== WAREHOUSES.UNIT ====================
        DB::table('warehouses')->where('unit', 'ədəd')->update(['unit' => 'piece']);
        DB::table('warehouses')->where('unit', 'litr')->update(['unit' => 'liter']);
        DB::table('warehouses')->where('unit', 'metr')->update(['unit' => 'meter']);
        DB::table('warehouses')->where('unit', 'kq')->update(['unit' => 'kg']);
        DB::table('warehouses')->where('unit', 'q')->update(['unit' => 'gram']);

        // ==================== COMPLAINTS.YER ====================
        DB::table('complaints')->where('yer', 'yol')->update(['yer' => 'road']);
        DB::table('complaints')->where('yer', 'qaraj')->update(['yer' => 'garage']);

        // ==================== COMPLAINTS.COMPLAINT_TYPE ====================
        DB::table('complaints')->where('complaint_type', 'qezali')->update(['complaint_type' => 'accident']);
        DB::table('complaints')->where('complaint_type', 'nasazliq')->update(['complaint_type' => 'breakdown']);
        DB::table('complaints')->where('complaint_type', 'texniki_xidmet')->update(['complaint_type' => 'maintenance']);

        // ==================== COMPLAINT_TYPES.NAME ====================
        $typeMap = [
            'Mühərrik səsi'        => 'Engine noise',
            'Şin partlaması'       => 'Tire puncture',
            'Əyləc problemi'       => 'Brake problem',
            'İşıqlandırma nasazlığı' => 'Lighting failure',
            'Transmissiya problemi'=> 'Transmission problem',
            'Süspansiyon problemi' => 'Suspension problem',
            'Elektrik problemi'    => 'Electrical problem',
            'Kondisioner nasazlığı'=> 'Air conditioning failure',
            'Yağ sızması'          => 'Oil leak',
            'Digər'                => 'Other',
        ];

        foreach ($typeMap as $from => $to) {
            DB::table('complaint_types')->where('name', $from)->update(['name' => $to]);
        }
    }

    public function down(): void
    {
        // Reverse if needed
        DB::table('employees')->where('position', 'master')->update(['position' => 'usta']);
        DB::table('employees')->where('position', 'mechanic')->update(['position' => 'mexanik']);
        DB::table('employees')->where('position', 'driver')->update(['position' => 'sürücü']);
        DB::table('employees')->where('position', 'electrician')->update(['position' => 'elektrik']);
        DB::table('employees')->where('position', 'welder')->update(['position' => 'qaynaqci']);
        DB::table('employees')->where('position', 'painter')->update(['position' => 'boyakar']);
        DB::table('employees')->where('position', 'other')->update(['position' => 'digər']);

        DB::table('warehouses')->where('unit', 'piece')->update(['unit' => 'ədəd']);
        DB::table('warehouses')->where('unit', 'liter')->update(['unit' => 'litr']);
        DB::table('warehouses')->where('unit', 'meter')->update(['unit' => 'metr']);
        DB::table('warehouses')->where('unit', 'kg')->update(['unit' => 'kq']);
        DB::table('warehouses')->where('unit', 'gram')->update(['unit' => 'q']);

        DB::table('complaints')->where('yer', 'road')->update(['yer' => 'yol']);
        DB::table('complaints')->where('yer', 'garage')->update(['yer' => 'qaraj']);

        DB::table('complaints')->where('complaint_type', 'accident')->update(['complaint_type' => 'qezali']);
        DB::table('complaints')->where('complaint_type', 'breakdown')->update(['complaint_type' => 'nasazliq']);
        DB::table('complaints')->where('complaint_type', 'maintenance')->update(['complaint_type' => 'texniki_xidmet']);
    }
};