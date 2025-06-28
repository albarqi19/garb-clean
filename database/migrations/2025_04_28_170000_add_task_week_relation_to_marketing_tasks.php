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
        Schema::table('marketing_tasks', function (Blueprint $table) {
            // إضافة مفتاح خارجي لربط المهمة بجدول الأسابيع بشكل مباشر
            $table->foreignId('marketing_task_week_id')->nullable()->after('id')
                  ->constrained('marketing_task_weeks')
                  ->nullOnDelete()
                  ->comment('المرجع المباشر لأسبوع المهمة التسويقية');
                  
            // إنشاء فهرس للبحث السريع
            $table->index('marketing_task_week_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketing_tasks', function (Blueprint $table) {
            $table->dropForeign(['marketing_task_week_id']);
            $table->dropIndex(['marketing_task_week_id']);
            $table->dropColumn('marketing_task_week_id');
        });
    }
};