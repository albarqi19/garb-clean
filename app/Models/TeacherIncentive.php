<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherIncentive extends Model
{
    use HasFactory;

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'circle_incentive_id',
        'teacher_id',
        'amount',
        'reason',
        'approved_by',
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
     * حافز الحلقة المرتبط بهذا الحافز
     */
    public function circleIncentive(): BelongsTo
    {
        return $this->belongsTo(CircleIncentive::class);
    }

    /**
     * المعلم المستفيد من الحافز
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * الشخص الذي وافق على الحافز
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * إلغاء هذا الحافز وإرجاع المبلغ إلى حافز الحلقة
     */
    public function cancel(): bool
    {
        // الحصول على حافز الحلقة
        $circleIncentive = $this->circleIncentive;

        if (!$circleIncentive) {
            return false;
        }

        // إرجاع المبلغ إلى حافز الحلقة
        $circleIncentive->remaining_amount += $this->amount;
        $circleIncentive->save();

        // حذف هذا الحافز
        return $this->delete();
    }
}
