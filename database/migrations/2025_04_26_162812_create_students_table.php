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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('identity_number')->unique(); // رقم الهوية
            $table->string('name'); // اسم الطالب
            $table->string('nationality'); // الجنسية
            $table->date('birth_date')->nullable(); // تاريخ الميلاد
            $table->string('phone')->nullable(); // رقم التواصل
            
            // العلاقات
            $table->foreignId('quran_circle_id')->nullable()->constrained()->onDelete('set null'); // الحلقة التي ينتمي إليها
            $table->foreignId('mosque_id')->nullable()->constrained()->onDelete('set null'); // المسجد
            $table->string('neighborhood')->nullable(); // الحي
            
            $table->date('enrollment_date')->nullable(); // تاريخ الالتحاق
            $table->integer('absence_count')->default(0); // عدد الغياب
            $table->integer('parts_count')->default(0); // عدد الأجزاء المحفوظة
            $table->string('last_exam')->nullable(); // آخر اختبار تم اجتيازه
            
            // سيتم ربطها بجداول الخطط لاحقاً
            $table->string('memorization_plan')->nullable(); // خطة الحفظ
            $table->string('review_plan')->nullable(); // خطة المراجعة
            
            $table->text('teacher_notes')->nullable(); // ملاحظات المعلم
            $table->text('supervisor_notes')->nullable(); // ملاحظات المشرف
            $table->text('center_notes')->nullable(); // ملاحظات المركز
            
            // معلومات إضافية قد تكون مفيدة
            $table->string('guardian_name')->nullable(); // اسم ولي الأمر
            $table->string('guardian_phone')->nullable(); // رقم هاتف ولي الأمر
            $table->string('education_level')->nullable(); // المستوى التعليمي
            $table->boolean('is_active')->default(true); // هل الطالب نشط أم لا
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
