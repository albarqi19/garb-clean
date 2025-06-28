<?php

namespace App\Observers;

use App\Models\Student;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StudentObserver
{
    /**
     * Handle the Student "creating" event.
     */
    public function creating(Student $student): void
    {
        // إذا لم يتم تحديد كلمة مرور، قم بتوليد واحدة تلقائياً
        if (empty($student->password)) {
            $randomPassword = Str::random(8);
            $student->password = Hash::make($randomPassword);
            $student->plain_password = $randomPassword;
        } else {
            // إذا تم تحديد كلمة مرور، قم بتشفيرها وحفظ النسخة الأصلية
            $student->plain_password = $student->password;
            $student->password = Hash::make($student->password);
        }

        // تحديد تاريخ تغيير كلمة المرور
        $student->password_changed_at = now();
    }

    /**
     * Handle the Student "updating" event.
     */
    public function updating(Student $student): void
    {
        // إذا تم تغيير كلمة المرور وليست مشفرة بعد
        if ($student->isDirty('password') && !empty($student->password)) {
            // التحقق من أن كلمة المرور الجديدة ليست مشفرة بالفعل
            if (!Hash::needsRehash($student->password) && strlen($student->password) < 60) {
                $student->plain_password = $student->password;
                $student->password = Hash::make($student->password);
                $student->password_changed_at = now();
            }
        }
    }
}
