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
        Schema::create('salary_rates', function (Blueprint $table) {
            $table->id();
            
            // الوظيفة والجنسية
            $table->enum('job_title', [
                'مشرف',
                'معلم تحفيظ',
                'معلم تلقين',
                'مساعد مشرف',
                'مساعد معلم'
            ]);
            $table->enum('nationality_type', ['سعودي', 'غير سعودي']);
            
            // معدلات المكافآت للفترات المختلفة
            // فترة الفجر-العصر-العشاء
            $table->decimal('main_periods_daily_rate', 10, 2); // معدل اليوم الواحد للفترات الرئيسية
            $table->decimal('main_periods_monthly_rate', 10, 2); // معدل الشهر (30 يوم) للفترات الرئيسية
            
            // فترة المغرب
            $table->decimal('maghrib_daily_rate', 10, 2); // معدل اليوم الواحد لفترة المغرب
            $table->decimal('maghrib_monthly_rate', 10, 2); // معدل الشهر (30 يوم) لفترة المغرب
            
            // معلومات إضافية
            $table->date('effective_from')->nullable(); // تاريخ بدء تطبيق هذه المعدلات
            $table->date('effective_to')->nullable(); // تاريخ انتهاء تطبيق هذه المعدلات (إذا كان منتهيًا)
            $table->boolean('is_active')->default(true); // هل هذه المعدلات سارية المفعول حاليًا
            $table->text('notes')->nullable(); // ملاحظات إضافية
            
            $table->timestamps();
            
            // يجب أن يكون كل مجموعة (وظيفة + جنسية) فريدة
            $table->unique(['job_title', 'nationality_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_rates');
    }
};
