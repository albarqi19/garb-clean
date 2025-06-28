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
        Schema::create('recitation_sessions', function (Blueprint $table) {
            $table->id();
            
            // معلومات الطالب والمعلم
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade'); // الطالب
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade'); // المعلم/المقيم
            $table->foreignId('quran_circle_id')->nullable()->constrained('quran_circles')->onDelete('set null'); // الحلقة
            
            // معلومات النطاق القرآني
            $table->unsignedTinyInteger('start_surah_number'); // رقم السورة البداية
            $table->unsignedSmallInteger('start_verse'); // رقم الآية البداية
            $table->unsignedTinyInteger('end_surah_number'); // رقم السورة النهاية
            $table->unsignedSmallInteger('end_verse'); // رقم الآية النهاية
            
            // معلومات التسميع
            $table->enum('recitation_type', ['حفظ', 'مراجعة صغرى', 'مراجعة كبرى']); // نوع التسميع
            $table->unsignedTinyInteger('duration_minutes')->nullable(); // مدة التسميع بالدقائق
            
            // التقييم والنتائج
            $table->decimal('grade', 4, 2); // الدرجة من 10 (مثل 8.50)
            $table->enum('evaluation', ['ممتاز', 'جيد جداً', 'جيد', 'مقبول', 'ضعيف']); // التقدير التلقائي
            $table->text('teacher_notes')->nullable(); // ملاحظات المعلم
            
            // معلومات إضافية
            $table->boolean('has_errors')->default(false); // هل توجد أخطاء
            $table->unsignedSmallInteger('total_verses')->nullable(); // إجمالي عدد الآيات المسمعة
            
            $table->timestamps();
            
            // فهارس لتحسين الأداء
            $table->index(['student_id', 'created_at']);
            $table->index('recitation_type');
            $table->index('grade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recitation_sessions');
    }
};
