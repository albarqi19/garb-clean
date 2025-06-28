<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StrategicInitiative extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'strategic_monitoring_id',
        'name',
        'description',
        'start_date',
        'end_date',
        'status',
        'responsible_id',
        'progress_percentage',
        'notes',
        'created_by',
    ];

    /**
     * الخصائص التي يجب تحويلها.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'progress_percentage' => 'decimal:2',
    ];

    /**
     * الحصول على عملية الرصد المرتبطة بالمبادرة.
     */
    public function strategicMonitoring(): BelongsTo
    {
        return $this->belongsTo(StrategicMonitoring::class);
    }

    /**
     * الحصول على المستخدم المسؤول عن المبادرة.
     */
    public function responsible()
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    /**
     * الحصول على المستخدم الذي أنشأ المبادرة.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * الحصول على المؤشر المرتبط بالمبادرة.
     */
    public function getStrategicIndicatorAttribute()
    {
        $monitoring = $this->strategicMonitoring;
        return $monitoring ? $monitoring->strategicIndicator : null;
    }

    /**
     * تحديد ما إذا كانت المبادرة متأخرة.
     *
     * @return bool هل المبادرة متأخرة؟
     */
    public function isDelayed(): bool
    {
        if (!$this->end_date || $this->status === 'completed') {
            return false;
        }
        
        return now()->gt($this->end_date) && $this->progress_percentage < 100;
    }

    /**
     * تنسيق حالة المبادرة لعرضها باللغة العربية.
     */
    public function getFormattedStatusAttribute(): string
    {
        switch ($this->status) {
            case 'planned':
                return 'مخطط';
            case 'in_progress':
                return 'قيد التنفيذ';
            case 'completed':
                return 'مكتملة';
            case 'delayed':
                return 'متأخرة';
            case 'cancelled':
                return 'ملغاة';
            default:
                return $this->status;
        }
    }

    /**
     * تحديد لون حالة المبادرة.
     */
    public function getStatusColorAttribute(): string
    {
        switch ($this->status) {
            case 'planned':
                return 'blue';
            case 'in_progress':
                return 'yellow';
            case 'completed':
                return 'green';
            case 'delayed':
                return 'red';
            case 'cancelled':
                return 'gray';
            default:
                return 'gray';
        }
    }
}
