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
        Schema::create('teacher_transfer_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained(); // المعلم مقدم الطلب
            $table->foreignId('current_circle_id')->constrained('quran_circles'); // الحلقة الحالية
            $table->foreignId('requested_circle_id')->nullable()->constrained('quran_circles'); // الحلقة المطلوب النقل إليها
            $table->foreignId('current_mosque_id')->nullable()->constrained('mosques'); // المسجد الحالي
            $table->foreignId('requested_mosque_id')->nullable()->constrained('mosques'); // المسجد المطلوب النقل إليه
            $table->string('requested_neighborhood')->nullable(); // الحي المطلوب النقل إليه (في حالة عدم تحديد مسجد بعينه)
            $table->date('request_date'); // تاريخ تقديم الطلب
            $table->enum('preferred_time', ['العصر', 'المغرب', 'العشاء', 'عصر - مغرب', 'مغرب - عشاء'])->nullable(); // الوقت المفضل للعمل بعد النقل
            $table->enum('status', [
                'قيد المراجعة',
                'موافقة مبدئية',
                'موافقة نهائية',
                'مرفوض',
                'ملغي',
                'تم النقل'
            ])->default('قيد المراجعة'); // حالة الطلب
            $table->text('transfer_reason'); // سبب طلب النقل
            $table->text('notes')->nullable(); // ملاحظات إضافية
            $table->date('response_date')->nullable(); // تاريخ الرد على الطلب
            $table->text('response_notes')->nullable(); // ملاحظات الرد
            $table->foreignId('approved_by')->nullable()->constrained('users'); // الشخص الذي وافق على الطلب
            $table->date('transfer_date')->nullable(); // تاريخ النقل الفعلي
            $table->boolean('has_appointment_decision')->default(false); // هل يوجد قرار تعيين
            $table->string('appointment_decision_number')->nullable(); // رقم قرار التعيين إن وجد
            $table->timestamps();
        });

        // جدول لتتبع مراحل معالجة الطلب
        Schema::create('teacher_transfer_request_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_request_id')->constrained('teacher_transfer_requests')->onDelete('cascade');
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
        Schema::dropIfExists('teacher_transfer_request_activities');
        Schema::dropIfExists('teacher_transfer_requests');
    }
};
