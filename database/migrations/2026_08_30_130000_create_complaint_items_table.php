<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaint_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complaint_id')->constrained()->cascadeOnDelete();
            $table->text('description');
            $table->string('type')->nullable();
            $table->timestamps();
            $table->index('type');
        });

        DB::table('complaints')->orderBy('id')->each(function (object $complaint) {
            foreach (array_filter(preg_split('/\R/u', (string) $complaint->shikayet)) as $description) {
                DB::table('complaint_items')->insert([
                    'complaint_id' => $complaint->id,
                    'description' => trim($description),
                    'type' => $complaint->sikayet_tipi,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_items');
    }
};
