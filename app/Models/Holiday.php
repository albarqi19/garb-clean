<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Holiday extends Model
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
        'is_official',
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
        'is_official' => 'boolean',
    ];
    
    /**
     * علاقة: الإجازة تنتمي لتقويم دراسي
     */
    public function academicCalendar(): BelongsTo
    {
        return $this->belongsTo(AcademicCalendar::class);
    }
    
    /**
     * الحصول على مدة الإجازة بالأيام
     */
    public function getDurationInDaysAttribute(): int
    {
        $startDate = new \DateTime($this->start_date);
        $endDate = new \DateTime($this->end_date);
        
        return $startDate->diff($endDate)->days + 1; // +1 لتضمين اليوم الأخير
    }
    
    /**
     * نطاق: الحصول على الإجازات المستقبلية فقط
     */
    public function scopeFuture($query)
    {
        return $query->where('start_date', '>=', now());
    }
    
    /**
     * نطاق: الحصول على الإجازات الرسمية فقط
     */
    public function scopeOfficial($query)
    {
        return $query->where('is_official', true);
    }
}
