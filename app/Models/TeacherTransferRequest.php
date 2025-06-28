<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeacherTransferRequest extends Model
{
    use HasFactory;

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'teacher_id',
        'current_circle_id',
        'requested_circle_id',
        'current_mosque_id',
        'requested_mosque_id',
        'requested_neighborhood',
        'request_date',
        'preferred_time',
        'status',
        'transfer_reason',
        'notes',
        'response_date',
        'response_notes',
        'approved_by',
        'transfer_date',
        'has_appointment_decision',
        'appointment_decision_number',
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
        'has_appointment_decision' => 'boolean',
    ];

    /**
     * المعلم مقدم الطلب
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * الحلقة الحالية للمعلم
     */
    public function currentCircle(): BelongsTo
    {
        return $this->belongsTo(QuranCircle::class, 'current_circle_id');
    }

    /**
     * الحلقة المطلوب النقل إليها
     */
    public function requestedCircle(): BelongsTo
    {
        return $this->belongsTo(QuranCircle::class, 'requested_circle_id');
    }

    /**
     * المسجد الحالي للمعلم
     */
    public function currentMosque(): BelongsTo
    {
        return $this->belongsTo(Mosque::class, 'current_mosque_id');
    }

    /**
     * المسجد المطلوب النقل إليه
     */
    public function requestedMosque(): BelongsTo
    {
        return $this->belongsTo(Mosque::class, 'requested_mosque_id');
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
        return $this->hasMany(TeacherTransferRequestActivity::class, 'transfer_request_id');
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
        if (in_array($status, ['موافقة مبدئية', 'موافقة نهائية', 'مرفوض'])) {
            $this->response_date = now();
            if ($notes) {
                $this->response_notes = $notes;
            }
        }
        
        // تحديث تاريخ النقل إذا تم النقل
        if ($status === 'تم النقل') {
            $this->transfer_date = now();
        }
        
        // حفظ التغييرات
        $saved = $this->save();
        
        // تحويل الحالة إلى نوع النشاط المناسب
        $activityType = match($status) {
            'قيد المراجعة' => 'مراجعة الطلب',
            'موافقة مبدئية' => 'موافقة مبدئية',
            'موافقة نهائية' => 'موافقة نهائية',
            'مرفوض' => 'رفض الطلب',
            'ملغي' => 'إلغاء الطلب',
            'تم النقل' => 'تنفيذ النقل',
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
     * تنفيذ النقل وتحديث بيانات المعلم
     * 
     * @return bool
     */
    public function executeTransfer(): bool
    {
        // الحصول على المعلم
        $teacher = $this->teacher;
        if (!$teacher) {
            return false;
        }
        
        // تحديث بيانات المعلم
        $teacher->quran_circle_id = $this->requested_circle_id;
        
        // تحديث المسجد إذا كان مختلفاً
        if ($this->requested_mosque_id) {
            $teacher->mosque_id = $this->requested_mosque_id;
        }
        
        // تحديث وقت العمل إذا تم تحديده
        if ($this->preferred_time) {
            $teacher->work_time = $this->preferred_time;
        }
        
        // حفظ تغييرات المعلم
        $teacherSaved = $teacher->save();
        
        // تحديث حالة الطلب
        $requestSaved = $this->updateStatus('تم النقل', $this->approved_by, 'مدير النظام', 'تم تنفيذ النقل');
        
        return $teacherSaved && $requestSaved;
    }
}
