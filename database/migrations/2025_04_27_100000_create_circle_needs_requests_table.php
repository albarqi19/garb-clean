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
        Schema::create('circle_needs_requests', function (Blueprint $table) {
            $table->id();
            
            // معلومات الحلقة والمسجد
            $table->foreignId('quran_circle_id')->nullable()->constrained()->nullOnDelete();
            $table->string('school_name'); // اسم المدرسة القرآنية
            $table->enum('time_period', ['عصر', 'مغرب', 'فجر', 'عشاء', 'غير محدد']); // الوقت
            $table->string('neighborhood'); // الحي
            
            // الاحتياجات المطلوبة
            $table->unsignedSmallInteger('teachers_needed')->default(0); // احتياج المعلمين
            $table->unsignedSmallInteger('supervisors_needed')->default(0); // احتياج المشرفين
            $table->unsignedSmallInteger('talqeen_teachers_needed')->default(0); // احتياج تلقين
            $table->unsignedSmallInteger('memorization_teachers_needed')->default(0); // احتياج تحفيظ
            $table->unsignedSmallInteger('assistant_supervisors_needed')->default(0); // احتياج مساعد مشرف
            
            // بيانات إحصائية
            $table->unsignedSmallInteger('current_students_count')->default(0); // عدد طلاب المدرسة الحاليين
            $table->unsignedSmallInteger('current_teachers_count')->default(0); // عدد المعلمين في المدرسة الحاليين
            
            // حالة الكفالة والمدرسة
            $table->enum('funding_status', ['متوفر', 'غير متوفر', 'جزئي', 'غير محدد'])->default('غير محدد'); // الكفالة
            $table->enum('school_status', ['متميز', 'جيد', 'متوسط', 'ضعيف', 'جديدة'])->default('جديدة'); // وضع المدرسة
            
            // الإجراء والملاحظات
            $table->enum('action', ['قيد المراجعة', 'تم التنسيق', 'غير مكتمل', 'مرفوض', 'مؤجل', 'مكتمل'])->default('قيد المراجعة'); // الإجراء
            $table->text('notes')->nullable(); // ملاحظات
            
            // معلومات إضافية
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete(); // مقدم الطلب
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete(); // معالج الطلب
            $table->date('approval_date')->nullable(); // تاريخ الموافقة
            $table->date('completion_date')->nullable(); // تاريخ الإكمال
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('circle_needs_requests');
    }
};