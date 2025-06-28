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
            // إضافة حالة الجلسة
            $table->enum('status', ['جارية', 'مكتملة', 'غير مكتملة'])
                  ->default('جارية')
                  ->after('evaluation')
                  ->comment('حالة جلسة التسميع');
            
            // إضافة معرف المنهج
            $table->unsignedBigInteger('curriculum_id')
                  ->nullable()
                  ->after('quran_circle_id')
                  ->comment('معرف المنهج المرتبط بالجلسة');
            
            // إضافة تاريخ إكمال الجلسة
            $table->timestamp('completed_at')
                  ->nullable()
                  ->after('updated_at')
                  ->comment('تاريخ إكمال الجلسة');
            
            // إضافة الفهارس
            $table->index('status');
            $table->index('curriculum_id');
            $table->index('completed_at');
            
            // إضافة المفاتيح الخارجية
            $table->foreign('curriculum_id')
                  ->references('id')
                  ->on('curricula')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recitation_sessions', function (Blueprint $table) {
            $table->dropForeign(['curriculum_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['curriculum_id']);
            $table->dropIndex(['completed_at']);
            $table->dropColumn(['status', 'curriculum_id', 'completed_at']);
        });
    }
};
