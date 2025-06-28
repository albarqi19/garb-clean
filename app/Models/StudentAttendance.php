<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAttendance extends Model
{
    use HasFactory;

    /**
     * اسم الجدول في قاعدة البيانات
     */
    protected $table = 'attendances';

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'attendable_id',    // هذا سيكون student_id
        'attendable_type',  // هذا سيكون Student::class
        'date',
        'status',
        'period',
        'notes',
    ];

    /**
     * الخصائص التي يجب تحويلها.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
    ];

    /**
     * علاقة مع الطالب (polymorphic)
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'attendable_id');
    }

    /**
     * علاقة polymorphic مع attendable
     */
    public function attendable()
    {
        return $this->morphTo();
    }

    /**
     * تحديد ما إذا كان الطالب حاضر
     */
    public function getIsPresentAttribute(): bool
    {
        return $this->status === 'حاضر';
    }

    /**
     * تحديد ما إذا كان الطالب غائب
     */
    public function getIsAbsentAttribute(): bool
    {
        return $this->status === 'غائب';
    }

    /**
     * تحديد ما إذا كان الطالب متأخر
     */
    public function getIsLateAttribute(): bool
    {
        return $this->status === 'متأخر';
    }

    /**
     * تحديد ما إذا كان الطالب مأذون
     */
    public function getIsExcusedAttribute(): bool
    {
        return $this->status === 'مأذون';
    }

    /**
     * نطاق للطلاب الحاضرين
     */
    public function scopePresent($query)
    {
        return $query->where('status', 'حاضر');
    }

    /**
     * نطاق للطلاب الغائبين
     */
    public function scopeAbsent($query)
    {
        return $query->where('status', 'غائب');
    }

    /**
     * نطاق للطلاب المتأخرين
     */
    public function scopeLate($query)
    {
        return $query->where('status', 'متأخر');
    }

    /**
     * نطاق للطلاب المأذونين
     */
    public function scopeExcused($query)
    {
        return $query->where('status', 'مأذون');
    }

    /**
     * الحصول على إحصائيات الحضور لطالب في فترة محددة
     */
    public static function getAttendanceStats($studentId, $startDate, $endDate)
    {
        $records = self::where('attendable_id', $studentId)
            ->where('attendable_type', Student::class)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        return [
            'total_days' => $records->count(),
            'present_days' => $records->where('status', 'حاضر')->count(),
            'absent_days' => $records->where('status', 'غائب')->count(),
            'late_days' => $records->where('status', 'متأخر')->count(),
            'excused_days' => $records->where('status', 'مأذون')->count(),
            'attendance_rate' => $records->count() > 0 ? 
                round(($records->where('status', 'حاضر')->count() / $records->count()) * 100, 2) : 0,
        ];
    }
}
