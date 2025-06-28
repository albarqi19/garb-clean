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
        Schema::create('whats_app_templates', function (Blueprint $table) {
            $table->id();
            $table->string('template_key')->unique()->comment('مفتاح القالب');
            $table->string('template_name')->comment('اسم القالب');
            $table->text('template_content')->comment('محتوى القالب');
            $table->text('description')->nullable()->comment('وصف القالب');
            $table->json('variables')->nullable()->comment('المتغيرات المتاحة في القالب');
            $table->string('category')->default('general')->comment('تصنيف القالب');
            $table->boolean('is_active')->default(true)->comment('هل القالب نشط');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whats_app_templates');
    }
};
