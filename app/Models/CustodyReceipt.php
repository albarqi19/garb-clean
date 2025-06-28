<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustodyReceipt extends Model
{
    use HasFactory;

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'financial_custody_id',
        'custody_item_id',
        'receipt_number',
        'supplier_name',
        'tax_number',
        'is_tax_invoice',
        'amount',
        'receipt_date',
        'receipt_file_path',
        'notes',
    ];

    /**
     * الخصائص التي يجب تحويلها.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_tax_invoice' => 'boolean',
        'amount' => 'float',
        'receipt_date' => 'date',
    ];

    /**
     * العهدة المالية التي ينتمي إليها هذا الإيصال
     */
    public function financialCustody(): BelongsTo
    {
        return $this->belongsTo(FinancialCustody::class);
    }

    /**
     * بند العهدة المرتبط بهذا الإيصال
     */
    public function custodyItem(): BelongsTo
    {
        return $this->belongsTo(FinancialCustodyItem::class, 'custody_item_id');
    }

    /**
     * التحقق مما إذا كان الإيصال يتجاوز سقف 2000 ريال
     */
    public function getIsExceedingLimitAttribute(): bool
    {
        return $this->amount > 2000;
    }

    /**
     * التحقق مما إذا كان الإيصال مقبولًا
     * (فاتورة ضريبية إذا كان المبلغ أكثر من 2000 ريال)
     */
    public function getIsValidAttribute(): bool
    {
        // إذا كان المبلغ أكثر من 2000 ريال، يجب أن تكون فاتورة ضريبية
        if ($this->is_exceeding_limit) {
            return $this->is_tax_invoice && $this->tax_number;
        }
        
        // إذا كان المبلغ أقل من أو يساوي 2000 ريال، فهو مقبول بغض النظر
        return true;
    }

    /**
     * تحميل ملف الإيصال
     *
     * @param \Illuminate\Http\UploadedFile $file الملف المرفوع
     * @return string مسار الملف المحفوظ
     */
    public function uploadReceiptFile($file): string
    {
        $path = $file->store('custody_receipts', 'public');
        $this->receipt_file_path = $path;
        $this->save();
        
        return $path;
    }
}
