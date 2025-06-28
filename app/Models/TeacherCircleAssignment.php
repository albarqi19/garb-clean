<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class TeacherCircleAssignment extends Model
{
    protected $fillable = [
        'teacher_id',
        'quran_circle_id', 
        'is_active',
        'start_date',
        'end_date',
        'notes'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    // العلاقات
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function circle(): BelongsTo
    {
        return $this->belongsTo(QuranCircle::class, 'quran_circle_id');
    }

    // Scopes للاستعلامات الشائعة
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function scopeForCircle($query, $circleId)
    {
        return $query->where('quran_circle_id', $circleId);
    }

    public function scopeInDateRange($query, $date = null)
    {
        $date = $date ?: now();
        return $query->where('start_date', '<=', $date)
                     ->where(function($q) use ($date) {
                         $q->whereNull('end_date')
                           ->orWhere('end_date', '>=', $date);
                     });
    }

    // التحقق من تعارض الأوقات (الفترة الزمنية مثل العصر، المغرب)
    public static function hasTimeConflict($teacherId, $circleId, $startDate, $endDate = null, $excludeId = null)
    {
        // الحصول على الفترة الزمنية للحلقة الجديدة
        $newCircle = \App\Models\QuranCircle::find($circleId);
        if (!$newCircle) {
            return false;
        }
        
        $newTimePeriod = $newCircle->time_period;

        // البحث عن تكليفات نشطة للمعلم نفسه في نفس الفترة الزمنية (مبسط)
        $conflictingAssignments = static::where('teacher_id', $teacherId)
                                      ->where('is_active', true)
                                      ->where('quran_circle_id', '!=', $circleId)
                                      ->whereHas('circle', function($circleQuery) use ($newTimePeriod) {
                                          $circleQuery->where('time_period', $newTimePeriod);
                                      });

        if ($excludeId) {
            $conflictingAssignments->where('id', '!=', $excludeId);
        }

        // فحص بسيط للتداخل الزمني
        if ($endDate) {
            // إذا كان هناك تاريخ انتهاء للتكليف الجديد
            $conflictingAssignments->where(function($q) use ($startDate, $endDate) {
                $q->where(function($subQ) use ($startDate, $endDate) {
                    // التكليف الموجود ليس له تاريخ انتهاء (مفتوح) ويبدأ قبل انتهاء الجديد
                    $subQ->whereNull('end_date')
                         ->where('start_date', '<=', $endDate);
                })->orWhere(function($subQ) use ($startDate, $endDate) {
                    // التكليف الموجود له تاريخ انتهاء والفترات متداخلة
                    $subQ->whereNotNull('end_date')
                         ->where('start_date', '<=', $endDate)
                         ->where('end_date', '>=', $startDate);
                });
            });
        } else {
            // إذا لم يكن هناك تاريخ انتهاء للتكليف الجديد (مفتوح)
            $conflictingAssignments->where(function($q) use ($startDate) {
                $q->where(function($subQ) use ($startDate) {
                    // التكليف الموجود ليس له تاريخ انتهاء (كلاهما مفتوح = تعارض)
                    $subQ->whereNull('end_date');
                })->orWhere(function($subQ) use ($startDate) {
                    // التكليف الموجود له تاريخ انتهاء ولم ينته بعد
                    $subQ->whereNotNull('end_date')
                         ->where('end_date', '>=', $startDate);
                });
            });
        }

        return $conflictingAssignments->exists();
    }
}
