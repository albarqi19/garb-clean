<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicCalendar extends Model
{
    use HasFactory;
    
    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'academic_year',
        'name',
        'start_date',
        'end_date',
        'is_current',
        'description',
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
     * علاقة: التقويم الدراسي له العديد من الفصول الدراسية
     */
    public function academicTerms(): HasMany
    {
        return $this->hasMany(AcademicTerm::class);
    }
    
    /**
     * علاقة: التقويم الدراسي له العديد من الإجازات
     */
    public function holidays(): HasMany
    {
        return $this->hasMany(Holiday::class);
    }
    
    /**
     * علاقة: التقويم الدراسي له العديد من أيام التعطيل
     */
    public function nonAttendanceDays(): HasMany
    {
        return $this->hasMany(NonAttendanceDay::class);
    }
    
    /**
     * نطاق: الحصول على التقويم الحالي النشط فقط
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }
    
    /**
     * معرفة ما إذا كان التاريخ المحدد يقع ضمن فترة التقويم
     */
    public function isDateWithinCalendar($date): bool
    {
        $checkDate = $date instanceof \DateTime ? $date : new \DateTime($date);
        $startDate = new \DateTime($this->start_date);
        $endDate = new \DateTime($this->end_date);
        
        return $checkDate >= $startDate && $checkDate <= $endDate;
    }
}
