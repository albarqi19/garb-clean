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
        Schema::table('recitation_errors', function (Blueprint $table) {
            $table->string('session_id', 50)->after('recitation_session_id')->comment('معرف الجلسة للربط مع جلسة التسميع');
            $table->index('session_id');
            $table->foreign('session_id')->references('session_id')->on('recitation_sessions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recitation_errors', function (Blueprint $table) {
            $table->dropForeign(['session_id']);
            $table->dropIndex(['session_id']);
            $table->dropColumn('session_id');
        });
    }
};
