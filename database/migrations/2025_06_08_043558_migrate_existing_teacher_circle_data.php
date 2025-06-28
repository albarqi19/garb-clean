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
        // نقل البيانات الحالية من العلاقة المباشرة إلى جدول teacher_circle_assignments
        $teachers = DB::table('teachers')
            ->whereNotNull('quran_circle_id')
            ->get();

        foreach ($teachers as $teacher) {
            // التحقق من عدم وجود تكليف مسبق
            $existingAssignment = DB::table('teacher_circle_assignments')
                ->where('teacher_id', $teacher->id)
                ->where('quran_circle_id', $teacher->quran_circle_id)
                ->where('is_active', true)
                ->exists();

            if (!$existingAssignment) {
                DB::table('teacher_circle_assignments')->insert([
                    'teacher_id' => $teacher->id,
                    'quran_circle_id' => $teacher->quran_circle_id,
                    'is_active' => true,
                    'start_date' => $teacher->created_at ?? now(),
                    'end_date' => null,
                    'notes' => 'تم نقل البيانات من النظام القديم',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        
        // إضافة رسالة في السجل
        echo "تم نقل " . $teachers->count() . " تكليف معلم من النظام القديم إلى النظام الجديد\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // حذف جميع التكليفات التي تم إنشاؤها بواسطة هذا migration
        DB::table('teacher_circle_assignments')
            ->where('notes', 'تم نقل البيانات من النظام القديم')
            ->delete();
    }
};
