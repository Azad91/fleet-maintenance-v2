<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Complaint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaint_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complaint_id')->constrained()->onDelete('cascade');
            $table->integer('shikayet_index')->default(0);
            $table->string('kodu')->nullable();
            $table->string('adi')->nullable();
            $table->integer('depo_miqdari')->nullable();
            $table->integer('islenen_miqdar')->default(0);
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->text('qeyd')->nullable();
            $table->timestamps();

            $table->index('complaint_id');
            $table->index('kodu');
            $table->index('employee_id');
        });

        // 🔥 JSON-dan məlumatları köçür
        $complaints = Complaint::all();
        foreach ($complaints as $complaint) {
            $detallar = is_string($complaint->detallar)
                ? json_decode($complaint->detallar, true)
                : $complaint->detallar;

            if (is_array($detallar)) {
                foreach ($detallar as $detal) {
                    DB::table('complaint_details')->insert([
                        'complaint_id' => $complaint->id,
                        'shikayet_index' => $detal['shikayet_index'] ?? 0,
                        'kodu' => $detal['kodu'] ?? null,
                        'adi' => $detal['adi'] ?? null,
                        'depo_miqdari' => $detal['depo_miqdari'] ?? null,
                        'islenen_miqdar' => $detal['islenen_miqdar'] ?? 0,
                        'employee_id' => $detal['employee_id'] ?? null,
                        'qeyd' => $detal['qeyd'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_details');
    }
};
