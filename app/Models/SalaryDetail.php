<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryDetail extends Model
{
    use HasFactory;

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'salary_id',
        'description',
        'amount',
        'type',
    ];

    /**
     * الخصائص التي يجب تحويلها.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'float',
    ];

    /**
     * الراتب المرتبط بهذا التفصيل
     */
    public function salary(): BelongsTo
    {
        return $this->belongsTo(Salary::class);
    }

    /**
     * تحديد ما إذا كان هذا البند إضافة
     */
    public function getIsAdditionAttribute(): bool
    {
        return $this->type === 'إضافة';
    }

    /**
     * تحديد ما إذا كان هذا البند خصمًا
     */
    public function getIsDeductionAttribute(): bool
    {
        return $this->type === 'خصم';
    }

    /**
     * استرداد مبلغ البند بإشارة سالبة إذا كان خصمًا
     */
    public function getSignedAmountAttribute(): float
    {
        return $this->is_deduction ? -$this->amount : $this->amount;
    }
}
