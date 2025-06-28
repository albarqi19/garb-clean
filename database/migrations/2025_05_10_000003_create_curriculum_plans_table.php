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
        Schema::create('curriculum_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_id')->constrained()->onDelete('cascade'); // المنهج الأساسي
            $table->foreignId('curriculum_level_id')->nullable()->constrained()->onDelete('cascade'); // المستوى (إذا كان منهج طالب)
            $table->string('name'); // اسم الخطة
            $table->enum('plan_type', ['الدرس', 'المراجعة الصغرى', 'المراجعة الكبرى']); // نوع الخطة
            $table->text('content'); // محتوى الخطة (السور والآيات أو الأجزاء المحددة)
            $table->text('instructions')->nullable(); // تعليمات الخطة
            $table->integer('expected_days')->nullable(); // عدد الأيام المتوقعة لإكمال الخطة
            $table->boolean('is_active')->default(true); // هل الخطة فعالة
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('curriculum_plans');
    }
};
