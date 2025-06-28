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
        Schema::create('strategic_initiatives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('strategic_monitoring_id')->constrained('strategic_monitorings')->onDelete('cascade')->comment('عملية الرصد المرتبطة');
            $table->string('name')->comment('اسم المبادرة');
            $table->text('description')->nullable()->comment('وصف تفصيلي للمبادرة');
            $table->date('start_date')->nullable()->comment('تاريخ بدء المبادرة');
            $table->date('end_date')->nullable()->comment('تاريخ انتهاء المبادرة');
            $table->enum('status', ['planned', 'in_progress', 'completed', 'delayed', 'cancelled'])->default('planned')->comment('حالة المبادرة');
            $table->foreignId('responsible_id')->nullable()->constrained('users')->comment('المستخدم المسؤول عن المبادرة');
            $table->decimal('progress_percentage', 5, 2)->default(0)->comment('نسبة إنجاز المبادرة');
            $table->text('notes')->nullable()->comment('ملاحظات على المبادرة');
            $table->foreignId('created_by')->nullable()->constrained('users')->comment('المستخدم الذي أنشأ المبادرة');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('strategic_initiatives');
    }
};
