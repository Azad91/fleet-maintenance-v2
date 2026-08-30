<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('garage_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->string('event');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['auditable_type', 'auditable_id']);
        });

        foreach (['buses', 'complaints', 'warehouses', 'employees', 'drivers', 'daily_km_records', 'bus_daily_statuses'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Köhnə və istifadə olunmayan cədvəl yoxlanılıb boş olduğu üçün silinir.
        Schema::dropIfExists('daily_kms');
    }

    public function down(): void
    {
        Schema::create('daily_kms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bus_id')->constrained()->onDelete('cascade');
            $table->date('tarix');
            $table->integer('km')->unsigned();
            $table->string('qeyd')->nullable();
            $table->timestamps();
        });

        foreach (['buses', 'complaints', 'warehouses', 'employees', 'drivers', 'daily_km_records', 'bus_daily_statuses'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        Schema::dropIfExists('audit_logs');
    }
};
