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
        Schema::create('circle_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quran_circle_id')->constrained()->onDelete('cascade'); // المدرسة القرآنية الجماعية التي تنتمي إليها الحلقة
            $table->foreignId('teacher_id')->nullable()->constrained()->nullOnDelete(); // معلم الحلقة
            $table->string('name'); // اسم الحلقة الفرعية
            $table->enum('status', ['نشطة', 'معلقة', 'مغلقة'])->default('نشطة'); // حالة الحلقة
            $table->text('description')->nullable(); // وصف الحلقة
            $table->string('meeting_days')->nullable(); // أيام اللقاء
            $table->string('additional_info')->nullable(); // معلومات إضافية
            $table->timestamps();
        });

        // إضافة عمود لربط الطلاب بالحلقات الفرعية
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('circle_group_id')->nullable()->after('quran_circle_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('circle_group_id');
        });
        
        Schema::dropIfExists('circle_groups');
    }
};
