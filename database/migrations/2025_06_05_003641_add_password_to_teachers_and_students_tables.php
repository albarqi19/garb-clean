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
        // إضافة حقول كلمة المرور للمعلمين
        Schema::table('teachers', function (Blueprint $table) {
            $table->string('password')->nullable()->after('phone')->comment('كلمة المرور للدخول للنظام');
            $table->timestamp('password_changed_at')->nullable()->comment('تاريخ آخر تغيير لكلمة المرور');
            $table->boolean('must_change_password')->default(true)->comment('يجب تغيير كلمة المرور في أول تسجيل دخول');
            $table->timestamp('last_login_at')->nullable()->comment('تاريخ آخر تسجيل دخول');
            $table->boolean('is_active_user')->default(true)->comment('المستخدم نشط للدخول للنظام');
        });

        // إضافة حقول كلمة المرور للطلاب
        Schema::table('students', function (Blueprint $table) {
            $table->string('password')->nullable()->after('phone')->comment('كلمة المرور للدخول للنظام');
            $table->timestamp('password_changed_at')->nullable()->comment('تاريخ آخر تغيير لكلمة المرور');
            $table->boolean('must_change_password')->default(true)->comment('يجب تغيير كلمة المرور في أول تسجيل دخول');
            $table->timestamp('last_login_at')->nullable()->comment('تاريخ آخر تسجيل دخول');
            $table->boolean('is_active_user')->default(true)->comment('المستخدم نشط للدخول للنظام');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn([
                'password',
                'password_changed_at', 
                'must_change_password',
                'last_login_at',
                'is_active_user'
            ]);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'password',
                'password_changed_at',
                'must_change_password', 
                'last_login_at',
                'is_active_user'
            ]);
        });
    }
};
