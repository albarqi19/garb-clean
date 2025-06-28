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
        Schema::table('curriculum_plans', function (Blueprint $table) {
            // إضافة حقول المحتوى القرآني المفقودة
            $table->string('content_type')->default('text')->after('content');
            $table->integer('surah_number')->nullable()->after('content_type');
            $table->integer('start_verse')->nullable()->after('surah_number');
            $table->integer('end_verse')->nullable()->after('start_verse');
            $table->integer('calculated_verses')->nullable()->after('end_verse');
            $table->text('formatted_content')->nullable()->after('calculated_verses');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('curriculum_plans', function (Blueprint $table) {
            $table->dropColumn([
                'content_type',
                'surah_number',
                'start_verse', 
                'end_verse',
                'calculated_verses',
                'formatted_content'
            ]);
        });
    }
};
