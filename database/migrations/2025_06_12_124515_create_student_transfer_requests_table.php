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
        Schema::create('student_transfer_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained(); // الطالب المراد نقله
            $table->foreignId('current_circle_id')->constrained('quran_circles'); // الحلقة الحالية
            $table->foreignId('current_circle_group_id')->nullable()->constrained('circle_groups'); // مجموعة الحلقة الحالية
            $table->foreignId('requested_circle_id')->nullable()->constrained('quran_circles'); // الحلقة المطلوب النقل إليها
            $table->foreignId('requested_circle_group_id')->nullable()->constrained('circle_groups'); // مجموعة الحلقة المطلوبة
            $table->string('requested_neighborhood')->nullable(); // الحي المطلوب النقل إليه
            $table->date('request_date'); // تاريخ تقديم الطلب
            $table->enum('status', [
                'pending',
                'in_progress', 
                'approved',
                'rejected',
                'completed'
            ])->default('pending'); // حالة الطلب
            $table->text('transfer_reason'); // سبب طلب النقل
            $table->text('notes')->nullable(); // ملاحظات إضافية
            $table->foreignId('requested_by')->constrained('users'); // المشرف الذي قدم الطلب
            $table->date('response_date')->nullable(); // تاريخ الرد على الطلب
            $table->text('response_notes')->nullable(); // ملاحظات الرد
            $table->foreignId('approved_by')->nullable()->constrained('users'); // الشخص الذي وافق على الطلب
            $table->date('transfer_date')->nullable(); // تاريخ النقل الفعلي
            $table->timestamps();
        });

        // جدول لتتبع مراحل معالجة الطلب
        Schema::create('student_transfer_request_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_request_id')->constrained('student_transfer_requests')->onDelete('cascade');
            $table->enum('activity_type', [
                'تقديم الطلب',
                'مراجعة الطلب',
                'تعديل الطلب',
                'موافقة مبدئية',
                'موافقة نهائية',
                'رفض الطلب',
                'إلغاء الطلب',
                'تنفيذ النقل'
            ]); // نوع النشاط
            $table->foreignId('user_id')->nullable()->constrained(); // الشخص الذي قام بالنشاط
            $table->string('activity_role')->nullable(); // دور الشخص الذي قام بالنشاط (مشرف، مدير، إلخ)
            $table->text('notes')->nullable(); // ملاحظات حول النشاط
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_transfer_request_activities');
        Schema::dropIfExists('student_transfer_requests');
    }
};
