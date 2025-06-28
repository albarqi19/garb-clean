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
        Schema::create('circle_needs_request_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('circle_needs_requests')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            
            // معلومات النشاط
            $table->enum('activity_type', [
                'إنشاء', 'تحديث', 'تغيير حالة', 'إضافة ملاحظة', 'تأكيد', 'رفض',
                'تخصيص معلم', 'تخصيص مشرف', 'تخصيص مساعد مشرف', 'إلغاء', 'أخرى'
            ]);
            $table->text('description');
            $table->text('old_values')->nullable();
            $table->text('new_values')->nullable();
            
            // معلومات إضافية
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('circle_needs_request_activities');
    }
};