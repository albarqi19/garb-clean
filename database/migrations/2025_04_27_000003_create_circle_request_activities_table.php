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
        Schema::create('circle_request_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('circle_opening_requests')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // المستخدم الذي قام بالنشاط
            
            // معلومات النشاط
            $table->enum('department', ['التسويق', 'الإدارية', 'التعليمية', 'النظام']); // القسم المسؤول عن النشاط
            $table->enum('activity_type', [
                'إنشاء', 'تحديث', 'تغيير حالة', 'إضافة ملاحظة', 'تأكيد', 'رفض',
                'إنشاء رابط المتجر', 'تعيين مشرف', 'تعيين معلمين', 'تأكيد ميزانية',
                'إنشاء حلقة', 'إضافة داعم', 'تأجيل', 'إلغاء', 'أخرى'
            ]); // نوع النشاط
            $table->text('description'); // وصف النشاط
            $table->text('old_values')->nullable(); // القيم القديمة (JSON)
            $table->text('new_values')->nullable(); // القيم الجديدة (JSON)
            
            // معلومات إضافية
            $table->string('ip_address', 45)->nullable(); // عنوان IP للمستخدم
            $table->string('user_agent')->nullable(); // معلومات متصفح المستخدم
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('circle_request_activities');
    }
};