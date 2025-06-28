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
        Schema::create('supervisor_visits', function (Blueprint $table) {
            $table->id();
            
            // معلومات المشرف والحلقة
            $table->foreignId('supervisor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('quran_circle_id')->constrained()->onDelete('cascade');
            
            // معلومات الزيارة
            $table->dateTime('visit_date'); // تاريخ ووقت الزيارة
            $table->enum('visit_status', ['مخطط', 'تمت الزيارة', 'ملغية'])->default('مخطط'); // حالة الزيارة
            $table->text('notes')->nullable(); // ملاحظات عامة
            
            // معلومات تقييم الزيارة
            $table->unsignedTinyInteger('circle_rating')->nullable(); // تقييم الحلقة من 40
            $table->unsignedSmallInteger('students_count')->nullable(); // عدد طلاب الحلقة حسب المرفوع في الميدان
            $table->unsignedSmallInteger('exam_students_count')->nullable(); // عدد الطلاب الذين دخلوا الاختبار حسب قياس
            $table->unsignedSmallInteger('passed_students_count')->nullable(); // عدد الناجحين
            $table->unsignedSmallInteger('memorized_parts_count')->nullable(); // عدد الأجزاء المحفوظة
            $table->unsignedSmallInteger('reviewed_parts_count')->nullable(); // عدد الأجزاء المراجعة
            $table->boolean('ratel_activated')->default(false); // هل تم تفعيل رتل
            
            // الجهة التي تتبعها الزيارة (مثل دورة تقييم أو زيارة عادية)
            $table->string('visit_type')->nullable();
            $table->foreignId('evaluation_period_id')->nullable(); // فترة التقييم (إذا كانت الزيارة جزء من دورة تقييم)
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supervisor_visits');
    }
};