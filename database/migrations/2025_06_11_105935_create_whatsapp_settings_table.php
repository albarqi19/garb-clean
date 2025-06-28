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
        Schema::create('whatsapp_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key')->unique()->comment('مفتاح الإعداد');
            $table->text('setting_value')->comment('قيمة الإعداد');
            $table->text('description')->nullable()->comment('وصف الإعداد');
            $table->boolean('is_active')->default(true)->comment('هل الإعداد نشط');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_settings');
    }
};
