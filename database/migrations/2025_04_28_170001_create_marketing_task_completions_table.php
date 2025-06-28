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
        Schema::create('marketing_task_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketing_task_id')->constrained('marketing_tasks')->cascadeOnDelete()->comment('المهمة التسويقية المرتبطة');
            $table->foreignId('completed_by')->nullable()->constrained('users')->comment('المستخدم الذي أكمل المهمة');
            $table->integer('week_number')->comment('رقم الأسبوع');
            $table->integer('year')->comment('السنة');
            $table->boolean('is_completed')->default(true)->comment('حالة الإنجاز');
            $table->text('notes')->nullable()->comment('ملاحظات الإنجاز');
            $table->dateTime('completion_date')->useCurrent()->comment('تاريخ ووقت الإنجاز');
            $table->timestamps();
            
            $table->unique(['marketing_task_id', 'week_number', 'year'], 'task_week_unique');
            $table->index(['week_number', 'year']);
            $table->index('is_completed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketing_task_completions');
    }
};