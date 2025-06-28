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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // المستخدم الذي قام بالنشاط
            $table->string('user_name')->nullable(); // اسم المستخدم (للاحتفاظ به حتى لو تم حذف المستخدم)
            $table->string('activity_type'); // نوع النشاط (إضافة، تعديل، حذف، إلخ)
            $table->string('module'); // القسم أو الوحدة في النظام (المعلمين، الطلاب، الحلقات، الرواتب، إلخ)
            $table->unsignedBigInteger('subject_id')->nullable(); // معرف العنصر المتأثر بالنشاط
            $table->string('subject_type')->nullable(); // نوع العنصر المتأثر (معلم، طالب، حلقة، إلخ)
            $table->text('description'); // وصف النشاط
            $table->json('old_values')->nullable(); // القيم القديمة (قبل التغيير)
            $table->json('new_values')->nullable(); // القيم الجديدة (بعد التغيير)
            $table->string('ip_address')->nullable(); // عنوان IP للمستخدم
            $table->string('user_agent')->nullable(); // معلومات متصفح المستخدم
            $table->timestamps(); // تاريخ ووقت تسجيل النشاط
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
