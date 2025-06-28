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
        Schema::create('marketing_activities', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // عنوان النشاط التسويقي
            $table->enum('type', [
                'منشور إعلامي',
                'رسالة للداعمين',
                'تقرير',
                'مشروع متجر إلكتروني',
                'مشروع مؤسسة مانحة',
                'اجتماع',
                'أخرى'
            ]); // نوع النشاط
            $table->text('description')->nullable(); // وصف النشاط
            $table->date('activity_date'); // تاريخ النشاط
            $table->string('target_audience')->nullable(); // الجمهور المستهدف
            $table->enum('status', [
                'مخطط',
                'قيد التنفيذ',
                'مكتمل',
                'ملغي'
            ])->default('مخطط'); // حالة النشاط
            $table->string('platform')->nullable(); // المنصة المستخدمة (إذا كان منشور إعلامي)
            $table->integer('reach_count')->nullable(); // عدد الوصول (إذا كان منشور إعلامي)
            $table->integer('interaction_count')->nullable(); // عدد التفاعلات (إذا كان منشور إعلامي)
            $table->string('file_attachment')->nullable(); // مرفق ملف (إن وجد)
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); // من قام بالنشاط
            $table->text('notes')->nullable(); // ملاحظات
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketing_activities');
    }
};