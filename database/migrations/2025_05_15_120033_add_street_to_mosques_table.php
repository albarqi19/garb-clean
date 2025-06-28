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
            $table->string('street')->nullable()->after('neighborhood'); // حقل الشارع
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mosques', function (Blueprint $table) {
            $table->dropColumn('street');
        });
    }
};
