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
        Schema::create('curriculum_alerts', function (Blueprint $table) {
            $table->id();
            
            // Student information
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->foreignId('recitation_session_id')->nullable()->constrained('recitation_sessions')->onDelete('set null');
            
            // Alert type and category
            $table->enum('alert_type', [
                'level_progression', 'curriculum_adjustment', 'performance_alert',
                'completion_milestone', 'attention_needed', 'recommendation'
            ]);
            
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            
            // Alert content
            $table->string('title');
            $table->text('message');
            $table->json('alert_data')->nullable(); // Flexible data storage
            
            // Current and suggested states
            $table->string('current_level')->nullable();
            $table->string('suggested_level')->nullable();
            $table->string('current_curriculum')->nullable();
            $table->string('suggested_curriculum')->nullable();
            
            // Performance metrics that triggered the alert
            $table->decimal('performance_score', 5, 2)->nullable();
            $table->integer('consecutive_sessions')->nullable();
            $table->decimal('completion_percentage', 5, 2)->nullable();
            
            // Alert status and handling
            $table->enum('status', ['pending', 'reviewed', 'applied', 'dismissed'])->default('pending');
            $table->boolean('requires_teacher_approval')->default(false);
            $table->boolean('auto_apply_enabled')->default(false);
            
            // Timestamps for tracking
            $table->timestamp('triggered_at');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            
            // Who handled the alert
            $table->foreignId('reviewed_by')->nullable()->constrained('teachers')->onDelete('set null');
            $table->text('review_notes')->nullable();
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['student_id', 'status']);
            $table->index(['teacher_id', 'status']);
            $table->index(['alert_type', 'priority']);
            $table->index('triggered_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('curriculum_alerts');
    }
};
