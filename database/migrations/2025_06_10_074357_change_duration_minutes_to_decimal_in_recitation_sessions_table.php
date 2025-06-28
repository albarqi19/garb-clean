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
        Schema::table('recitation_sessions', function (Blueprint $table) {
            // تغيير نوع عمود duration_minutes من unsignedTinyInteger إلى decimal لدعم القيم العشرية
            $table->decimal('duration_minutes', 5, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recitation_sessions', function (Blueprint $table) {
            // إعادة النوع إلى unsignedTinyInteger
            $table->unsignedTinyInteger('duration_minutes')->nullable()->change();
        });
    }
};
