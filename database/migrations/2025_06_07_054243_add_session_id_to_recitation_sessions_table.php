<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('recitation_sessions', function (Blueprint $table) {
            $table->string('session_id', 50)->unique()->after('id')->comment('معرف الجلسة الفريد للربط مع الأخطاء');
            $table->index('session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recitation_sessions', function (Blueprint $table) {
            $table->dropIndex(['session_id']);
            $table->dropColumn('session_id');
        });
    }
};
