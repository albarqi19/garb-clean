<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialCustodyItem extends Model
{
    use HasFactory;

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'financial_custody_id',
        'custody_category_id',
        'description',
        'amount',
        'notes',
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
     * العهدة المالية التي ينتمي إليها هذا البند
     */
    public function financialCustody(): BelongsTo
    {
        return $this->belongsTo(FinancialCustody::class);
    }

    /**
     * فئة البند
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CustodyCategory::class, 'custody_category_id');
    }

    /**
     * الإيصالات المرتبطة بهذا البند
     */
    public function receipts(): HasMany
    {
        return $this->hasMany(CustodyReceipt::class, 'custody_item_id');
    }

    /**
     * حساب إجمالي المبلغ المستخدم لهذا البند
     */
    public function getUsedAmountAttribute(): float
    {
        return $this->receipts()->sum('amount');
    }

    /**
     * حساب المبلغ المتبقي لهذا البند
     */
    public function getRemainingAmountAttribute(): float
    {
        return $this->amount - $this->used_amount;
    }

    /**
     * التحقق مما إذا كان هذا البند قد تم استخدام كامل مبلغه
     */
    public function getIsFullyUtilizedAttribute(): bool
    {
        return $this->remaining_amount <= 0;
    }
}
