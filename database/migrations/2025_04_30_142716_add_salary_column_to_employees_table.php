<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * إضافة عمود الراتب إلى جدول الموظفين
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // إضافة عمود الراتب مع السماح بقيم null (للموظفين بدون راتب)
            $table->decimal('salary', 10, 2)->nullable()->after('email')->comment('راتب الموظف الشهري');
        });
    }

    /**
     * Reverse the migrations.
     * حذف عمود الراتب من جدول الموظفين في حالة التراجع عن الهجرة
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // حذف العمود في حالة التراجع عن الهجرة
            $table->dropColumn('salary');
        });
    }
};
