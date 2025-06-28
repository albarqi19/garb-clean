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
        // إضافة عمود كلمة المرور الأصلية للمعلمين
        Schema::table('teachers', function (Blueprint $table) {
            $table->string('plain_password')->nullable()->after('password')->comment('كلمة المرور الأصلية غير المشفرة');
        });

        // إضافة عمود كلمة المرور الأصلية للطلاب
        Schema::table('students', function (Blueprint $table) {
            $table->string('plain_password')->nullable()->after('password')->comment('كلمة المرور الأصلية غير المشفرة');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn('plain_password');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('plain_password');
        });
    }
};
