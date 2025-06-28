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
        Schema::create('branch_followers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم المتابع
            $table->string('phone')->nullable(); // رقم الهاتف
            $table->string('email')->nullable(); // البريد الإلكتروني
            $table->string('source'); // مصدر التسجيل (اسم الفرع)
            $table->boolean('is_donor')->default(false); // هل هو متبرع بالفعل
            $table->date('registration_date'); // تاريخ التسجيل
            $table->text('notes')->nullable(); // ملاحظات
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete(); // من قام بتسجيله
            $table->timestamps();
            
            // التأكد من عدم التكرار باستخدام رقم الهاتف أو البريد الإلكتروني
            $table->unique(['phone', 'email'], 'follower_contact_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_followers');
    }
};