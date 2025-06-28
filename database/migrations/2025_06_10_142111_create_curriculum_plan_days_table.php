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
        Schema::create('curriculum_plan_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_plan_id')->constrained()->onDelete('cascade'); // الخطة التي ينتمي إليها هذا اليوم
            $table->integer('day_number'); // رقم اليوم في الخطة
            
            // حقول الحفظ
            $table->boolean('memorization_enabled')->default(false); // هل الحفظ مفعل
            $table->string('memorization_from_surah')->nullable(); // السورة الأولى للحفظ
            $table->integer('memorization_from_verse')->nullable(); // الآية الأولى للحفظ
            $table->string('memorization_to_surah')->nullable(); // السورة الأخيرة للحفظ
            $table->integer('memorization_to_verse')->nullable(); // الآية الأخيرة للحفظ
            
            // حقول المراجعة الصغرى
            $table->boolean('minor_review_enabled')->default(false); // هل المراجعة الصغرى مفعلة
            $table->string('minor_review_from_surah')->nullable(); // السورة الأولى للمراجعة الصغرى
            $table->integer('minor_review_from_verse')->nullable(); // الآية الأولى للمراجعة الصغرى
            $table->string('minor_review_to_surah')->nullable(); // السورة الأخيرة للمراجعة الصغرى
            $table->integer('minor_review_to_verse')->nullable(); // الآية الأخيرة للمراجعة الصغرى
            
            // حقول المراجعة الكبرى
            $table->boolean('major_review_enabled')->default(false); // هل المراجعة الكبرى مفعلة
            $table->string('major_review_from_surah')->nullable(); // السورة الأولى للمراجعة الكبرى
            $table->integer('major_review_from_verse')->nullable(); // الآية الأولى للمراجعة الكبرى
            $table->string('major_review_to_surah')->nullable(); // السورة الأخيرة للمراجعة الكبرى
            $table->integer('major_review_to_verse')->nullable(); // الآية الأخيرة للمراجعة الكبرى
            
            // حقول إضافية
            $table->text('notes')->nullable(); // ملاحظات لليوم
            
            $table->timestamps();
            
            // فهارس لتحسين الأداء
            $table->index('curriculum_plan_id');
            $table->index('day_number');
            $table->unique(['curriculum_plan_id', 'day_number']); // منع تكرار نفس اليوم في نفس الخطة
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('curriculum_plan_days');
    }
};
