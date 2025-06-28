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
    {        Schema::create('student_curriculum_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_curriculum_id')->constrained('student_curricula')->onDelete('cascade'); // علاقة مع منهج الطالب
            $table->foreignId('curriculum_plan_id')->constrained()->onDelete('cascade'); // خطة المنهج
            $table->date('start_date'); // تاريخ البدء
            $table->date('completion_date')->nullable(); // تاريخ الإكمال
            $table->enum('status', ['قيد التنفيذ', 'مكتمل'])->default('قيد التنفيذ'); // حالة الخطة
            $table->float('completion_percentage')->default(0); // نسبة الإكمال
            $table->text('teacher_notes')->nullable(); // ملاحظات المعلم
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_curriculum_progress');
    }
};
