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
        Schema::table('recitation_errors', function (Blueprint $table) {
            $table->string('error_type', 50)->change(); // توسيع العمود إلى 50 حرف
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recitation_errors', function (Blueprint $table) {
            $table->string('error_type', 20)->change(); // العودة للحجم الأصلي
        });
    }
};
