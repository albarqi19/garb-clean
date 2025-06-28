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
        Schema::table('mosques', function (Blueprint $table) {
            // إضافة حقل الشارع
            $table->string('street')->nullable()->after('neighborhood');
            
            // تعديل حقل الحي ليكون nullable
            $table->string('neighborhood')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mosques', function (Blueprint $table) {
            // حذف حقل الشارع
            $table->dropColumn('street');
            
            // إعادة حقل الحي ليكون مطلوب
            $table->string('neighborhood')->nullable(false)->change();
        });
    }
};
