<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bus_oil_changes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('garage_id')->constrained('garages')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('bus_id')->constrained('buses')->cascadeOnDelete();

            $table->string('oil_type', 20);
            $table->string('oil_brand', 50)->nullable();
            $table->unsignedInteger('scheduled_km')->nullable();
            $table->unsignedInteger('actual_km');
            $table->unsignedInteger('interval_km');
            $table->date('changed_at')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['garage_id', 'oil_type']);
            $table->index(['bus_id', 'oil_type', 'actual_km']);
        });

        DB::statement("
            ALTER TABLE bus_oil_changes
            ADD CONSTRAINT bus_oil_changes_type_check
            CHECK (oil_type IN ('motor', 'gearbox', 'axle'))
        ");

        DB::statement('
            CREATE UNIQUE INDEX bus_oil_changes_unique_scheduled
            ON bus_oil_changes (bus_id, oil_type, scheduled_km)
            WHERE deleted_at IS NULL AND scheduled_km IS NOT NULL
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('bus_oil_changes');
    }
};
