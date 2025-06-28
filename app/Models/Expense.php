<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory;

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'quran_circle_id',
        'amount',
        'expense_type_id',
        'month',
        'expense_date',
        'academic_term_id',
        'transaction_reference',
        'beneficiary_name',
        'is_for_center',
        'notes',
        'approved_by',
        'recorded_by',
    ];

    /**
     * الخصائص التي يجب تحويلها.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'float',
        'expense_date' => 'date',
        'is_for_center' => 'boolean',
    ];

    /**
     * الحلقة المرتبطة بالمصروف (إذا كان المصروف للحلقة)
     */
    public function quranCircle(): BelongsTo
    {
        return $this->belongsTo(QuranCircle::class);
    }

    /**
     * نوع المصروف
     */
    public function expenseType(): BelongsTo
    {
        return $this->belongsTo(ExpenseType::class);
    }

    /**
     * الفصل الدراسي المرتبط بالمصروف
     */
    public function academicTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    /**
     * الشخص الذي وافق على المصروف
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * الشخص الذي سجل المصروف
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * الحصول على إجمالي المصروفات لحلقة معينة في فترة محددة
     *
     * @param int $circleId معرف الحلقة
     * @param string $month الشهر
     * @param int|null $academicTermId معرف الفصل الدراسي (اختياري)
     * @return float
     */
    public static function getTotalForCircle($circleId, $month, $academicTermId = null): float
    {
        $query = self::where('quran_circle_id', $circleId)
            ->where('month', $month);
            
        if ($academicTermId) {
            $query->where('academic_term_id', $academicTermId);
        }
        
        return $query->sum('amount');
    }

    /**
     * الحصول على إجمالي المصروفات للمركز في فترة محددة
     *
     * @param string $month الشهر
     * @param int|null $academicTermId معرف الفصل الدراسي (اختياري)
     * @return float
     */
    public static function getTotalForCenter($month, $academicTermId = null): float
    {
        $query = self::where('is_for_center', true)
            ->where('month', $month);
            
        if ($academicTermId) {
            $query->where('academic_term_id', $academicTermId);
        }
        
        return $query->sum('amount');
    }

    /**
     * الحصول على إجمالي المصروفات حسب نوع المصروف في فترة محددة
     *
     * @param int $expenseTypeId معرف نوع المصروف
     * @param string $month الشهر
     * @param int|null $academicTermId معرف الفصل الدراسي (اختياري)
     * @return float
     */
    public static function getTotalByType($expenseTypeId, $month, $academicTermId = null): float
    {
        $query = self::where('expense_type_id', $expenseTypeId)
            ->where('month', $month);
            
        if ($academicTermId) {
            $query->where('academic_term_id', $academicTermId);
        }
        
        return $query->sum('amount');
    }
}
