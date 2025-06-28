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
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('username')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->string('phone')->nullable();
                $table->boolean('is_active')->default(true);
                $table->rememberToken();
                $table->timestamps();
            });
        } else {
            // إضافة الأعمدة الإضافية إذا كان الجدول موجودًا بالفعل
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'username')) {
                    $table->string('username')->unique();
                }
                if (!Schema::hasColumn('users', 'phone')) {
                    $table->string('phone')->nullable();
                }
                if (!Schema::hasColumn('users', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // لا تقم بحذف الجدول، فقط قم بإزالة الأعمدة المضافة
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'username')) {
                    $table->dropColumn('username');
                }
                if (Schema::hasColumn('users', 'phone')) {
                    $table->dropColumn('phone');
                }
                if (Schema::hasColumn('users', 'is_active')) {
                    $table->dropColumn('is_active');
                }
            });
        }
    }
};
