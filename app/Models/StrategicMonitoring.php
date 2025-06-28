<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StrategicMonitoring extends Model
{
    use HasFactory;

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'strategic_indicator_id',
        'period',
        'year',
        'achieved_value',
        'achievement_percentage',
        'notes',
        'created_by',
    ];

    /**
     * الخصائص التي يجب تحويلها.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'achieved_value' => 'decimal:2',
        'achievement_percentage' => 'decimal:2',
    ];

    /**
     * الحصول على المؤشر الاستراتيجي المرتبط بعملية الرصد.
     */
    public function strategicIndicator(): BelongsTo
    {
        return $this->belongsTo(StrategicIndicator::class);
    }

    /**
     * الحصول على المبادرات المرتبطة بعملية الرصد.
     */
    public function initiatives(): HasMany
    {
        return $this->hasMany(StrategicInitiative::class);
    }

    /**
     * الحصول على المستخدم الذي سجل عملية الرصد.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * حساب نسبة الإنجاز بناءً على القيمة المتحققة والقيمة المستهدفة.
     */
    public function calculateAchievementPercentage(): float
    {
        $indicator = $this->strategicIndicator;
        
        if (!$indicator || $indicator->target_value <= 0) {
            return 0;
        }
        
        // إذا كان المؤشر تراكمي، نحتاج إلى الحصول على مجموع القيم المتحققة من الفترات السابقة
        $achievedValue = $this->achieved_value;
        
        if ($indicator->monitoring_type === 'cumulative') {
            // الحصول على مجموع القيم المتحققة من الفترات السابقة في نفس السنة
            $periods = ['first_quarter', 'second_quarter', 'third_quarter', 'fourth_quarter'];
            $currentPeriodIndex = array_search($this->period, $periods);
            
            if ($currentPeriodIndex > 0) {
                $previousPeriods = array_slice($periods, 0, $currentPeriodIndex);
                
                $previousMonitorings = StrategicMonitoring::where('strategic_indicator_id', $indicator->id)
                    ->where('year', $this->year)
                    ->whereIn('period', $previousPeriods)
                    ->get();
                    
                foreach ($previousMonitorings as $monitoring) {
                    $achievedValue += $monitoring->achieved_value;
                }
            }
        }
        
        // حساب نسبة الإنجاز
        $achievementPercentage = ($achievedValue / $indicator->target_value) * 100;
        
        // إذا كان المؤشر من نوع النسبة المئوية، نستخدم القيمة المتحققة مباشرة
        if ($indicator->result_type === 'percentage') {
            $achievementPercentage = $this->achieved_value;
        }
        
        // تحديث نسبة الإنجاز في النموذج
        $this->achievement_percentage = $achievementPercentage;
        
        return $achievementPercentage;
    }

    /**
     * تنسيق فترة الرصد لعرضها باللغة العربية.
     */
    public function getFormattedPeriodAttribute(): string
    {
        switch ($this->period) {
            case 'first_quarter':
                return 'الربع الأول';
            case 'second_quarter':
                return 'الربع الثاني';
            case 'third_quarter':
                return 'الربع الثالث';
            case 'fourth_quarter':
                return 'الربع الرابع';
            default:
                return $this->period;
        }
    }

    /**
     * تنسيق نوع الرصد (تراكمي/غير تراكمي).
     */
    public function getFormattedMonitoringTypeAttribute(): string
    {
        $indicator = $this->strategicIndicator;
        
        if (!$indicator) {
            return '';
        }
        
        return $indicator->monitoring_type === 'cumulative' ? 'تراكمي' : 'غير تراكمي';
    }
}
