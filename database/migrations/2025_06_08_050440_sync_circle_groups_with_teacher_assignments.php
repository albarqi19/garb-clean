<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // مزامنة الحلقات الفرعية مع تكليفات المعلمين الجديدة
        
        // 1. البحث عن التكليفات النشطة
        $activeAssignments = DB::table('teacher_circle_assignments')
            ->where('is_active', true)
            ->get();

        foreach ($activeAssignments as $assignment) {
            // 2. البحث عن الحلقات الفرعية التابعة لهذه الحلقة الرئيسية
            $circleGroups = DB::table('circle_groups')
                ->where('quran_circle_id', $assignment->quran_circle_id)
                ->whereNull('teacher_id') // فقط المجموعات التي لا تحتوي على معلم
                ->get();

            // 3. تحديث الحلقات الفرعية لتشير للمعلم المكلف
            foreach ($circleGroups as $group) {
                DB::table('circle_groups')
                    ->where('id', $group->id)
                    ->update([
                        'teacher_id' => $assignment->teacher_id,
                        'updated_at' => now()
                    ]);
                    
                echo "تم ربط المعلم {$assignment->teacher_id} بالمجموعة {$group->name}" . PHP_EOL;
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // إرجاع التغييرات - إزالة الربط
        DB::table('circle_groups')
            ->whereNotNull('teacher_id')
            ->update(['teacher_id' => null]);
    }
};
