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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('due_date');
            $table->enum('priority', ['منخفضة', 'متوسطة', 'عالية', 'عاجلة'])->default('متوسطة');
            $table->enum('status', ['جديدة', 'قيد التنفيذ', 'مكتملة', 'متأخرة', 'ملغاة'])->default('جديدة');
            $table->enum('department', ['التعليمية', 'التسويق', 'المالية', 'الموارد البشرية', 'عام'])->default('عام');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->date('completed_at')->nullable();
            
            // العلاقات المورفية للمهام (يمكن ربط المهمة بعناصر مختلفة)
            $table->nullableMorphs('taskable');
            
            // معلومات إضافية
            $table->integer('completion_percentage')->default(0);
            $table->string('tags')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->json('recurring_pattern')->nullable(); // نمط التكرار للمهام المتكررة
            
            $table->timestamps();
            $table->softDeletes(); // للحفاظ على تاريخ المهام حتى بعد حذفها
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
