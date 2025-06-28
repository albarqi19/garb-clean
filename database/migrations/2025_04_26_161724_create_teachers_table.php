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
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->string('identity_number')->unique(); // رقم الهوية
            $table->string('name'); // اسم المعلم
            $table->string('nationality'); // الجنسية
            $table->foreignId('mosque_id')->nullable()->constrained()->onDelete('set null'); // مركز التكلفة (المسجد)
            $table->string('system_number')->nullable(); // رقم المعلم في النظام
            $table->string('phone')->nullable(); // رقم التواصل
            
            // المسمى الوظيفي
            $table->enum('job_title', [
                'معلم حفظ',
                'معلم تلقين',
                'مشرف مقيم',
                'مساعد مشرف مقيم'
            ]);
            
            // المهمة
            $table->enum('task_type', [
                'معلم بمكافأة',
                'معلم محتسب',
                'مشرف',
                'مساعد مشرف'
            ]);
            
            $table->string('iban')->nullable(); // رقم الايبان
            
            // نوع الحلقة
            $table->enum('circle_type', [
                'مدرسة قرآنية',
                'حلقة فردية',
                'تلقين',
                'تحفيظ'
            ]);
            
            // وقت العمل
            $table->enum('work_time', [
                'عصر',
                'مغرب',
                'عصر ومغرب',
                'كل الأوقات'
            ]);
            
            $table->integer('absence_count')->default(0); // عدد الغياب
            $table->boolean('ratel_activated')->default(false); // تفعيل رتل
            $table->date('start_date')->nullable(); // تاريخ المباشرة
            $table->integer('evaluation')->nullable(); // تقييم المعلم (من 1 إلى 10 مثلاً)
            
            // علاقات إضافية
            $table->foreignId('quran_circle_id')->nullable()->constrained()->onDelete('set null'); // الحلقة التي يعمل بها المعلم
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
