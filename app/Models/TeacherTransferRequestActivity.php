<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherTransferRequestActivity extends Model
{
    use HasFactory;

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'transfer_request_id',
        'activity_type',
        'user_id',
        'activity_role',
        'notes',
    ];

    /**
     * طلب النقل المرتبط بالنشاط
     */
    public function transferRequest(): BelongsTo
    {
        return $this->belongsTo(TeacherTransferRequest::class, 'transfer_request_id');
    }

    /**
     * المستخدم الذي قام بالنشاط
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * الحصول على وصف قابل للقراءة للنشاط
     *
     * @return string
     */
    public function getActivityDescriptionAttribute(): string
    {
        $description = match($this->activity_type) {
            'تقديم الطلب' => 'تم تقديم طلب نقل جديد',
            'مراجعة الطلب' => 'تمت مراجعة الطلب',
            'تعديل الطلب' => 'تم تعديل بيانات الطلب',
            'موافقة مبدئية' => 'تمت الموافقة المبدئية على الطلب',
            'موافقة نهائية' => 'تمت الموافقة النهائية على الطلب',
            'رفض الطلب' => 'تم رفض الطلب',
            'إلغاء الطلب' => 'تم إلغاء الطلب بواسطة مقدم الطلب',
            'تنفيذ النقل' => 'تم تنفيذ النقل',
            default => 'إجراء على الطلب'
        };

        // إضافة اسم المستخدم ودوره إلى الوصف إذا كان متوفرًا
        if ($this->user) {
            $role = $this->activity_role ?: 'مستخدم';
            $description .= ' بواسطة ' . $this->user->name . ' (' . $role . ')';
        }

        return $description;
    }

    /**
     * دالة مساعدة لإضافة نشاط جديد لطلب نقل
     *
     * @param TeacherTransferRequest $request طلب النقل
     * @param string $activityType نوع النشاط
     * @param int|null $userId معرف المستخدم
     * @param string|null $role دور المستخدم
     * @param string|null $notes ملاحظات إضافية
     * @return TeacherTransferRequestActivity
     */
    public static function addActivity(TeacherTransferRequest $request, $activityType, $userId = null, $role = null, $notes = null): self
    {
        return self::create([
            'transfer_request_id' => $request->id,
            'activity_type' => $activityType,
            'user_id' => $userId,
            'activity_role' => $role,
            'notes' => $notes,
        ]);
    }
}
