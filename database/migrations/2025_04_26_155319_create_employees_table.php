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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // الاسم
            $table->string('identity_number')->unique(); // رقم الهوية
            $table->string('job_title'); // المسمى الوظيفي
            $table->string('cost_center')->nullable(); // مركز التكلفة (مكان العمل)
            $table->string('association_employee_number')->nullable(); // رقم الموظف في نظام الجمعية الرئيسية
            
            // فترة العمل (يمكن اختيار أكثر من فترة)
            $table->boolean('afternoon_shift')->default(false); // فترة العصر
            $table->boolean('maghrib_shift')->default(false); // فترة المغرب
            $table->boolean('isha_shift')->default(false); // فترة العشاء
            
            $table->string('appointment_decision_link')->nullable(); // قرار التعيين (رابط لملف)
            $table->string('amendment_form_link')->nullable(); // استمارة تعديل/تكليف
            $table->string('circle_permit_link')->nullable(); // تصريح الحلقة
            
            $table->text('branch_notes')->nullable(); // ملاحظات الفرع
            $table->text('hr_notes')->nullable(); // ملاحظات الموارد البشرية
            $table->text('data_entry_notes')->nullable(); // ملاحظات مدخلة البيانات
            
            // معلومات إضافية قد تكون مفيدة
            $table->date('hire_date')->nullable(); // تاريخ التعيين
            $table->string('phone')->nullable(); // رقم الهاتف
            $table->string('email')->nullable(); // البريد الإلكتروني
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
