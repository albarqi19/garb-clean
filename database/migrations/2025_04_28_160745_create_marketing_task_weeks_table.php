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
        Schema::create('marketing_task_weeks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->integer('week_number');
            $table->integer('year');
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->float('completion_percentage')->default(0);
            $table->json('goals')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_current')->default(false);
            $table->boolean('is_template')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            // ضمان عدم تكرار الأسابيع
            $table->unique(['week_number', 'year', 'is_template']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketing_task_weeks');
    }
};
