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
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->enum('user_type', ['teacher', 'student', 'parent', 'admin'])->comment('نوع المستخدم');
            $table->unsignedBigInteger('user_id')->nullable()->comment('معرف المستخدم');
            $table->string('phone_number')->comment('رقم الهاتف');
            $table->enum('message_type', ['notification', 'command', 'response', 'reminder'])->comment('نوع الرسالة');
            $table->text('content')->comment('محتوى الرسالة');
            $table->enum('direction', ['outgoing', 'incoming'])->comment('اتجاه الرسالة');
            $table->enum('status', ['pending', 'sent', 'delivered', 'read', 'failed'])->default('pending')->comment('حالة الرسالة');
            $table->string('webhook_id')->nullable()->comment('معرف الويب هوك');
            $table->unsignedBigInteger('response_to')->nullable()->comment('رد على رسالة');
            $table->json('metadata')->nullable()->comment('بيانات إضافية');
            $table->timestamp('sent_at')->nullable()->comment('وقت الإرسال');
            $table->timestamp('delivered_at')->nullable()->comment('وقت التسليم');
            $table->timestamp('read_at')->nullable()->comment('وقت القراءة');
            $table->timestamps();
            
            // إضافة فهارس
            $table->index(['user_type', 'user_id']);
            $table->index('phone_number');
            $table->index('status');
            $table->index('message_type');
            $table->foreign('response_to')->references('id')->on('whatsapp_messages')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
