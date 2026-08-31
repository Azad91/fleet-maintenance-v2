<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_log_archive', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('garage_id')->nullable();
            $table->foreignId('company_id')->nullable();
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->string('event');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('archived_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_log_archive');
    }
};
