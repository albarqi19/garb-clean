<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicTerm extends Model
{
    use HasFactory;
    
    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'academic_calendar_id',
        'name',
        'start_date',
        'end_date',
        'is_current',
        'notes',
    ];
    
    /**
     * الخصائص التي يجب تحويلها إلى أنواع محددة.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
    ];
    
    /**
     * علاقة: الفصل الدراسي ينتمي لتقويم دراسي
     */
    public function academicCalendar(): BelongsTo
    {
        return $this->belongsTo(AcademicCalendar::class);
    }
    
    /**
     * نطاق: الحصول على الفصل الدراسي الحالي النشط فقط
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }
    
    /**
     * معرفة ما إذا كان التاريخ المحدد يقع ضمن فترة الفصل الدراسي
     */
    public function isDateWithinTerm($date): bool
    {
        $checkDate = $date instanceof \DateTime ? $date : new \DateTime($date);
        $startDate = new \DateTime($this->start_date);
        $endDate = new \DateTime($this->end_date);
        
        return $checkDate >= $startDate && $checkDate <= $endDate;
    }
    
    /**
     * الحصول على عدد الأيام في الفصل الدراسي
     */
    public function getDaysInTermAttribute(): int
    {
        $startDate = new \DateTime($this->start_date);
        $endDate = new \DateTime($this->end_date);
        
        return $startDate->diff($endDate)->days + 1; // +1 لتضمين اليوم الأخير
    }
}
