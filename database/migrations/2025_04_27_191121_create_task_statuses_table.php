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
        Schema::create('task_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained(); // المستخدم الذي غير الحالة
            $table->string('from_status'); // الحالة السابقة
            $table->string('to_status'); // الحالة الجديدة
            $table->text('comment')->nullable(); // تعليق/سبب تغيير الحالة
            $table->integer('completion_percentage')->nullable(); // نسبة الإنجاز عند تغيير الحالة
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_statuses');
    }
};
