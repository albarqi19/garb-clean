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
        Schema::create('student_curricula', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade'); // الطالب
            $table->foreignId('curriculum_id')->constrained()->onDelete('cascade'); // المنهج
            $table->foreignId('curriculum_level_id')->nullable()->constrained()->onDelete('set null'); // المستوى الحالي
            $table->foreignId('teacher_id')->nullable()->constrained()->onDelete('set null'); // المعلم المشرف
            $table->date('start_date'); // تاريخ البدء
            $table->date('completion_date')->nullable(); // تاريخ الإكمال
            $table->enum('status', ['قيد التنفيذ', 'مكتمل', 'معلق', 'ملغي'])->default('قيد التنفيذ'); // حالة المنهج
            $table->float('completion_percentage')->default(0); // نسبة الإكمال
            $table->text('notes')->nullable(); // ملاحظات
            $table->timestamps();
            
            // ضمان عدم وجود منهجين بنفس النوع للطالب في وقت واحد (إلا إذا كان المنهج الأول مكتمل)
            $table->unique(['student_id', 'curriculum_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_curricula');
    }
};
