<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class CircleBudget extends Model
{
    use HasFactory;

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'quran_circle_id',
        'academic_term_id',
        'total_budget',
        'salaries_budget',
        'initiatives_budget',
        'remaining_budget',
        'monthly_cost',
        'monthly_salaries_cost',
        'coverage_months',
        'coverage_end_date',
        'is_at_risk',
        'has_surplus',
        'surplus_amount',
        'notes',
    ];

    /**
     * الخصائص التي يجب تحويلها.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total_budget' => 'float',
        'salaries_budget' => 'float',
        'initiatives_budget' => 'float',
        'remaining_budget' => 'float',
        'monthly_cost' => 'float',
        'monthly_salaries_cost' => 'float',
        'coverage_months' => 'float',
        'coverage_end_date' => 'date',
        'is_at_risk' => 'boolean',
        'has_surplus' => 'boolean',
        'surplus_amount' => 'float',
    ];

    /**
     * الحلقة المرتبطة بالميزانية
     */
    public function quranCircle(): BelongsTo
    {
        return $this->belongsTo(QuranCircle::class);
    }

    /**
     * الفصل الدراسي المرتبط بالميزانية
     */
    public function academicTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    /**
     * حساب التكلفة الشهرية للحلقة بناءً على معلمين الحلقة وإعدادات التكلفة
     *
     * @return float التكلفة الشهرية الإجمالية
     */
    public function calculateMonthlyCost(): float
    {
        $totalCost = 0;
        $circleId = $this->quran_circle_id;
        
        // الحصول على معلمي الحلقة الحاليين
        $teachers = Teacher::where('quran_circle_id', $circleId)->get();
        
        foreach ($teachers as $teacher) {
            // حساب تكلفة المعلم الشهرية
            $cost = CircleCostSetting::getCost(
                $teacher->task_type, 
                $teacher->nationality, 
                $teacher->work_time, 
                $this->academic_term_id
            );
            
            $totalCost += $cost;
        }
        
        // تحديث حقول التكلفة الشهرية
        $this->monthly_cost = $totalCost;
        $this->monthly_salaries_cost = $totalCost; // يمكن تعديله لاحقاً إذا كان هناك تفاصيل إضافية
        
        $this->save();
        
        return $totalCost;
    }
    
    /**
     * حساب عدد أشهر التغطية المالية المتبقية للحلقة
     *
     * @return float عدد أشهر التغطية
     */
    public function calculateCoverageMonths(): float
    {
        // نتأكد أولاً من تحديث التكلفة الشهرية
        if ($this->monthly_cost <= 0) {
            $this->calculateMonthlyCost();
        }
        
        // تجنب القسمة على صفر
        if ($this->monthly_salaries_cost <= 0) {
            $this->coverage_months = 0;
            $this->coverage_end_date = null;
            $this->is_at_risk = false;
            $this->save();
            return 0;
        }
        
        // حساب عدد أشهر التغطية بقسمة المبلغ المتبقي على التكلفة الشهرية
        $coverageMonths = $this->remaining_budget / $this->monthly_salaries_cost;
        
        // تحديث حقل أشهر التغطية
        $this->coverage_months = $coverageMonths;
        
        // حساب تاريخ نهاية التغطية
        $months = floor($coverageMonths);
        $remainingDays = ($coverageMonths - $months) * 30; // تقريبي لأيام الشهر
        
        $this->coverage_end_date = Carbon::now()->addMonths($months)->addDays($remainingDays);
        
        // تحديد ما إذا كانت الحلقة في خطر مالي (تغطية أقل من 3 أشهر)
        $this->is_at_risk = $coverageMonths < 3;
        
        $this->save();
        
        return $coverageMonths;
    }
    
    /**
     * حساب الفائض المالي المتاح للعهد والحوافز
     *
     * @param int $minimumMonths الحد الأدنى لعدد أشهر التغطية المطلوبة (افتراضياً 12 شهراً)
     * @return float مبلغ الفائض المتاح
     */
    public function calculateSurplus(int $minimumMonths = 12): float
    {
        // نتأكد أولاً من تحديث عدد أشهر التغطية
        if ($this->coverage_months <= 0) {
            $this->calculateCoverageMonths();
        }
        
        // لا يوجد فائض إذا كانت التغطية أقل من الحد الأدنى المطلوب
        if ($this->coverage_months <= $minimumMonths) {
            $this->has_surplus = false;
            $this->surplus_amount = 0;
            $this->save();
            return 0;
        }
        
        // حساب الفائض كمبلغ زائد عن تغطية الحد الأدنى المطلوب من الأشهر
        $requiredAmount = $this->monthly_salaries_cost * $minimumMonths;
        $surplusAmount = $this->remaining_budget - $requiredAmount;
        
        // ضمان ألا يكون الفائض سالباً
        $surplusAmount = max(0, $surplusAmount);
        
        // تحديث حقول الفائض
        $this->has_surplus = $surplusAmount > 0;
        $this->surplus_amount = $surplusAmount;
        $this->save();
        
        return $surplusAmount;
    }
    
    /**
     * تحديث جميع حسابات الميزانية دفعة واحدة
     *
     * @param string $month الشهر الحالي
     * @param int $minimumMonths الحد الأدنى لعدد أشهر التغطية المطلوبة للفائض
     * @return self
     */
    public function updateFinancialStatus(string $month, int $minimumMonths = 12): self
    {
        // تحديث المبلغ المتبقي
        $this->calculateRemainingBudget($month);
        
        // حساب التكلفة الشهرية
        $this->calculateMonthlyCost();
        
        // حساب عدد أشهر التغطية
        $this->calculateCoverageMonths();
        
        // حساب الفائض المتاح
        $this->calculateSurplus($minimumMonths);
        
        return $this;
    }

    /**
     * حساب الميزانية المتبقية بناءً على المصروفات
     *
     * @param string $month الشهر الحالي
     * @return float المبلغ المتبقي من الميزانية
     */
    public function calculateRemainingBudget($month): float
    {
        // حساب إجمالي المصروفات لهذه الحلقة في الشهر المحدد
        $totalExpenses = Expense::getTotalForCircle($this->quran_circle_id, $month, $this->academic_term_id);
        
        // تحديث المبلغ المتبقي
        $this->remaining_budget = $this->total_budget - $totalExpenses;
        $this->save();
        
        return $this->remaining_budget;
    }

    /**
     * الحصول على نسبة استهلاك الميزانية
     */
    public function getConsumptionPercentageAttribute(): float
    {
        if ($this->total_budget <= 0) {
            return 0;
        }
        
        return 100 - (($this->remaining_budget / $this->total_budget) * 100);
    }

    /**
     * الحصول على نسبة توزيع ميزانية الرواتب من إجمالي الميزانية
     */
    public function getSalariesPercentageAttribute(): float
    {
        if ($this->total_budget <= 0) {
            return 0;
        }
        
        return ($this->salaries_budget / $this->total_budget) * 100;
    }

    /**
     * الحصول على نسبة توزيع ميزانية المبادرات من إجمالي الميزانية
     */
    public function getInitiativesPercentageAttribute(): float
    {
        if ($this->total_budget <= 0 || $this->initiatives_budget === null) {
            return 0;
        }
        
        return ($this->initiatives_budget / $this->total_budget) * 100;
    }
    
    /**
     * الإستعلام عن ميزانيات الحلقات التي في خطر مالي
     */
    public function scopeAtRisk($query)
    {
        return $query->where('is_at_risk', true);
    }
    
    /**
     * الإستعلام عن ميزانيات الحلقات التي لديها فائض
     */
    public function scopeWithSurplus($query)
    {
        return $query->where('has_surplus', true);
    }
    
    /**
     * تحديث الحالة المالية لجميع ميزانيات الحلقات
     *
     * @param string $month الشهر الحالي
     * @param int $minimumMonths الحد الأدنى لعدد أشهر التغطية المطلوبة للفائض
     * @return int عدد السجلات التي تم تحديثها
     */
    public static function updateAllFinancialStatuses(string $month, int $minimumMonths = 12): int
    {
        $count = 0;
        $budgets = self::all();
        
        foreach ($budgets as $budget) {
            $budget->updateFinancialStatus($month, $minimumMonths);
            $count++;
        }
        
        return $count;
    }
}
