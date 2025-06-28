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
        Schema::create('teacher_evaluations', function (Blueprint $table) {
            $table->id();
            
            // ربط بالمعلم
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            
            // معايير التقييم (كل معيار من 20 نقطة)
            $table->decimal('performance_score', 3, 1)->default(0)->comment('تقييم الأداء (0-20)');
            $table->decimal('attendance_score', 3, 1)->default(0)->comment('تقييم الالتزام بالحضور (0-20)');
            $table->decimal('student_interaction_score', 3, 1)->default(0)->comment('تقييم التفاعل مع الطلاب (0-20)');
            $table->decimal('behavior_cooperation_score', 3, 1)->default(0)->comment('تقييم السمت والتعاون (0-20)');
            $table->decimal('memorization_recitation_score', 3, 1)->default(0)->comment('تقييم الحفظ والتلاوة (0-20)');
            $table->decimal('general_evaluation_score', 3, 1)->default(0)->comment('التقييم العام (0-20)');
            
            // النتيجة الإجمالية (مجموع المعايير الستة)
            $table->decimal('total_score', 4, 1)->default(0)->comment('النتيجة الإجمالية (0-100)');
            
            // بيانات التقييم
            $table->date('evaluation_date')->comment('تاريخ التقييم');
            $table->string('evaluation_period', 100)->nullable()->comment('فترة التقييم (شهري، فصلي، سنوي)');
            $table->text('notes')->nullable()->comment('ملاحظات التقييم');
            
            // بيانات المقيم
            $table->foreignId('evaluator_id')->constrained('users')->comment('المقيم');
            $table->string('evaluator_role', 50)->default('مشرف')->comment('دور المقيم');
            
            // حالة التقييم
            $table->enum('status', ['مسودة', 'مكتمل', 'معتمد', 'مراجعة'])->default('مسودة');
            
            // تواريخ النظام
            $table->timestamps();
            
            // فهارس للأداء
            $table->index(['teacher_id', 'evaluation_date']);
            $table->index(['evaluator_id']);
            $table->index(['status']);
            $table->index(['evaluation_period']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_evaluations');
    }
};
