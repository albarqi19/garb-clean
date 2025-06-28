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
        Schema::create('circle_cost_settings', function (Blueprint $table) {
            $table->id();
            $table->string('role_type')->comment('نوع الدور: معلم، مشرف، مساعد مشرف، الخ');
            $table->string('nationality')->comment('الجنسية: سعودي، غير سعودي');
            $table->string('period')->comment('الفترة: فجر، عصر، مغرب، عشاء، أو جميع الفترات');
            $table->decimal('monthly_cost', 10, 2)->comment('التكلفة الشهرية');
            $table->foreignId('academic_term_id')->nullable()->constrained();
            $table->date('valid_from')->comment('تاريخ بداية سريان هذه الإعدادات');
            $table->date('valid_until')->nullable()->comment('تاريخ نهاية سريان هذه الإعدادات');
            $table->boolean('is_active')->default(true)->comment('هل هذا الإعداد نشط حالياً');
            $table->text('notes')->nullable()->comment('ملاحظات إضافية');
            $table->timestamps();
            
            // مؤشر فريد لضمان عدم تكرار نفس الإعدادات للفترة الزمنية
            $table->unique(['role_type', 'nationality', 'period', 'academic_term_id', 'valid_from'], 'unique_cost_setting');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('circle_cost_settings');
    }
};
