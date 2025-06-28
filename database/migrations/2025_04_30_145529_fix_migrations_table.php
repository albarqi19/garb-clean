<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * إضافة ملف هجرة المستخدمين المشكل إلى جدول migrations
     * لتجاوز خطأ "Table 'users' already exists"
     */
    public function up(): void
    {
        // إضافة سجل لملف الهجرة المشكل في جدول migrations
        // لمنع محاولة إنشاء جدول users مرة أخرى
        DB::table('migrations')->insert([
            'migration' => '2025_04_28_192314_create_users_table',
            'batch' => DB::table('migrations')->max('batch') + 1
        ]);
    }

    /**
     * Reverse the migrations.
     * حذف السجل المضاف في حالة التراجع عن الهجرة
     */
    public function down(): void
    {
        // حذف السجل المضاف في حالة التراجع عن الهجرة
        DB::table('migrations')->where('migration', '2025_04_28_192314_create_users_table')->delete();
    }
};
