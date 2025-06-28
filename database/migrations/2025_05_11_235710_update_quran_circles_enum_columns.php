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
        // تعديل عمود circle_type لدعم 'حلقة جماعية' كقيمة مسموح بها
        DB::statement("ALTER TABLE quran_circles MODIFY COLUMN circle_type ENUM('مدرسة قرآنية', 'حلقة فردية', 'حلقة جماعية', 'لم تبدأ بعد')");
        
        // تعديل عمود circle_status لدعم القيم المستخدمة في الواجهة
        DB::statement("ALTER TABLE quran_circles MODIFY COLUMN circle_status ENUM('تعمل', 'متوقفة', 'لم تبدأ بعد', 'نشطة', 'معلقة', 'مغلقة')");
        
        // تعديل عمود time_period لدعم القيم المستخدمة في الواجهة
        DB::statement("ALTER TABLE quran_circles MODIFY COLUMN time_period ENUM('عصر', 'مغرب', 'عصر ومغرب', 'كل الأوقات', 'صباحية', 'مسائية', 'ليلية', 'الفجر')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // إعادة القيم إلى الحالة الأصلية
        DB::statement("ALTER TABLE quran_circles MODIFY COLUMN circle_type ENUM('مدرسة قرآنية', 'حلقة فردية', 'لم تبدأ بعد')");
        DB::statement("ALTER TABLE quran_circles MODIFY COLUMN circle_status ENUM('تعمل', 'متوقفة', 'لم تبدأ بعد')");
        DB::statement("ALTER TABLE quran_circles MODIFY COLUMN time_period ENUM('عصر', 'مغرب', 'عصر ومغرب', 'كل الأوقات')");
    }
};
