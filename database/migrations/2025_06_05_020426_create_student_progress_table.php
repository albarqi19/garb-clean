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
        Schema::create('student_progress', function (Blueprint $table) {
            $table->id();
            
            // ربط بالطالب
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            
            // ربط بخطة المنهج
            $table->foreignId('curriculum_plan_id')->constrained('curriculum_plans')->onDelete('cascade');
            
            // ربط بالمنهج
            $table->foreignId('curriculum_id')->constrained('curricula')->onDelete('cascade');
            
            // بيانات التقدم
            $table->enum('status', [
                'not_started',      // لم يبدأ
                'in_progress',      // قيد التنفيذ
                'completed',        // مكتمل
                'reviewed',         // تم المراجعة
                'mastered',         // متقن
                'needs_revision'    // يحتاج مراجعة
            ])->default('not_started');
            
            // تفاصيل التسميع
            $table->enum('recitation_status', [
                'pending',          // في انتظار التسميع
                'passed',           // نجح في التسميع
                'failed',           // رسب في التسميع
                'partial',          // تسميع جزئي
                'excellent'         // ممتاز
            ])->nullable();
            
            // تقييم الأداء (من 1 إلى 10)
            $table->decimal('performance_score', 3, 1)->nullable();
            
            // عدد محاولات التسميع
            $table->integer('recitation_attempts')->default(0);
            
            // تواريخ مهمة
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_recitation_at')->nullable();
            
            // تفاصيل إضافية
            $table->text('notes')->nullable();
            $table->text('teacher_feedback')->nullable();
            
            // معلومات الحفظ للمحتوى القرآني
            $table->integer('memorized_verses')->default(0);
            $table->decimal('memorization_accuracy', 5, 2)->nullable(); // دقة الحفظ بالنسبة المئوية
            
            // تتبع الوقت المستغرق
            $table->integer('time_spent_minutes')->default(0);
            
            // ربط بالمعلم الذي قيّم التقدم
            $table->foreignId('evaluated_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            
            // فهارس لتحسين الأداء
            $table->index(['student_id', 'curriculum_id']);
            $table->index(['curriculum_plan_id', 'status']);
            $table->index(['recitation_status']);
            $table->index(['started_at', 'completed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_progress');
    }
};
