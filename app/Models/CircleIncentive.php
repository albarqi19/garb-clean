<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CircleIncentive extends Model
{
    use HasFactory;

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'quran_circle_id',
        'sponsor_name',
        'amount',
        'remaining_amount',
        'allocation_date',
        'month',
        'academic_term_id',
        'notes',
        'is_blocked', // حقل جديد للإشارة إلى منع الصرف بسبب عدم وجود فائض كافي
    ];

    /**
     * الخصائص التي يجب تحويلها.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'float',
        'remaining_amount' => 'float',
        'allocation_date' => 'date',
        'is_blocked' => 'boolean',
    ];

    /**
     * الحلقة المستفيدة من الحافز
     */
    public function quranCircle(): BelongsTo
    {
        return $this->belongsTo(QuranCircle::class);
    }

    /**
     * الفصل الدراسي المرتبط بالحافز
     */
    public function academicTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    /**
     * حوافز المعلمين المرتبطة بهذا الحافز
     */
    public function teacherIncentives(): HasMany
    {
        return $this->hasMany(TeacherIncentive::class);
    }

    /**
     * توزيع مبلغ من الحافز على معلم محدد
     *
     * @param int $teacherId معرف المعلم
     * @param float $amount المبلغ المراد توزيعه
     * @param string|null $reason سبب الحافز
     * @param int|null $approvedBy معرف الشخص الذي وافق على الحافز
     * @param string|null $notes ملاحظات إضافية
     * @return \App\Models\TeacherIncentive|false
     */
    public function distributeToTeacher($teacherId, $amount, $reason = null, $approvedBy = null, $notes = null)
    {
        // التحقق من أن المبلغ المراد توزيعه لا يتجاوز المبلغ المتبقي
        if ($amount > $this->remaining_amount) {
            return false;
        }
        
        // التحقق من عدم وجود منع للصرف
        if ($this->is_blocked) {
            return false;
        }
        
        // التحقق من وجود فائض كافي في ميزانية الحلقة
        $circleBudget = CircleBudget::where('quran_circle_id', $this->quran_circle_id)
                                ->where('academic_term_id', $this->academic_term_id)
                                ->first();
        
        if (!$circleBudget || !$circleBudget->has_surplus || $circleBudget->surplus_amount < $amount) {
            // لا يوجد فائض كافي، منع الصرف
            return false;
        }

        // إنشاء حافز للمعلم
        $teacherIncentive = $this->teacherIncentives()->create([
            'teacher_id' => $teacherId,
            'amount' => $amount,
            'reason' => $reason,
            'approved_by' => $approvedBy,
            'notes' => $notes,
        ]);

        // تحديث المبلغ المتبقي
        $this->remaining_amount -= $amount;
        $this->save();

        return $teacherIncentive;
    }

    /**
     * التحقق من إمكانية صرف حوافز إضافية بناءً على الحالة المالية للحلقة
     *
     * @return bool
     */
    public function canDistributeIncentives(): bool
    {
        if ($this->is_blocked) {
            return false;
        }
        
        $circleBudget = CircleBudget::where('quran_circle_id', $this->quran_circle_id)
                                ->where('academic_term_id', $this->academic_term_id)
                                ->first();
        
        return $circleBudget && $circleBudget->has_surplus && $circleBudget->surplus_amount > 0;
    }
    
    /**
     * تحديث حالة الحافز بناءً على الحالة المالية للحلقة
     *
     * @return bool
     */
    public function updateBlockStatus(): bool
    {
        $circleBudget = CircleBudget::where('quran_circle_id', $this->quran_circle_id)
                                ->where('academic_term_id', $this->academic_term_id)
                                ->first();
        
        $shouldBlock = !$circleBudget || !$circleBudget->has_surplus || $circleBudget->is_at_risk;
        
        $this->is_blocked = $shouldBlock;
        $this->save();
        
        return !$shouldBlock;
    }
    
    /**
     * تحديث حالات جميع الحوافز
     *
     * @return int عدد السجلات التي تم تحديثها
     */
    public static function updateAllIncentiveStatuses(): int
    {
        $count = 0;
        $incentives = self::all();
        
        foreach ($incentives as $incentive) {
            $incentive->updateBlockStatus();
            $count++;
        }
        
        return $count;
    }

    /**
     * الحصول على إجمالي المبالغ الموزعة على المعلمين
     */
    public function getDistributedAmountAttribute(): float
    {
        return $this->teacherIncentives()->sum('amount');
    }

    /**
     * الحصول على النسبة المئوية للمبلغ الموزع
     */
    public function getDistributionPercentageAttribute(): float
    {
        if ($this->amount <= 0) {
            return 0;
        }

        return ($this->distributed_amount / $this->amount) * 100;
    }
    
    /**
     * نطاق الاستعلام للحوافز المتاحة
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_blocked', false)->where('remaining_amount', '>', 0);
    }
    
    /**
     * نطاق الاستعلام للحوافز المحظورة
     */
    public function scopeBlocked($query)
    {
        return $query->where('is_blocked', true);
    }
}
