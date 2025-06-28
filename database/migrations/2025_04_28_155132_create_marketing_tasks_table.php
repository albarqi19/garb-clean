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
        Schema::create('marketing_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title')->comment('عنوان المهمة التسويقية');
            $table->text('description')->nullable()->comment('وصف تفصيلي للمهمة');
            $table->string('schedule_type')->comment('نمط الجدولة: يومياً، أسبوعياً، مرة واحدة، مرتين، إلخ');
            $table->string('day_of_week')->nullable()->comment('يوم الأسبوع للمهمة: الخميس، الجمعة، إلخ');
            $table->string('time_of_day')->nullable()->comment('وقت اليوم: عصر، ظهر، يومياً، إلخ');
            
            // المسؤول عن المهمة والوسيلة
            $table->foreignId('assigned_to')->nullable()->constrained('users')->comment('المستخدم المسؤول عن المهمة');
            $table->string('channel')->nullable()->comment('الوسيلة المستخدمة: واتس، نصية، إكسل، تويتر، إلخ');
            
            // تتبع الإنجاز
            $table->json('completion_dates')->nullable()->comment('تواريخ إنجاز المهمة لكل أسبوع');
            $table->boolean('is_recurring')->default(true)->comment('هل المهمة متكررة');
            $table->boolean('is_active')->default(true)->comment('هل المهمة نشطة');
            
            // الأولوية والتصنيف
            $table->string('priority')->default('normal')->comment('أولوية المهمة: منخفضة، عادية، عالية، عاجلة');
            $table->string('category')->default('marketing')->comment('تصنيف المهمة: تسويق، إعلام، متابعة، إلخ');
            
            // التقييم والملاحظات
            $table->text('notes')->nullable()->comment('ملاحظات حول المهمة');
            $table->foreignId('created_by')->constrained('users')->comment('المستخدم الذي أنشأ المهمة');
            
            // الأسبوع والسنة
            $table->integer('week_number')->nullable()->comment('رقم الأسبوع في السنة');
            $table->integer('year')->nullable()->comment('السنة');
            
            $table->timestamps();
            $table->softDeletes();
            
            // فهارس للبحث السريع
            $table->index(['assigned_to', 'is_active']);
            $table->index(['week_number', 'year']);
            $table->index('schedule_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketing_tasks');
    }
};
