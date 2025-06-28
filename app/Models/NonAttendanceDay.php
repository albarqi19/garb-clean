<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NonAttendanceDay extends Model
{
    use HasFactory;
    
    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'academic_calendar_id',
        'date',
        'reason',
        'is_emergency',
        'is_makeup_required',
        'notes',
    ];
    
    /**
     * الخصائص التي يجب تحويلها إلى أنواع محددة.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'is_emergency' => 'boolean',
        'is_makeup_required' => 'boolean',
    ];
    
    /**
     * علاقة: يوم التعطيل ينتمي لتقويم دراسي
     */
    public function academicCalendar(): BelongsTo
    {
        return $this->belongsTo(AcademicCalendar::class);
    }
    
    /**
     * نطاق: الحصول على أيام التعطيل المستقبلية فقط
     */
    public function scopeFuture($query)
    {
        return $query->where('date', '>=', now());
    }
    
    /**
     * نطاق: الحصول على أيام التعطيل الطارئة فقط
     */
    public function scopeEmergency($query)
    {
        return $query->where('is_emergency', true);
    }
    
    /**
     * نطاق: الحصول على أيام التعطيل التي تتطلب تعويض
     */
    public function scopeRequiresMakeup($query)
    {
        return $query->where('is_makeup_required', true);
    }
}
