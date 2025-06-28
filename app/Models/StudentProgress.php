<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProgress extends Model
{
    use HasFactory;    protected $fillable = [
        'student_id',
        'curriculum_plan_id',
        'curriculum_id',
        'status',
        'recitation_status',
        'performance_score',
        'recitation_attempts',
        'started_at',
        'completed_at',
        'last_recitation_at',
        'notes',
        'teacher_feedback',
        'memorized_verses',
        'memorization_accuracy',
        'time_spent_minutes',
        'evaluated_by',
    ];

    protected $casts = [
        'performance_score' => 'decimal:1',
        'recitation_attempts' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_recitation_at' => 'datetime',
        'memorized_verses' => 'integer',
        'memorization_accuracy' => 'decimal:2',
        'time_spent_minutes' => 'integer',
    ];    /**
     * علاقة الطالب
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * علاقة خطة المنهج
     */
    public function curriculumPlan(): BelongsTo
    {
        return $this->belongsTo(CurriculumPlan::class);
    }

    /**
     * علاقة المنهج
     */
    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }

    /**
     * علاقة المقيم (المعلم)
     */
    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluated_by');
    }

    /**
     * نطاقات الاستعلام
     */
    public function scopeCompleted($query)
    {
        return $query->whereIn('status', ['completed', 'reviewed', 'mastered']);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeNeedsReview($query)
    {
        return $query->where('status', 'needs_revision');
    }

    public function scopeNotStarted($query)
    {
        return $query->where('status', 'not_started');
    }

    public function scopePassedRecitation($query)
    {
        return $query->whereIn('recitation_status', ['passed', 'excellent']);
    }

    public function scopeFailedRecitation($query)
    {
        return $query->whereIn('recitation_status', ['failed', 'partial']);
    }

    /**
     * خصائص محسوبة
     */
    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            'not_started' => 'لم يبدأ',
            'in_progress' => 'قيد التنفيذ',
            'completed' => 'مكتمل',
            'reviewed' => 'تم المراجعة',
            'mastered' => 'متقن',
            'needs_revision' => 'يحتاج مراجعة',
            default => 'غير محدد',
        };
    }

    public function getRecitationStatusTextAttribute(): string
    {
        return match ($this->recitation_status) {
            'pending' => 'في انتظار التسميع',
            'passed' => 'نجح في التسميع',
            'failed' => 'رسب في التسميع',
            'partial' => 'تسميع جزئي',
            'excellent' => 'ممتاز',
            default => 'غير محدد',
        };
    }

    public function getPerformanceGradeAttribute(): string
    {
        if (!$this->performance_score) {
            return 'غير مقيم';
        }

        return match (true) {
            $this->performance_score >= 9 => 'ممتاز',
            $this->performance_score >= 8 => 'جيد جداً',
            $this->performance_score >= 7 => 'جيد',
            $this->performance_score >= 6 => 'مقبول',
            default => 'ضعيف',
        };
    }    public function getMemorizationPercentageAttribute(): float
    {
        if (!$this->curriculumPlan) {
            return 0;
        }

        // Get total verses based on plan type
        $totalVerses = 0;
        if ($this->curriculumPlan->range_type === 'multi_surah') {
            $totalVerses = $this->curriculumPlan->total_verses_calculated ?? 0;
        } else {
            $totalVerses = $this->curriculumPlan->calculated_verses ?? 0;
        }

        if (!$totalVerses) {
            return 0;
        }

        return ($this->memorized_verses / $totalVerses) * 100;
    }

    /**
     * دوال مساعدة
     */
    public function markAsStarted(): void
    {
        if ($this->status === 'not_started') {
            $this->update([
                'status' => 'in_progress',
                'started_at' => now(),
            ]);
        }
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function addRecitationAttempt(string $result, float $score = null, string $feedback = null): void
    {
        $this->increment('recitation_attempts');
        
        $this->update([
            'recitation_status' => $result,
            'performance_score' => $score,
            'teacher_feedback' => $feedback,
            'last_recitation_at' => now(),
            'evaluated_by' => auth()->id(),
        ]);

        // تحديث الحالة بناء على نتيجة التسميع
        if (in_array($result, ['passed', 'excellent'])) {
            $this->markAsCompleted();
        } elseif ($result === 'failed') {
            $this->update(['status' => 'needs_revision']);
        }
    }

    public function updateMemorization(int $versesMemorized, float $accuracy = null): void
    {
        $this->update([
            'memorized_verses' => $versesMemorized,
            'memorization_accuracy' => $accuracy,
        ]);
    }

    public function addTimeSpent(int $minutes): void
    {
        $this->increment('time_spent_minutes', $minutes);
    }

    /**
     * تحديد ما إذا كان الطالب يحتاج لمراجعة
     */
    public function needsReview(): bool
    {
        return $this->status === 'needs_revision' || 
               $this->recitation_status === 'failed' ||
               ($this->performance_score && $this->performance_score < 6) ||
               ($this->memorization_accuracy && $this->memorization_accuracy < 80);
    }

    /**
     * تحديد أولوية المراجعة (1-10)
     */
    public function getReviewPriorityAttribute(): int
    {
        $priority = 5; // متوسط افتراضي

        // زيادة الأولوية للطلاب الذين رسبوا
        if ($this->recitation_status === 'failed') {
            $priority += 3;
        }

        // زيادة الأولوية للنتائج الضعيفة
        if ($this->performance_score && $this->performance_score < 5) {
            $priority += 2;
        }

        // زيادة الأولوية للدقة المنخفضة
        if ($this->memorization_accuracy && $this->memorization_accuracy < 70) {
            $priority += 2;
        }

        // تقليل الأولوية للمحاولات المتعددة
        if ($this->recitation_attempts > 3) {
            $priority -= 1;
        }        return min(10, max(1, $priority));
    }

    public function getRecitationQualityTextAttribute(): string
    {
        if (!$this->recitation_quality) {
            return 'غير مقيم';
        }

        return match (true) {
            $this->recitation_quality >= 9 => 'ممتاز',
            $this->recitation_quality >= 8 => 'جيد جداً',
            $this->recitation_quality >= 7 => 'جيد',
            $this->recitation_quality >= 6 => 'مقبول',
            default => 'ضعيف',
        };
    }

    public function getReviewPriorityTextAttribute(): string
    {
        if (!$this->review_priority) {
            return 'عادي';
        }

        return match (true) {
            $this->review_priority >= 80 => 'عاجل جداً',
            $this->review_priority >= 60 => 'عاجل',
            $this->review_priority >= 40 => 'متوسط',
            default => 'منخفض',
        };
    }

    /**
     * تحديث التقدم مع إعادة حساب الأولوية
     */
    public function updateProgress(array $data): bool
    {
        // تحديث البيانات الأساسية
        $this->fill($data);
        
        // إعادة حساب أولوية المراجعة
        if ($this->status !== 'completed') {
            $this->review_priority = $this->calculateReviewPriority();
            $this->estimated_review_time = $this->calculateEstimatedReviewTime();
        }
        
        // تحديد ما إذا كانت تحتاج مراجعة
        $this->review_required = $this->shouldRequireReview();
        
        return $this->save();
    }

    /**
     * حساب أولوية المراجعة
     */
    private function calculateReviewPriority(): int
    {
        $priority = 0;
        
        // بناء على عدد الأخطاء
        $priority += min($this->mistakes_count * 2, 30);
        
        // بناء على جودة التسميع
        if ($this->recitation_quality) {
            $priority += (10 - $this->recitation_quality) * 4;
        }
        
        // بناء على نسبة الإكمال
        if ($this->completion_percentage < 50) {
            $priority += 20;
        }
        
        // بناء على آخر نشاط
        if ($this->last_activity) {
            $daysSinceActivity = now()->diffInDays($this->last_activity);
            $priority += min($daysSinceActivity * 2, 20);
        }
        
        return min($priority, 100);
    }

    /**
     * حساب الوقت المقدر للمراجعة
     */
    private function calculateEstimatedReviewTime(): int
    {
        $baseTime = 15; // دقيقة
        
        // زيادة حسب عدد الأخطاء
        $baseTime += min($this->mistakes_count, 20);
        
        // زيادة حسب ضعف الجودة
        if ($this->recitation_quality && $this->recitation_quality < 7) {
            $baseTime += (7 - $this->recitation_quality) * 3;
        }
        
        return min($baseTime, 60);
    }

    /**
     * تحديد ما إذا كانت تحتاج مراجعة
     */
    private function shouldRequireReview(): bool
    {
        return $this->mistakes_count > 3 
            || ($this->recitation_quality && $this->recitation_quality < 7)
            || $this->completion_percentage < 70;
    }
}
