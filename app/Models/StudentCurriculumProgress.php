<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentCurriculumProgress extends Model
{
    use HasFactory;
    
    /**
     * تحديد اسم الجدول
     */
    protected $table = 'student_curriculum_progress';
    
    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'student_curriculum_id',
        'curriculum_plan_id',
        'start_date',
        'completion_date',
        'status',
        'completion_percentage',
        'teacher_notes',
    ];
    
    /**
     * الخصائص التي يجب تحويلها إلى أنواع محددة.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'completion_date' => 'date',
        'completion_percentage' => 'float',
    ];
    
    /**
     * العلاقة: تقدم الخطة ينتمي إلى تقدم منهج الطالب
     */
    public function studentCurriculum(): BelongsTo
    {
        return $this->belongsTo(StudentCurriculum::class);
    }
    
    /**
     * العلاقة: تقدم الخطة ينتمي إلى خطة منهجية
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(CurriculumPlan::class, 'curriculum_plan_id');
    }
    
    /**
     * نطاق: الخطط قيد التنفيذ فقط
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'قيد التنفيذ');
    }
    
    /**
     * نطاق: الخطط المكتملة فقط
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'مكتمل');
    }
    
    /**
     * تحديث حالة الخطة إلى مكتملة وتحديث تقدم المنهج
     */
    public function markAsCompleted(): void
    {
        $this->status = 'مكتمل';
        $this->completion_date = now();
        $this->completion_percentage = 100;
        $this->save();
        
        // تحديث نسبة إكمال المنهج
        $this->studentCurriculum->updateCompletionPercentage();
    }
    
    /**
     * تحديث نسبة الإكمال وتحديث تقدم المنهج
     */
    public function updateProgress(float $percentage): void
    {
        $this->completion_percentage = $percentage;
        
        if ($percentage >= 100) {
            $this->status = 'مكتمل';
            $this->completion_date = now();
        }
        
        $this->save();
        
        // تحديث نسبة إكمال المنهج
        $this->studentCurriculum->updateCompletionPercentage();
    }
}
