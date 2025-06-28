<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TeacherMosqueSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'mosque_id',
        'day_of_week',
        'start_time',
        'end_time',
        'session_type',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'is_active' => 'boolean',
    ];

    // العلاقات
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function mosque(): BelongsTo
    {
        return $this->belongsTo(Mosque::class);
    }

    // الطرق المساعدة
    public function getDayInArabic(): string
    {
        $days = [
            'Sunday' => 'الأحد',
            'Monday' => 'الاثنين',
            'Tuesday' => 'الثلاثاء',
            'Wednesday' => 'الأربعاء',
            'Thursday' => 'الخميس',
            'Friday' => 'الجمعة',
            'Saturday' => 'السبت',
        ];

        return $days[$this->day_of_week] ?? $this->day_of_week;
    }

    public function getFormattedTimeRange(): string
    {
        return $this->start_time . ' - ' . $this->end_time;
    }

    // نطاقات الاستعلام
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForDay($query, $day)
    {
        return $query->where('day_of_week', $day);
    }

    public function scopeForTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function scopeForMosque($query, $mosqueId)
    {
        return $query->where('mosque_id', $mosqueId);
    }
}
