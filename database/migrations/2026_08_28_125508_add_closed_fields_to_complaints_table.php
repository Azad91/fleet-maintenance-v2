<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->timestamp('closed_at')->nullable()->after('status');
            $table->foreignId('closed_by')->nullable()->constrained('users')->onDelete('set null')->after('closed_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null')->after('id');
        });
    }

    public function down()
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropForeign(['closed_by']);
            $table->dropForeign(['created_by']);
            $table->dropColumn(['closed_at', 'closed_by', 'created_by']);
        });
    }
};
