<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialCustody extends Model
{
    use HasFactory;

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'request_number',
        'requester_id',
        'requester_job_title',
        'mosque_id',
        'total_amount',
        'status',
        'request_date',
        'approval_date',
        'disbursement_date',
        'approved_by',
        'notes',
        'disbursement_method',
    ];

    /**
     * الخصائص التي يجب تحويلها.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'request_date' => 'date',
        'approval_date' => 'date',
        'disbursement_date' => 'date',
        'total_amount' => 'float',
    ];

    /**
     * مقدم طلب العهدة
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * المسجد المرتبط بالعهدة
     */
    public function mosque(): BelongsTo
    {
        return $this->belongsTo(Mosque::class);
    }

    /**
     * الشخص الذي اعتمد طلب العهدة
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * بنود العهدة المالية
     */
    public function items(): HasMany
    {
        return $this->hasMany(FinancialCustodyItem::class);
    }

    /**
     * إيصالات العهدة
     */
    public function receipts(): HasMany
    {
        return $this->hasMany(CustodyReceipt::class);
    }
    
    /**
     * إضافة بند جديد للعهدة
     *
     * @param int $categoryId معرف فئة البند
     * @param float $amount المبلغ
     * @param string|null $description وصف البند
     * @param string|null $notes ملاحظات إضافية
     * @return FinancialCustodyItem
     */
    public function addItem($categoryId, $amount, $description = null, $notes = null): FinancialCustodyItem
    {
        return $this->items()->create([
            'custody_category_id' => $categoryId,
            'amount' => $amount,
            'description' => $description,
            'notes' => $notes,
        ]);
    }

    /**
     * حساب إجمالي مبلغ العهدة من البنود
     *
     * @return float
     */
    public function calculateTotal(): float
    {
        $total = $this->items()->sum('amount');
        $this->total_amount = $total;
        $this->save();
        
        return $total;
    }

    /**
     * تحديث حالة العهدة
     *
     * @param string $status الحالة الجديدة للعهدة
     * @param int|null $approvedBy معرف المستخدم الذي اعتمد العهدة (إذا كانت الحالة "معتمد")
     * @return self
     */
    public function updateStatus($status, $approvedBy = null): self
    {
        $this->status = $status;
        
        if ($status === 'معتمد' && $approvedBy) {
            $this->approved_by = $approvedBy;
            $this->approval_date = now();
            
            // تحديد طريقة الاستلام بناءً على ما إذا كانت أول عهدة للمستخدم أم لا
            $this->disbursement_method = $this->isFirstCustodyForRequester() ? 'حضوري' : 'تحويل بنكي';
        }
        
        if ($status === 'تم الصرف') {
            $this->disbursement_date = now();
        }
        
        $this->save();
        
        return $this;
    }

    /**
     * توليد رقم طلب فريد
     *
     * @return string
     */
    public static function generateRequestNumber(): string
    {
        $latestCustody = self::latest()->first();
        $lastNumber = $latestCustody ? intval(substr($latestCustody->request_number, 5)) : 0;
        
        return 'CUSTO' . str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
    }

    /**
     * حساب إجمالي المبلغ المستخدم (من خلال الإيصالات)
     *
     * @return float
     */
    public function getUsedAmountAttribute(): float
    {
        return $this->receipts()->sum('amount');
    }

    /**
     * حساب المبلغ المتبقي من العهدة
     *
     * @return float
     */
    public function getRemainingAmountAttribute(): float
    {
        return $this->total_amount - $this->used_amount;
    }

    /**
     * التحقق مما إذا كانت هذه أول عهدة للمستخدم
     *
     * @return bool
     */
    public function isFirstCustodyForRequester(): bool
    {
        return self::where('requester_id', $this->requester_id)
            ->where('status', 'تم الصرف')
            ->where('id', '!=', $this->id)
            ->count() === 0;
    }

    /**
     * الحصول على طريقة الاستلام المناسبة للعهدة
     *
     * @return string
     */
    public function getAppropriateDeliveryMethod(): string
    {
        return $this->isFirstCustodyForRequester() 
            ? 'يجب الحضور شخصياً لاستلام العهدة'
            : 'سيتم تحويل المبلغ إلى الحساب البنكي';
    }
}
