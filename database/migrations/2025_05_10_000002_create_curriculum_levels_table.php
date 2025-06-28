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
        Schema::create('curriculum_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_id')->constrained()->onDelete('cascade'); // المنهج الأساسي
            $table->string('name'); // اسم المستوى
            $table->integer('level_order'); // ترتيب المستوى (1, 2, 3, 4)
            $table->text('description')->nullable(); // وصف المستوى
            $table->boolean('is_active')->default(true); // هل المستوى فعال
            $table->timestamps();
            
            // ضمان عدم تكرار مستوى بنفس الاسم والترتيب للمنهج نفسه
            $table->unique(['curriculum_id', 'level_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('curriculum_levels');
    }
};
