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
        Schema::create('strategic_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('اسم الخطة الاستراتيجية');
            $table->text('description')->nullable()->comment('وصف الخطة');
            $table->date('start_date')->comment('تاريخ بداية الخطة');
            $table->date('end_date')->comment('تاريخ نهاية الخطة');
            $table->boolean('is_active')->default(true)->comment('حالة الخطة: نشطة أم غير نشطة');
            $table->foreignId('created_by')->nullable()->constrained('users')->comment('المستخدم الذي أنشأ الخطة');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('strategic_plans');
    }
};
