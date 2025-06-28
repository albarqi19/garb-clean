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
        Schema::create('circle_supervisors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supervisor_id')->constrained('users')->onDelete('cascade'); // المشرف
            $table->foreignId('quran_circle_id')->constrained()->onDelete('cascade'); // الحلقة
            $table->date('assignment_date'); // تاريخ تعيين المشرف على الحلقة
            $table->date('end_date')->nullable(); // تاريخ انتهاء تعيين المشرف على الحلقة (إذا انتهى)
            $table->boolean('is_active')->default(true); // هل التعيين نشط حالياً؟
            $table->text('notes')->nullable(); // ملاحظات على التعيين
            $table->timestamps();
            
            // لا يمكن تعيين نفس المشرف على نفس الحلقة مرتين بشكل نشط
            $table->unique(['supervisor_id', 'quran_circle_id', 'is_active'], 'active_assignment_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('circle_supervisors');
    }
};