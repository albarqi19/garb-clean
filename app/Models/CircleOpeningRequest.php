<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CircleOpeningRequest extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array
     */
    protected $fillable = [
        'requester_name', 'requester_phone', 'requester_relation_to_circle',
        'neighborhood', 'mosque_id', 'mosque_name', 'mosque_location_url', 'nearest_circle',
        'number_of_circles_requested', 'had_previous_circles', 'expected_students_number',
        'is_mosque_owner_welcoming', 'circle_time',
        'notes', 'terms_accepted',
        'store_link', 'support_status', 'teacher_availability', 'launch_date', 'is_launched',
        'request_status', 'rejection_reason', 'days_since_submission',
    ];

    /**
     * الخصائص التي يجب تحويلها.
     *
     * @var array
     */
    protected $casts = [
        'had_previous_circles' => 'boolean',
        'is_mosque_owner_welcoming' => 'boolean',
        'terms_accepted' => 'boolean',
        'is_launched' => 'boolean',
        'circle_time' => 'array',
        'launch_date' => 'date',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // إنشاء مرحلة الطلب تلقائياً عند إنشاء طلب جديد
        static::created(function ($request) {
            $request->stage()->create([]);
            
            // تسجيل نشاط إنشاء الطلب
            $request->activities()->create([
                'department' => 'النظام',
                'activity_type' => 'إنشاء',
                'description' => 'تم إنشاء طلب جديد لفتح حلقة',
                'old_values' => null,
                'new_values' => json_encode($request->toArray()),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'user_id' => auth()->id(),
            ]);
        });

        // تحديث عدد الأيام منذ التقديم
        static::updating(function ($request) {
            $request->days_since_submission = now()->diffInDays($request->created_at);
        });
    }

    /**
     * علاقة مع المسجد.
     */
    public function mosque(): BelongsTo
    {
        return $this->belongsTo(Mosque::class);
    }

    /**
     * علاقة مع مراحل الطلب.
     */
    public function stage(): HasOne
    {
        return $this->hasOne(CircleRequestStage::class, 'request_id');
    }

    /**
     * علاقة مع سجل الأنشطة.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(CircleRequestActivity::class, 'request_id');
    }

    /**
     * علاقة مع الحلقة المنشأة (إذا تم إنشاؤها).
     */
    public function createdCircle(): HasOne
    {
        return $this->hasOne(QuranCircle::class, 'id', 'created_circle_id')
            ->through('stage');
    }

    /**
     * تحديث حالة الطلب وتسجيل نشاط.
     */
    public function updateStatus(string $status, ?string $reason = null, ?string $department = 'النظام'): self
    {
        $oldStatus = $this->request_status;
        $oldReason = $this->rejection_reason;
        
        $this->request_status = $status;
        if ($reason) {
            $this->rejection_reason = $reason;
        }
        
        $this->save();
        
        // تسجيل نشاط تغيير الحالة
        $this->activities()->create([
            'department' => $department,
            'activity_type' => 'تغيير حالة',
            'description' => "تم تغيير حالة الطلب من '{$oldStatus}' إلى '{$status}'" . ($reason ? " بسبب: {$reason}" : ''),
            'old_values' => json_encode(['status' => $oldStatus, 'rejection_reason' => $oldReason]),
            'new_values' => json_encode(['status' => $status, 'rejection_reason' => $this->rejection_reason]),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
        ]);
        
        return $this;
    }

    /**
     * تحديث حالة اكتمال الدعم وتسجيل نشاط.
     */
    public function updateSupportStatus(string $status): self
    {
        $oldStatus = $this->support_status;
        $this->support_status = $status;
        $this->save();
        
        // تسجيل نشاط تغيير حالة الدعم
        $this->activities()->create([
            'department' => 'التسويق',
            'activity_type' => 'تحديث',
            'description' => "تم تحديث حالة اكتمال الدعم من '{$oldStatus}' إلى '{$status}'",
            'old_values' => json_encode(['support_status' => $oldStatus]),
            'new_values' => json_encode(['support_status' => $status]),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
        ]);
        
        return $this;
    }

    /**
     * تحديث حالة توفر المعلمين وتسجيل نشاط.
     */
    public function updateTeacherAvailability(string $status): self
    {
        $oldStatus = $this->teacher_availability;
        $this->teacher_availability = $status;
        $this->save();
        
        // تسجيل نشاط تغيير حالة توفر المعلمين
        $this->activities()->create([
            'department' => 'التعليمية',
            'activity_type' => 'تحديث',
            'description' => "تم تحديث حالة توفر المعلمين من '{$oldStatus}' إلى '{$status}'",
            'old_values' => json_encode(['teacher_availability' => $oldStatus]),
            'new_values' => json_encode(['teacher_availability' => $status]),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
        ]);
        
        return $this;
    }

    /**
     * تحديث رابط المتجر وتسجيل نشاط.
     */
    public function updateStoreLink(string $link): self
    {
        $oldLink = $this->store_link;
        $this->store_link = $link;
        $this->save();
        
        // تسجيل نشاط إضافة رابط المتجر
        $this->activities()->create([
            'department' => 'التسويق',
            'activity_type' => 'إنشاء رابط المتجر',
            'description' => "تم إضافة/تحديث رابط المتجر",
            'old_values' => json_encode(['store_link' => $oldLink]),
            'new_values' => json_encode(['store_link' => $link]),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
        ]);
        
        return $this;
    }

    /**
     * تسجيل انطلاق الحلقة وتسجيل نشاط.
     */
    public function markAsLaunched(string $launchDate): self
    {
        $this->is_launched = true;
        $this->launch_date = $launchDate;
        $this->request_status = 'مكتمل';
        $this->save();
        
        // تسجيل نشاط انطلاق الحلقة
        $this->activities()->create([
            'department' => 'التعليمية',
            'activity_type' => 'تأكيد',
            'description' => "تم تأكيد انطلاق الحلقة بتاريخ {$launchDate}",
            'old_values' => json_encode(['is_launched' => false, 'launch_date' => null]),
            'new_values' => json_encode(['is_launched' => true, 'launch_date' => $launchDate]),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
        ]);
        
        return $this;
    }
}