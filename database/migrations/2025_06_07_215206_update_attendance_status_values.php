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
        // تحديث القيم الإنجليزية إلى العربية في جدول حضور الطلاب
        DB::table('student_attendances')
            ->where('status', 'present')
            ->update(['status' => 'حاضر']);
            
        DB::table('student_attendances')
            ->where('status', 'absent')
            ->update(['status' => 'غائب']);
            
        DB::table('student_attendances')
            ->where('status', 'late')
            ->update(['status' => 'متأخر']);
            
        DB::table('student_attendances')
            ->where('status', 'excused')
            ->update(['status' => 'مأذون']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // إعادة القيم إلى الإنجليزية في حالة التراجع
        DB::table('student_attendances')
            ->where('status', 'حاضر')
            ->update(['status' => 'present']);
            
        DB::table('student_attendances')
            ->where('status', 'غائب')
            ->update(['status' => 'absent']);
            
        DB::table('student_attendances')
            ->where('status', 'متأخر')
            ->update(['status' => 'late']);
            
        DB::table('student_attendances')
            ->where('status', 'مأذون')
            ->update(['status' => 'excused']);
    }
};
