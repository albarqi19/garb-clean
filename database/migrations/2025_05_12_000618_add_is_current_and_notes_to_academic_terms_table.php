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
        Schema::table('academic_terms', function (Blueprint $table) {
            // إضافة عمود is_current لتحديد ما إذا كان هذا هو الفصل الدراسي الحالي
            $table->boolean('is_current')->default(false)->after('end_date');
            
            // إضافة عمود notes للملاحظات
            $table->text('notes')->nullable()->after('is_current');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_terms', function (Blueprint $table) {
            // حذف الأعمدة في حالة التراجع عن الترحيل
            $table->dropColumn(['is_current', 'notes']);
        });
    }
};
