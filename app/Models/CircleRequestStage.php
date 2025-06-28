<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CircleRequestStage extends Model
{
    use HasFactory;

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array
     */
    protected $fillable = [
        'request_id',
        // القسم التسويقي
        'marketing_status', 'mosque_verification_done', 'surrounding_circles_identified',
        'store_link_created', 'donor_search_done', 'marketing_notes', 'marketing_user_id',
        'marketing_completed_at',
        // القسم الإداري
        'administrative_status', 'budget_confirmation', 'documents_completed',
        'teacher_contracts_prepared', 'administrative_notes', 'administrative_user_id',
        'administrative_completed_at',
        // القسم التعليمي
        'educational_status', 'supervisor_assigned', 'teachers_assigned',
        'educational_plan_ready', 'educational_notes', 'educational_user_id',
        'educational_completed_at',
        // معلومات إنشاء الحلقة
        'circle_created', 'created_circle_id',
    ];

    /**
     * الخصائص التي يجب تحويلها.
     *
     * @var array
     */
    protected $casts = [
        'mosque_verification_done' => 'boolean',
        'surrounding_circles_identified' => 'boolean',
        'store_link_created' => 'boolean',
        'donor_search_done' => 'boolean',
        'budget_confirmation' => 'boolean',
        'documents_completed' => 'boolean',
        'teacher_contracts_prepared' => 'boolean',
        'supervisor_assigned' => 'boolean',
        'teachers_assigned' => 'boolean',
        'educational_plan_ready' => 'boolean',
        'circle_created' => 'boolean',
        'marketing_completed_at' => 'datetime',
        'administrative_completed_at' => 'datetime',
        'educational_completed_at' => 'datetime',
    ];

    /**
     * علاقة مع طلب فتح الحلقة.
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(CircleOpeningRequest::class, 'request_id');
    }

    /**
     * علاقة مع مسؤول قسم التسويق.
     */
    public function marketingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marketing_user_id');
    }

    /**
     * علاقة مع مسؤول القسم الإداري.
     */
    public function administrativeUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'administrative_user_id');
    }

    /**
     * علاقة مع مسؤول القسم التعليمي.
     */
    public function educationalUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'educational_user_id');
    }

    /**
     * علاقة مع الحلقة القرآنية المنشأة.
     */
    public function createdCircle(): BelongsTo
    {
        return $this->belongsTo(QuranCircle::class, 'created_circle_id');
    }

    /**
     * تحديث حالة قسم التسويق وتسجيل النشاط.
     */
    public function updateMarketingStatus(string $status, ?string $notes = null): self
    {
        $oldStatus = $this->marketing_status;
        $oldNotes = $this->marketing_notes;

        $this->marketing_status = $status;
        if ($notes) {
            $this->marketing_notes = $notes;
        }

        if ($status === 'مكتمل') {
            $this->marketing_completed_at = now();
        } elseif ($status === 'مرفوض') {
            $this->request->updateStatus('مرفوض', $notes ?? 'مرفوض من قسم التسويق', 'التسويق');
        }

        $this->marketing_user_id = auth()->id();
        $this->save();

        // تسجيل نشاط تحديث حالة قسم التسويق
        $this->request->activities()->create([
            'department' => 'التسويق',
            'activity_type' => 'تغيير حالة',
            'description' => "تم تغيير حالة قسم التسويق من '{$oldStatus}' إلى '{$status}'" . ($notes ? " - ملاحظات: {$notes}" : ''),
            'old_values' => json_encode(['marketing_status' => $oldStatus, 'marketing_notes' => $oldNotes]),
            'new_values' => json_encode(['marketing_status' => $status, 'marketing_notes' => $this->marketing_notes]),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
        ]);

        return $this;
    }

    /**
     * تحديث حالة القسم الإداري وتسجيل النشاط.
     */
    public function updateAdministrativeStatus(string $status, ?string $notes = null): self
    {
        $oldStatus = $this->administrative_status;
        $oldNotes = $this->administrative_notes;

        $this->administrative_status = $status;
        if ($notes) {
            $this->administrative_notes = $notes;
        }

        if ($status === 'مكتمل') {
            $this->administrative_completed_at = now();
        } elseif ($status === 'مرفوض') {
            $this->request->updateStatus('مرفوض', $notes ?? 'مرفوض من القسم الإداري', 'الإدارية');
        }

        $this->administrative_user_id = auth()->id();
        $this->save();

        // تسجيل نشاط تحديث حالة القسم الإداري
        $this->request->activities()->create([
            'department' => 'الإدارية',
            'activity_type' => 'تغيير حالة',
            'description' => "تم تغيير حالة القسم الإداري من '{$oldStatus}' إلى '{$status}'" . ($notes ? " - ملاحظات: {$notes}" : ''),
            'old_values' => json_encode(['administrative_status' => $oldStatus, 'administrative_notes' => $oldNotes]),
            'new_values' => json_encode(['administrative_status' => $status, 'administrative_notes' => $this->administrative_notes]),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
        ]);

        return $this;
    }

    /**
     * تحديث حالة القسم التعليمي وتسجيل النشاط.
     */
    public function updateEducationalStatus(string $status, ?string $notes = null): self
    {
        $oldStatus = $this->educational_status;
        $oldNotes = $this->educational_notes;

        $this->educational_status = $status;
        if ($notes) {
            $this->educational_notes = $notes;
        }

        if ($status === 'مكتمل') {
            $this->educational_completed_at = now();
        } elseif ($status === 'مرفوض') {
            $this->request->updateStatus('مرفوض', $notes ?? 'مرفوض من القسم التعليمي', 'التعليمية');
        }

        $this->educational_user_id = auth()->id();
        $this->save();

        // تسجيل نشاط تحديث حالة القسم التعليمي
        $this->request->activities()->create([
            'department' => 'التعليمية',
            'activity_type' => 'تغيير حالة',
            'description' => "تم تغيير حالة القسم التعليمي من '{$oldStatus}' إلى '{$status}'" . ($notes ? " - ملاحظات: {$notes}" : ''),
            'old_values' => json_encode(['educational_status' => $oldStatus, 'educational_notes' => $oldNotes]),
            'new_values' => json_encode(['educational_status' => $status, 'educational_notes' => $this->educational_notes]),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
        ]);

        return $this;
    }

    /**
     * إنشاء حلقة جديدة من الطلب وتسجيل النشاط.
     */
    public function createCircle(): ?QuranCircle
    {
        // التحقق من أن جميع الأقسام قد أكملت عملها
        if (
            $this->marketing_status !== 'مكتمل' ||
            $this->administrative_status !== 'مكتمل' ||
            $this->educational_status !== 'مكتمل'
        ) {
            return null;
        }

        // إنشاء الحلقة (المسجد)
        $mosque = Mosque::updateOrCreate(
            ['id' => $this->request->mosque_id],
            [
                'name' => $this->request->mosque_name,
                'neighborhood' => $this->request->neighborhood,
                'location_lat' => null, // يمكن استخراج هذه البيانات من رابط الخرائط لاحقاً
                'location_long' => null,
                'contact_number' => $this->request->requester_phone,
            ]
        );

        // إنشاء الحلقة القرآنية
        $circle = QuranCircle::create([
            'mosque_id' => $mosque->id,
            'circle_type' => 'لم تبدأ بعد',
            'circle_status' => 'لم تبدأ بعد',
            'time_period' => is_array($this->request->circle_time) && count($this->request->circle_time) === 1
                ? $this->request->circle_time[0]
                : 'كل الأوقات',
            'registration_link' => $this->request->store_link,
        ]);

        // تحديث معلومات الطلب والمرحلة
        $this->circle_created = true;
        $this->created_circle_id = $circle->id;
        $this->save();

        $this->request->updateStatus('موافق نهائياً', null, 'النظام');

        // تسجيل نشاط إنشاء الحلقة
        $this->request->activities()->create([
            'department' => 'النظام',
            'activity_type' => 'إنشاء حلقة',
            'description' => "تم إنشاء حلقة جديدة برقم {$circle->id} في مسجد {$mosque->name}",
            'old_values' => json_encode(['circle_created' => false, 'created_circle_id' => null]),
            'new_values' => json_encode(['circle_created' => true, 'created_circle_id' => $circle->id]),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
        ]);

        return $circle;
    }
}