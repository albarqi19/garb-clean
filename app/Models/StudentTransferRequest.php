<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentTransferRequest extends Model
{
    use HasFactory;

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'student_id',
        'current_circle_id',
        'current_circle_group_id',
        'requested_circle_id',
        'requested_circle_group_id',
        'requested_neighborhood',
        'request_date',
        'status',
        'transfer_reason',
        'notes',
        'requested_by',
        'response_date',
        'response_notes',
        'approved_by',
        'transfer_date',
    ];

    /**
     * الخصائص التي يجب تحويلها.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'request_date' => 'date',
        'response_date' => 'date',
        'transfer_date' => 'date',
    ];

    /**
     * الطالب المراد نقله
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * الحلقة الحالية للطالب
     */
    public function currentCircle(): BelongsTo
    {
        return $this->belongsTo(QuranCircle::class, 'current_circle_id');
    }

    /**
     * مجموعة الحلقة الحالية
     */
    public function currentCircleGroup(): BelongsTo
    {
        return $this->belongsTo(CircleGroup::class, 'current_circle_group_id');
    }

    /**
     * الحلقة المطلوب النقل إليها
     */
    public function requestedCircle(): BelongsTo
    {
        return $this->belongsTo(QuranCircle::class, 'requested_circle_id');
    }

    /**
     * مجموعة الحلقة المطلوبة
     */
    public function requestedCircleGroup(): BelongsTo
    {
        return $this->belongsTo(CircleGroup::class, 'requested_circle_group_id');
    }

    /**
     * المشرف الذي قدم الطلب
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * المستخدم الذي وافق على الطلب
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * أنشطة الطلب (تتبع مراحل الطلب)
     */
    public function activities(): HasMany
    {
        return $this->hasMany(StudentTransferRequestActivity::class, 'transfer_request_id');
    }

    /**
     * تحديث حالة الطلب وإضافة نشاط جديد
     *
     * @param string $status الحالة الجديدة
     * @param int|null $userId معرف المستخدم الذي قام بالتغيير
     * @param string|null $role دور المستخدم
     * @param string|null $notes ملاحظات
     * @return bool
     */
    public function updateStatus($status, $userId = null, $role = null, $notes = null): bool
    {
        // تحديث حالة الطلب
        $this->status = $status;
        
        // تحديث تاريخ الرد إذا كانت الحالة تمت المعالجة
        if (in_array($status, ['approved', 'rejected'])) {
            $this->response_date = now();
            if ($notes) {
                $this->response_notes = $notes;
            }
        }
        
        // تحديث تاريخ النقل إذا تم النقل
        if ($status === 'completed') {
            $this->transfer_date = now();
        }
        
        // حفظ التغييرات
        $saved = $this->save();
        
        // تحويل الحالة إلى نوع النشاط المناسب
        $activityType = match($status) {
            'pending' => 'تقديم الطلب', 
            'in_progress' => 'مراجعة الطلب',
            'approved' => 'موافقة نهائية',
            'rejected' => 'رفض الطلب',
            'completed' => 'تنفيذ النقل',
            default => 'مراجعة الطلب'
        };
        
        // إضافة نشاط جديد
        $this->activities()->create([
            'activity_type' => $activityType,
            'user_id' => $userId,
            'activity_role' => $role,
            'notes' => $notes,
        ]);
        
        return $saved;
    }
    
    /**
     * تنفيذ النقل وتحديث بيانات الطالب
     * 
     * @return bool
     */
    public function executeTransfer(): bool
    {
        // الحصول على الطالب
        $student = $this->student;
        if (!$student) {
            return false;
        }
        
        // تحديث بيانات الطالب
        $student->quran_circle_id = $this->requested_circle_id;
        
        // تحديث مجموعة الحلقة إذا كانت محددة
        if ($this->requested_circle_group_id) {
            $student->circle_group_id = $this->requested_circle_group_id;
        }
        
        // حفظ تغييرات الطالب
        $studentSaved = $student->save();
        
        // تحديث حالة الطلب
        $requestSaved = $this->updateStatus('completed', $this->approved_by, 'مدير النظام', 'تم تنفيذ النقل');
        
        return $studentSaved && $requestSaved;
    }
}
