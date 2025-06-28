<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherEvaluation extends Model
{
    use HasFactory;

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'teacher_id',
        'performance_score',
        'attendance_score',
        'student_interaction_score',
        'behavior_cooperation_score',
        'memorization_recitation_score',
        'general_evaluation_score',
        'total_score',
        'evaluation_date',
        'evaluation_period',
        'notes',
        'evaluator_id',
        'evaluator_role',
        'status',
    ];

    /**
     * تحويل الخصائص.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'evaluation_date' => 'date',
        'performance_score' => 'decimal:1',
        'attendance_score' => 'decimal:1',
        'student_interaction_score' => 'decimal:1',
        'behavior_cooperation_score' => 'decimal:1',
        'memorization_recitation_score' => 'decimal:1',
        'general_evaluation_score' => 'decimal:1',
        'total_score' => 'decimal:1',
    ];

    /**
     * المعلم المُقيَّم
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * المُقيِّم (المستخدم الذي قام بالتقييم)
     */
    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    /**
     * حساب النتيجة الإجمالية تلقائياً
     */
    public function calculateTotalScore(): float
    {
        return $this->performance_score + 
               $this->attendance_score + 
               $this->student_interaction_score + 
               $this->behavior_cooperation_score + 
               $this->memorization_recitation_score + 
               $this->general_evaluation_score;
    }

    /**
     * تحديث النتيجة الإجمالية
     */
    public function updateTotalScore(): void
    {
        $this->total_score = $this->calculateTotalScore();
        $this->save();
    }

    /**
     * الحصول على درجة التقييم كنسبة مئوية
     */
    public function getPercentageAttribute(): float
    {
        return $this->total_score;
    }

    /**
     * الحصول على تصنيف الأداء
     */
    public function getPerformanceGradeAttribute(): string
    {
        $score = $this->total_score;
        
        return match (true) {
            $score >= 90 => 'ممتاز',
            $score >= 80 => 'جيد جداً',
            $score >= 70 => 'جيد',
            $score >= 60 => 'مقبول',
            default => 'ضعيف'
        };
    }

    /**
     * الحصول على لون التقييم
     */
    public function getGradeColorAttribute(): string
    {
        $score = $this->total_score;
        
        return match (true) {
            $score >= 90 => 'success',
            $score >= 80 => 'primary',
            $score >= 70 => 'info',
            $score >= 60 => 'warning',
            default => 'danger'
        };
    }

    /**
     * تحديث النتيجة الإجمالية عند الحفظ
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($evaluation) {
            $evaluation->total_score = $evaluation->calculateTotalScore();
        });
    }

    /**
     * Scope للتقييمات المكتملة
     */
    public function scopeCompleted($query)
    {
        return $query->whereIn('status', ['مكتمل', 'معتمد']);
    }

    /**
     * Scope للتقييمات حسب الفترة
     */
    public function scopeByPeriod($query, $period)
    {
        return $query->where('evaluation_period', $period);
    }

    /**
     * Scope لآخر تقييم للمعلم
     */
    public function scopeLatestForTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId)
                    ->orderBy('evaluation_date', 'desc')
                    ->first();
    }
}
