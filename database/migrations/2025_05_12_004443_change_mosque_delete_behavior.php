<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * الحصول على اسم مفتاح أجنبي للجدول والعمود
     */
    protected function getForeignKeyName($table, $column)
    {
        $constraints = DB::select("
            SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$table, $column]);

        if (!empty($constraints)) {
            return $constraints[0]->CONSTRAINT_NAME;
        }
        
        return null;
    }
    
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // إزالة قيد الحذف المتتالي cascade وإضافة قيد تعيين قيمة فارغة set null
        Schema::table('quran_circles', function (Blueprint $table) {
            try {
                // محاولة حذف القيد الحالي إذا كان موجودا
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                
                // التعليمة التالية قد تفشل إذا كان اسم القيد مختلفا
                $constraintName = $this->getForeignKeyName('quran_circles', 'mosque_id');
                if (!empty($constraintName)) {
                    DB::statement("ALTER TABLE quran_circles DROP FOREIGN KEY {$constraintName}");
                }
                
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            } catch (\Exception $e) {
                // نتجاهل الخطأ إذا لم يكن هناك قيد أجنبي
            }
            
            // تغيير العمود ليصبح قابل لأن يكون فارغا
            DB::statement('ALTER TABLE quran_circles MODIFY mosque_id BIGINT UNSIGNED NULL');
            
            // ثم نضيف القيد الجديد مع onDelete set null
            $table->foreign('mosque_id')
                ->references('id')
                ->on('mosques')
                ->onDelete('set null');
        });
        
        // أيضا نقوم بتطبيق نفس التغيير على أي جداول أخرى مرتبطة بالمساجد
        if (Schema::hasColumn('teachers', 'mosque_id')) {
            Schema::table('teachers', function (Blueprint $table) {
                // تحقق مما إذا كان هناك قيد مفتاح أجنبي
                try {
                    DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                    
                    $constraintName = $this->getForeignKeyName('teachers', 'mosque_id');
                    if (!empty($constraintName)) {
                        DB::statement("ALTER TABLE teachers DROP FOREIGN KEY {$constraintName}");
                    }
                    
                    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
                } catch (\Exception $e) {
                    // قد لا يكون هناك قيد
                }
                
                // تغيير العمود ليصبح قابل لأن يكون فارغا
                DB::statement('ALTER TABLE teachers MODIFY mosque_id BIGINT UNSIGNED NULL');
                
                $table->foreign('mosque_id')
                    ->references('id')
                    ->on('mosques')
                    ->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // إعادة قيد الحذف المتتالي cascade
        Schema::table('quran_circles', function (Blueprint $table) {
            try {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                
                $constraintName = $this->getForeignKeyName('quran_circles', 'mosque_id');
                if (!empty($constraintName)) {
                    DB::statement("ALTER TABLE quran_circles DROP FOREIGN KEY {$constraintName}");
                }
                
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            } catch (\Exception $e) {
                // نتجاهل الخطأ إذا لم يكن هناك قيد أجنبي
            }
            
            // إعادة العمود ليصبح غير قابل للقيمة الفارغة
            DB::statement('ALTER TABLE quran_circles MODIFY mosque_id BIGINT UNSIGNED NOT NULL');
            
            $table->foreign('mosque_id')
                ->references('id')
                ->on('mosques')
                ->onDelete('cascade');
        });
        
        // إعادة القيود الأصلية للجداول الأخرى
        if (Schema::hasColumn('teachers', 'mosque_id')) {
            Schema::table('teachers', function (Blueprint $table) {
                try {
                    DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                    
                    $constraintName = $this->getForeignKeyName('teachers', 'mosque_id');
                    if (!empty($constraintName)) {
                        DB::statement("ALTER TABLE teachers DROP FOREIGN KEY {$constraintName}");
                    }
                    
                    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
                } catch (\Exception $e) {
                    // نتجاهل الخطأ إذا لم يكن هناك قيد أجنبي
                }
                
                $table->foreign('mosque_id')
                    ->references('id')
                    ->on('mosques')
                    ->onDelete('set null');
            });
        }
    }
};
