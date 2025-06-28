<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StrategicIndicator extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'strategic_plan_id',
        'code',
        'name',
        'description',
        'reference_number',
        'target_value',
        'result_type',
        'monitoring_type',
        'unit',
        'responsible_department',
        'created_by',
    ];

    /**
     * الخصائص التي يجب تحويلها.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'target_value' => 'decimal:2',
    ];

    /**
     * الحصول على الخطة الاستراتيجية للمؤشر.
     */
    public function strategicPlan(): BelongsTo
    {
        return $this->belongsTo(StrategicPlan::class);
    }

    /**
     * الحصول على عمليات رصد المؤشر.
     */
    public function monitorings(): HasMany
    {
        return $this->hasMany(StrategicMonitoring::class);
    }

    /**
     * الحصول على المستخدم الذي أنشأ المؤشر.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * الحصول على آخر عملية رصد للمؤشر.
     *
     * @param int|null $year السنة المطلوبة
     * @param string|null $quarter الربع المطلوب
     * @return StrategicMonitoring|null
     */
    public function getLatestMonitoring(?int $year = null, ?string $quarter = null)
    {
        $query = $this->monitorings();
        
        if ($year) {
            $query->where('year', $year);
        }
        
        if ($quarter) {
            $query->where('period', $quarter);
        }
        
        return $query->latest()->first();
    }

    /**
     * حساب متوسط الإنجاز للمؤشر في سنة معينة.
     *
     * @param int|null $year السنة المطلوبة
     * @return float متوسط نسبة الإنجاز
     */
    public function calculateAverageAchievement(?int $year = null): float
    {
        $year = $year ?? date('Y');
        
        $monitorings = $this->monitorings()
            ->where('year', $year)
            ->get();
            
        if ($monitorings->isEmpty()) {
            return 0;
        }
        
        return $monitorings->avg('achievement_percentage');
    }

    /**
     * التحقق مما إذا كان المؤشر تراكميًا.
     *
     * @return bool
     */
    public function isCumulative(): bool
    {
        return $this->monitoring_type === 'cumulative';
    }

    /**
     * التحقق مما إذا كانت نتيجة المؤشر نسبة مئوية.
     *
     * @return bool
     */
    public function isPercentageResult(): bool
    {
        return $this->result_type === 'percentage';
    }
}
