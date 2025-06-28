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
        Schema::table('users', function (Blueprint $table) {
            // إضافة حقل رقم الهوية بعد البريد الإلكتروني مع إمكانية أن يكون فارغاً
            $table->string('identity_number')->nullable()->unique()->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // إزالة حقل رقم الهوية عند التراجع عن الـ migration
            $table->dropColumn('identity_number');
        });
    }
};
