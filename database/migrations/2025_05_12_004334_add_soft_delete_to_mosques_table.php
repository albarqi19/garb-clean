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
        Schema::table('mosques', function (Blueprint $table) {
            // إضافة عمود deleted_at للحذف الناعم
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mosques', function (Blueprint $table) {
            // حذف عمود deleted_at عند التراجع عن الترحيل
            $table->dropSoftDeletes();
        });
    }
};
