<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // تحديث عمود job_title ليشمل 'مشرف' كقيمة مسموح بها
        DB::statement("ALTER TABLE teachers MODIFY COLUMN job_title ENUM(
            'معلم حفظ',
            'معلم تلقين',
            'مشرف مقيم',
            'مساعد مشرف مقيم',
            'مشرف'
        )");
        
        // تحديث عمود circle_type ليشمل 'حلقة جماعية' كقيمة مسموح بها
        DB::statement("ALTER TABLE teachers MODIFY COLUMN circle_type ENUM(
            'مدرسة قرآنية',
            'حلقة فردية',
            'تلقين',
            'تحفيظ',
            'حلقة جماعية'
        )");
        
        // تحديث عمود work_time ليشمل 'جميع الفترات' كقيمة مسموح بها
        DB::statement("ALTER TABLE teachers MODIFY COLUMN work_time ENUM(
            'عصر',
            'مغرب',
            'عصر ومغرب',
            'كل الأوقات',
            'جميع الفترات'
        )");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // إعادة الأعمدة إلى قيمها الأصلية
        DB::statement("ALTER TABLE teachers MODIFY COLUMN job_title ENUM(
            'معلم حفظ',
            'معلم تلقين',
            'مشرف مقيم',
            'مساعد مشرف مقيم'
        )");
        
        DB::statement("ALTER TABLE teachers MODIFY COLUMN circle_type ENUM(
            'مدرسة قرآنية',
            'حلقة فردية',
            'تلقين',
            'تحفيظ'
        )");
        
        DB::statement("ALTER TABLE teachers MODIFY COLUMN work_time ENUM(
            'عصر',
            'مغرب',
            'عصر ومغرب',
            'كل الأوقات'
        )");
    }
};
