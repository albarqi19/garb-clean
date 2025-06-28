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
        Schema::create('circle_request_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('circle_opening_requests')->onDelete('cascade');
            
            // معلومات مراحل القسم التسويقي
            $table->enum('marketing_status', ['لم يبدأ', 'قيد المعالجة', 'مكتمل', 'مرفوض'])->default('لم يبدأ');
            $table->boolean('mosque_verification_done')->default(false); // التحقق من وجود المسجد
            $table->boolean('surrounding_circles_identified')->default(false); // تحديد الحلقات المجاورة
            $table->boolean('store_link_created')->default(false); // إنشاء رابط المتجر
            $table->boolean('donor_search_done')->default(false); // البحث عن داعم
            $table->text('marketing_notes')->nullable(); // ملاحظات القسم التسويقي
            $table->foreignId('marketing_user_id')->nullable()->constrained('users')->nullOnDelete(); // المسؤول عن المراجعة في قسم التسويق
            $table->timestamp('marketing_completed_at')->nullable(); // تاريخ اكتمال مراجعة التسويق
            
            // معلومات مراحل القسم الإداري
            $table->enum('administrative_status', ['لم يبدأ', 'قيد المعالجة', 'مكتمل', 'مرفوض'])->default('لم يبدأ');
            $table->boolean('budget_confirmation')->default(false); // تأكيد الميزانية
            $table->boolean('documents_completed')->default(false); // تجهيز المستندات
            $table->boolean('teacher_contracts_prepared')->default(false); // تجهيز عقود المعلمين
            $table->text('administrative_notes')->nullable(); // ملاحظات القسم الإداري
            $table->foreignId('administrative_user_id')->nullable()->constrained('users')->nullOnDelete(); // المسؤول عن المراجعة في القسم الإداري
            $table->timestamp('administrative_completed_at')->nullable(); // تاريخ اكتمال المراجعة الإدارية
            
            // معلومات مراحل القسم التعليمي
            $table->enum('educational_status', ['لم يبدأ', 'قيد المعالجة', 'مكتمل', 'مرفوض'])->default('لم يبدأ');
            $table->boolean('supervisor_assigned')->default(false); // تعيين مشرف للحلقة
            $table->boolean('teachers_assigned')->default(false); // تعيين معلمين للحلقة
            $table->boolean('educational_plan_ready')->default(false); // تجهيز الخطة التعليمية
            $table->text('educational_notes')->nullable(); // ملاحظات القسم التعليمي
            $table->foreignId('educational_user_id')->nullable()->constrained('users')->nullOnDelete(); // المسؤول عن المراجعة في القسم التعليمي
            $table->timestamp('educational_completed_at')->nullable(); // تاريخ اكتمال المراجعة التعليمية
            
            // معلومات إنشاء الحلقة
            $table->boolean('circle_created')->default(false); // هل تم إنشاء الحلقة في النظام
            $table->foreignId('created_circle_id')->nullable()->constrained('quran_circles')->nullOnDelete(); // رقم الحلقة المنشأة (إذا تم إنشاؤها)
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('circle_request_stages');
    }
};