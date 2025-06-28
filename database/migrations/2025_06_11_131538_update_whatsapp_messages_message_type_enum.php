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
        // تحديث enum ليشمل 'test'
        DB::statement("ALTER TABLE whatsapp_messages MODIFY COLUMN message_type ENUM('notification','command','response','reminder','attendance','custom','session','test')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // إرجاع enum إلى حالته السابقة
        DB::statement("ALTER TABLE whatsapp_messages MODIFY COLUMN message_type ENUM('notification','command','response','reminder','attendance','custom','session')");
    }
};
