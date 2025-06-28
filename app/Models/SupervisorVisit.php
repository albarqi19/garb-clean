<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupervisorVisit extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array
     */
    protected $fillable = [
        'supervisor_id',
        'quran_circle_id',
        'visit_date',
        'visit_status',
        'notes',
        'circle_rating',
        'students_count',
        'exam_students_count',
        'passed_students_count',
        'memorized_parts_count',
        'reviewed_parts_count',
        'ratel_activated',
        'visit_type',
        'evaluation_period_id',
    ];

    /**
     * الخصائص التي يجب تحويلها.
     *
     * @var array
     */
    protected $casts = [
        'visit_date' => 'datetime',
        'ratel_activated' => 'boolean',
    ];

    /**
     * علاقة مع المشرف.
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * علاقة مع الحلقة القرآنية.
     */
    public function quranCircle(): BelongsTo
    {
        return $this->belongsTo(QuranCircle::class);
    }

    /**
     * علاقة مع فترة التقييم.
     */
    public function evaluationPeriod(): BelongsTo
    {
        return $this->belongsTo(EvaluationPeriod::class);
    }

    /**
     * نسبة نجاح الطلاب في الاختبار (محسوبة).
     */
    public function getPassRateAttribute(): ?float
    {
        if (!$this->exam_students_count || $this->exam_students_count <= 0) {
            return null;
        }

        return ($this->passed_students_count / $this->exam_students_count) * 100;
    }

    /**
     * تمييز ما إذا كانت الزيارة مكتملة.
     */
    public function getIsCompletedAttribute(): bool
    {
        return $this->visit_status === 'تمت الزيارة' && $this->circle_rating !== null;
    }

    /**
     * نطاق الاستعلام للزيارات المخططة.
     */
    public function scopePlanned($query)
    {
        return $query->where('visit_status', 'مخطط');
    }

    /**
     * نطاق الاستعلام للزيارات المكتملة.
     */
    public function scopeCompleted($query)
    {
        return $query->where('visit_status', 'تمت الزيارة');
    }

    /**
     * نطاق الاستعلام للزيارات الملغاة.
     */
    public function scopeCancelled($query)
    {
        return $query->where('visit_status', 'ملغية');
    }

    /**
     * نطاق الاستعلام للزيارات حسب فترة زمنية.
     */
    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('visit_date', [$startDate, $endDate]);
    }

    /**
     * الخصائص الملحقة.
     */
    protected $appends = [
        'pass_rate',
        'is_completed',
    ];
}