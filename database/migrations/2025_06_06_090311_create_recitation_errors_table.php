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
        Schema::create('recitation_errors', function (Blueprint $table) {
            $table->id();
            
            // ربط الخطأ بجلسة التسميع
            $table->foreignId('recitation_session_id')->constrained('recitation_sessions')->onDelete('cascade');
            
            // موقع الخطأ في القرآن
            $table->unsignedTinyInteger('surah_number'); // رقم السورة
            $table->unsignedSmallInteger('verse_number'); // رقم الآية
            $table->string('word_text'); // الكلمة التي تم رصد الخطأ عليها
            
            // تصنيف الخطأ
            $table->enum('error_type', ['نطق', 'تجويد', 'حفظ', 'ترتيل', 'وقف وابتداء', 'أخرى']); // نوع الخطأ
            $table->text('correction_note')->nullable(); // ملاحظة التصحيح
            $table->text('teacher_note')->nullable(); // ملاحظة المعلم حول الخطأ
            
            // معلومات إضافية
            $table->boolean('is_repeated')->default(false); // هل هو خطأ متكرر للطالب
            $table->enum('severity_level', ['خفيف', 'متوسط', 'شديد'])->default('متوسط'); // مستوى الخطأ
            
            $table->timestamps();
            
            // فهارس لتحسين الأداء
            $table->index(['recitation_session_id']);
            $table->index(['surah_number', 'verse_number']);
            $table->index('error_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recitation_errors');
    }
};
