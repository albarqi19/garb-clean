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
        Schema::table('teachers', function (Blueprint $table) {
            // إضافة فهرس للعلاقة بين المعلمين والمدارس القرآنية
            if (Schema::hasColumn('teachers', 'quran_circle_id')) {
                $table->index('quran_circle_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            if (Schema::hasColumn('teachers', 'quran_circle_id')) {
                $table->dropIndex(['quran_circle_id']);
            }
        });
    }
};
