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
        Schema::create('mosques', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم المسجد
            $table->string('neighborhood'); // الحي
            $table->decimal('location_lat', 10, 8)->nullable(); // خط العرض للموقع
            $table->decimal('location_long', 11, 8)->nullable(); // خط الطول للموقع
            $table->string('contact_number')->nullable(); // رقم التواصل
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mosques');
    }
};
