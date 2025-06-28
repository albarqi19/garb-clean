<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Salary extends Model
{
    use HasFactory;

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'payee_id',
        'payee_type',
        'academic_term_id',
        'month',
        'base_amount',
        'attendance_days',
        'deductions',
        'bonuses',
        'total_amount',
        'payment_date',
        'is_paid',
        'payment_notes',
        'transaction_reference',
        'iban',
    ];

    /**
     * الخصائص التي يجب تحويلها.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'base_amount' => 'float',
        'deductions' => 'float',
        'bonuses' => 'float',
        'total_amount' => 'float',
        'payment_date' => 'date',
        'is_paid' => 'boolean',
    ];

    /**
     * الشخص المستلم للراتب (معلم أو موظف)
     */
    public function payee(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * الفصل الدراسي المرتبط بالراتب
     */
    public function academicTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    /**
     * تفاصيل الراتب (البنود المختلفة)
     */
    public function details(): HasMany
    {
        return $this->hasMany(SalaryDetail::class);
    }

    /**
     * إضافة تفصيل جديد للراتب (بند إضافة أو خصم)
     *
     * @param string $description وصف البند
     * @param float $amount المبلغ
     * @param string $type نوع البند ('إضافة' أو 'خصم')
     * @return \App\Models\SalaryDetail
     */
    public function addDetail($description, $amount, $type = 'إضافة')
    {
        return $this->details()->create([
            'description' => $description,
            'amount' => $amount,
            'type' => $type,
        ]);
    }

    /**
     * إعادة حساب المبلغ الإجمالي للراتب بناءً على التفاصيل
     *
     * @return float
     */
    public function recalculateTotal(): float
    {
        $additions = $this->details()->where('type', 'إضافة')->sum('amount');
        $deductions = $this->details()->where('type', 'خصم')->sum('amount');
        
        $this->bonuses = $additions;
        $this->deductions = $deductions;
        $this->total_amount = $this->base_amount + $additions - $deductions;
        $this->save();
        
        return $this->total_amount;
    }

    /**
     * تحديد حالة الدفع للراتب
     *
     * @param bool $isPaid حالة الدفع
     * @param string|null $transactionReference الرقم المرجعي للمعاملة
     * @param string|null $notes ملاحظات على الدفع
     * @return self
     */
    public function markAsPaid($isPaid = true, $transactionReference = null, $notes = null): self
    {
        $this->is_paid = $isPaid;
        
        if ($isPaid) {
            $this->payment_date = now();
            
            if ($transactionReference) {
                $this->transaction_reference = $transactionReference;
            }
            
            if ($notes) {
                $this->payment_notes = $notes;
            }
        }
        
        $this->save();
        
        return $this;
    }
}
