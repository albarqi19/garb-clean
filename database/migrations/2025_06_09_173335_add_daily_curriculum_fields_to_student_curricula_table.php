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
        Schema::table('student_curricula', function (Blueprint $table) {
            // إعدادات المنهج اليومي
            $table->integer('daily_memorization_pages')->default(1)->comment('عدد صفحات الحفظ اليومية');
            $table->integer('daily_minor_review_pages')->default(5)->comment('عدد صفحات المراجعة الصغرى اليومية');
            $table->integer('daily_major_review_pages')->default(20)->comment('عدد صفحات المراجعة الكبرى اليومية');
            
            // تتبع الموقع الحالي
            $table->integer('current_page')->nullable()->comment('الصفحة الحالية في المنهج');
            $table->string('current_surah')->nullable()->comment('السورة الحالية');
            $table->integer('current_ayah')->nullable()->comment('الآية الحالية');
            
            // تواريخ التتبع
            $table->date('last_progress_date')->nullable()->comment('آخر تاريخ تقدم');
            $table->integer('consecutive_days')->default(0)->comment('عدد الأيام المتتالية المكتملة');
            
            // إعدادات إضافية
            $table->boolean('is_active')->default(true)->comment('هل المنهج نشط');
            $table->json('daily_goals')->nullable()->comment('أهداف يومية مخصصة (JSON)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_curricula', function (Blueprint $table) {
            $table->dropColumn([
                'daily_memorization_pages',
                'daily_minor_review_pages', 
                'daily_major_review_pages',
                'current_page',
                'current_surah',
                'current_ayah',
                'last_progress_date',
                'consecutive_days',
                'is_active',
                'daily_goals'
            ]);
        });
    }
};
