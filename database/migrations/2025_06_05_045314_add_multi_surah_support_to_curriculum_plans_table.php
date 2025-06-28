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
            // إضافة نوع النطاق - سورة واحدة أو سور متعددة
            $table->enum('range_type', ['single_surah', 'multi_surah'])
                  ->default('single_surah')
                  ->after('content')
                  ->comment('نوع النطاق: سورة واحدة أو سور متعددة');
            
            // للنطاقات عبر السور المتعددة - السورة الأولى
            $table->unsignedTinyInteger('start_surah_number')
                  ->nullable()
                  ->after('range_type')
                  ->comment('رقم السورة الأولى (للنطاقات المتعددة)');
            
            // للنطاقات عبر السور المتعددة - السورة الأخيرة
            $table->unsignedTinyInteger('end_surah_number')
                  ->nullable()
                  ->after('start_surah_number')
                  ->comment('رقم السورة الأخيرة (للنطاقات المتعددة)');
            
            // للنطاقات عبر السور المتعددة - الآية الأولى في السورة الأولى
            $table->unsignedSmallInteger('start_surah_verse')
                  ->nullable()
                  ->after('end_surah_number')
                  ->comment('رقم الآية الأولى في السورة الأولى (للنطاقات المتعددة)');
            
            // للنطاقات عبر السور المتعددة - الآية الأخيرة في السورة الأخيرة
            $table->unsignedSmallInteger('end_surah_verse')
                  ->nullable()
                  ->after('start_surah_verse')
                  ->comment('رقم الآية الأخيرة في السورة الأخيرة (للنطاقات المتعددة)');
            
            // إجمالي عدد الآيات المحسوب للنطاق المتعدد
            $table->unsignedInteger('total_verses_calculated')
                  ->nullable()
                  ->after('end_surah_verse')
                  ->comment('إجمالي عدد الآيات المحسوب للنطاق');
            
            // المحتوى المنسق للنطاق المتعدد
            $table->text('multi_surah_formatted_content')
                  ->nullable()
                  ->after('total_verses_calculated')
                  ->comment('المحتوى المنسق للنطاق المتعدد');
            
            // إضافة فهارس للبحث السريع
            $table->index(['range_type']);
            $table->index(['start_surah_number', 'end_surah_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('curriculum_plans', function (Blueprint $table) {
            // حذف الفهارس أولاً
            $table->dropIndex(['range_type']);
            $table->dropIndex(['start_surah_number', 'end_surah_number']);
            
            // حذف الحقول
            $table->dropColumn([
                'range_type',
                'start_surah_number',
                'end_surah_number',
                'start_surah_verse',
                'end_surah_verse',
                'total_verses_calculated',
                'multi_surah_formatted_content'
            ]);
        });
    }
};
