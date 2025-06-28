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
        Schema::table('student_progress', function (Blueprint $table) {
            // حقول التتبع اليومي للمناهج
            $table->json('today_recitation_content')->nullable()->comment('محتوى تسميع اليوم');
            $table->json('tomorrow_recitation_content')->nullable()->comment('محتوى تسميع الغد');
            $table->date('today_date')->nullable()->comment('تاريخ تسميع اليوم');
            $table->date('tomorrow_date')->nullable()->comment('تاريخ تسميع الغد');
            
            // حالة التقدم اليومي
            $table->enum('today_status', ['pending', 'completed', 'partially_completed', 'missed'])
                  ->default('pending')->comment('حالة تسميع اليوم');
            $table->decimal('today_completion_percentage', 5, 2)->default(0)->comment('نسبة إكمال تسميع اليوم');
            
            // ربط بجلسات التسميع
            $table->unsignedBigInteger('last_recitation_session_id')->nullable()->comment('آخر جلسة تسميع');
            $table->foreign('last_recitation_session_id')->references('id')->on('recitation_sessions')->onDelete('set null');
            
            // معلومات إضافية للتتبع
            $table->timestamp('daily_tracking_updated_at')->nullable()->comment('آخر تحديث للتتبع اليومي');
            $table->integer('consecutive_completion_days')->default(0)->comment('أيام الإكمال المتتالية');
            $table->integer('total_missed_days')->default(0)->comment('مجموع الأيام المفقودة');
            
            // فهارس لتحسين الأداء
            $table->index(['student_id', 'today_date']);
            $table->index(['today_status']);
            $table->index(['daily_tracking_updated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_progress', function (Blueprint $table) {
            $table->dropForeign(['last_recitation_session_id']);
            $table->dropColumn([
                'today_recitation_content',
                'tomorrow_recitation_content',
                'today_date',
                'tomorrow_date',
                'today_status',
                'today_completion_percentage',
                'last_recitation_session_id',
                'daily_tracking_updated_at',
                'consecutive_completion_days',
                'total_missed_days'
            ]);
        });
    }
};
