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
        Schema::create('quran_circles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mosque_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('supervisor_id')->nullable(); // معرف المشرف - سيتم ربطه لاحقاً عند إنشاء الجدول المناسب
            $table->enum('circle_type', ['مدرسة قرآنية', 'حلقة فردية', 'لم تبدأ بعد']);
            $table->enum('circle_status', ['تعمل', 'متوقفة', 'لم تبدأ بعد']);
            $table->enum('time_period', ['عصر', 'مغرب', 'عصر ومغرب', 'كل الأوقات']);
            $table->string('registration_link')->nullable();
            $table->boolean('has_ratel')->default(false);
            $table->boolean('has_qias')->default(false);
            $table->string('masser_link')->nullable();
            $table->unsignedBigInteger('monitor_id')->nullable(); // معرف المراقب - سيتم ربطه لاحقاً
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quran_circles');
    }
};
