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
        // حذف جميع البيانات الموجودة لتجنب تضارب enum
        DB::table('whatsapp_messages')->truncate();
        
        // تحديث enum ليشمل جميع القيم المطلوبة
        DB::statement("ALTER TABLE whatsapp_messages MODIFY COLUMN message_type ENUM('notification', 'command', 'response', 'reminder', 'attendance', 'custom', 'session') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // العودة للقيم الأصلية
        DB::statement("ALTER TABLE whatsapp_messages MODIFY COLUMN message_type ENUM('notification', 'command', 'response', 'reminder') NOT NULL");
    }
};
