<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Attendance extends Model
{
    use HasFactory;

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'attendable_id',
        'attendable_type',
        'date',
        'period',
        'status',
        'check_in',
        'check_out',
        'notes',
    ];

    /**
     * الخصائص التي يجب تحويلها.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'check_in' => 'datetime',
        'check_out' => 'datetime',
    ];

    /**
     * الشخص الذي يتم تتبع حضوره (معلم أو موظف)
     */
    public function attendable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * تحديد ما إذا كان الحضور يعتبر غائبًا
     */
    public function getIsAbsentAttribute(): bool
    {
        return $this->status === 'غائب';
    }

    /**
     * تحديد ما إذا كان الحضور يعتبر متأخرًا
     */
    public function getIsLateAttribute(): bool
    {
        return $this->status === 'متأخر';
    }

    /**
     * تحديد ما إذا كان الحضور مع إذن
     */
    public function getIsPermittedAttribute(): bool
    {
        return $this->status === 'مأذون';
    }

    /**
     * تحديد ما إذا كان الحضور يستحق المكافأة
     * الحضور العادي والمتأخر يستحقان المكافأة، أما الغائب والمأذون فلا يستحقان
     */
    public function getIsEligibleForPaymentAttribute(): bool
    {
        return in_array($this->status, ['حاضر', 'متأخر']);
    }

    /**
     * الحصول على سجلات الحضور للشخص في فترة محددة
     *
     * @param string $attendableType نوع الشخص ('App\Models\Teacher' أو 'App\Models\Employee')
     * @param int $attendableId معرف الشخص
     * @param string $period الفترة ('الفجر', 'العصر', 'المغرب', 'العشاء')
     * @param \DateTime $startDate تاريخ البداية
     * @param \DateTime $endDate تاريخ النهاية
     * @return \Illuminate\Database\Eloquent\Collection سجلات الحضور
     */
    public static function getAttendanceRecords($attendableType, $attendableId, $period, $startDate, $endDate)
    {
        return self::where('attendable_type', $attendableType)
            ->where('attendable_id', $attendableId)
            ->where('period', $period)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();
    }

    /**
     * حساب عدد أيام الحضور المؤهلة للدفع في فترة محددة
     *
     * @param string $attendableType نوع الشخص ('App\Models\Teacher' أو 'App\Models\Employee')
     * @param int $attendableId معرف الشخص
     * @param string $period الفترة ('الفجر', 'العصر', 'المغرب', 'العشاء')
     * @param \DateTime $startDate تاريخ البداية
     * @param \DateTime $endDate تاريخ النهاية
     * @return int عدد أيام الحضور المؤهلة للدفع
     */
    public static function countEligibleDays($attendableType, $attendableId, $period, $startDate, $endDate)
    {
        return self::where('attendable_type', $attendableType)
            ->where('attendable_id', $attendableId)
            ->where('period', $period)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('status', ['حاضر', 'متأخر'])
            ->count();
    }
}
