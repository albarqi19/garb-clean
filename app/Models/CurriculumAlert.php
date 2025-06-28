<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\AlertType;

class CurriculumAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'current_curriculum_id',
        'suggested_curriculum_id',
        'alert_type',
        'alert_message',
        'performance_summary',
        'teacher_decision',
        'decision_notes',
        'decided_at',
    ];

    protected $casts = [
        'alert_type' => AlertType::class,
        'performance_summary' => 'array',
        'decided_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * علاقة مع الطالب
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * علاقة مع المنهج الحالي
     */
    public function currentCurriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class, 'current_curriculum_id');
    }

    /**
     * علاقة مع المنهج المقترح
     */
    public function suggestedCurriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class, 'suggested_curriculum_id');
    }

    /**
     * النطاقات (Scopes)
     */
    public function scopePending($query)
    {
        return $query->whereNull('teacher_decision');
    }

    public function scopeApproved($query)
    {
        return $query->where('teacher_decision', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('teacher_decision', 'rejected');
    }

    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeByAlertType($query, AlertType $alertType)
    {
        return $query->where('alert_type', $alertType);
    }

    /**
     * طرق مساعدة
     */
    public function isPending(): bool
    {
        return is_null($this->teacher_decision);
    }

    public function isApproved(): bool
    {
        return $this->teacher_decision === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->teacher_decision === 'rejected';
    }

    public function markAsApproved(string $notes = null): void
    {
        $this->update([
            'teacher_decision' => 'approved',
            'decision_notes' => $notes,
            'decided_at' => now(),
        ]);
    }

    public function markAsRejected(string $notes = null): void
    {
        $this->update([
            'teacher_decision' => 'rejected',
            'decision_notes' => $notes,
            'decided_at' => now(),
        ]);
    }

    /**
     * الحصول على رسالة التنبيه المنسقة
     */
    public function getFormattedAlertMessage(): string
    {
        $currentCurriculumName = $this->currentCurriculum->name ?? 'غير محدد';
        $suggestedCurriculumName = $this->suggestedCurriculum->name ?? 'غير محدد';
        
        return str_replace(
            ['{current_curriculum}', '{suggested_curriculum}'],
            [$currentCurriculumName, $suggestedCurriculumName],
            $this->alert_message
        );
    }

    /**
     * الحصول على ملخص الأداء المنسق
     */
    public function getFormattedPerformanceSummary(): array
    {
        $summary = $this->performance_summary ?? [];
        
        return [
            'average_score' => $summary['average_score'] ?? 0,
            'completion_rate' => $summary['completion_rate'] ?? 0,
            'session_count' => $summary['session_count'] ?? 0,
            'days_in_current_level' => $summary['days_in_current_level'] ?? 0,
            'recommendation_reason' => $summary['recommendation_reason'] ?? '',
        ];
    }

    /**
     * التحقق من إمكانية اتخاذ قرار
     */
    public function canMakeDecision(): bool
    {
        return $this->isPending();
    }
}
