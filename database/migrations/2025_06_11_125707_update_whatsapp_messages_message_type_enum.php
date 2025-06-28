<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            // تحديث enum ليشمل القيم الجديدة
            DB::statement("ALTER TABLE whatsapp_messages MODIFY COLUMN message_type ENUM('notification', 'command', 'response', 'reminder', 'attendance', 'custom', 'session', 'alert') COMMENT 'نوع الرسالة'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            // إعادة enum إلى القيم الأصلية
            DB::statement("ALTER TABLE whatsapp_messages MODIFY COLUMN message_type ENUM('notification', 'command', 'response', 'reminder') COMMENT 'نوع الرسالة'");
        });
    }
};
