<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CircleNeedsRequest extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array
     */
    protected $fillable = [
        'quran_circle_id', 'school_name', 'time_period', 'neighborhood',
        'teachers_needed', 'supervisors_needed', 'talqeen_teachers_needed',
        'memorization_teachers_needed', 'assistant_supervisors_needed',
        'current_students_count', 'current_teachers_count',
        'funding_status', 'school_status', 'action', 'notes',
        'requested_by', 'processed_by', 'approval_date', 'completion_date',
    ];

    /**
     * الخصائص التي يجب تحويلها.
     *
     * @var array
     */
    protected $casts = [
        'approval_date' => 'date',
        'completion_date' => 'date',
    ];

    /**
     * إعداد النموذج.
     */
    protected static function boot()
    {
        parent::boot();

        // تسجيل نشاط عند إنشاء طلب جديد
        static::created(function ($request) {
            $request->activities()->create([
                'activity_type' => 'إنشاء',
                'description' => 'تم إنشاء طلب احتياج جديد للحلقة',
                'user_id' => auth()->id(),
                'new_values' => json_encode($request->toArray()),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });
    }

    /**
     * العلاقة مع الحلقة القرآنية.
     */
    public function quranCircle(): BelongsTo
    {
        return $this->belongsTo(QuranCircle::class);
    }

    /**
     * العلاقة مع المستخدم الذي قام بتقديم الطلب.
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * العلاقة مع المستخدم الذي قام بمعالجة الطلب.
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * العلاقة مع أنشطة الطلب.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(CircleNeedsRequestActivity::class, 'request_id');
    }

    /**
     * حساب إجمالي الاحتياجات المطلوبة.
     */
    public function getTotalNeedsAttribute(): int
    {
        return $this->teachers_needed + $this->supervisors_needed + 
               $this->talqeen_teachers_needed + $this->memorization_teachers_needed + 
               $this->assistant_supervisors_needed;
    }

    /**
     * تحديث حالة الطلب وتسجيل نشاط.
     */
    public function updateAction(string $action, ?string $notes = null): self
    {
        $oldAction = $this->action;
        $oldNotes = $this->notes;
        
        $this->action = $action;
        if ($notes) {
            $this->notes = $notes;
        }
        
        // تحديث تواريخ الموافقة والإكمال حسب الحالة
        if ($action === 'تم التنسيق') {
            $this->approval_date = now();
        } elseif ($action === 'مكتمل') {
            $this->completion_date = now();
        }
        
        $this->processed_by = auth()->id();
        $this->save();
        
        // تسجيل نشاط تغيير الحالة
        $this->activities()->create([
            'activity_type' => 'تغيير حالة',
            'description' => "تم تغيير حالة الطلب من '{$oldAction}' إلى '{$action}'" . ($notes ? " - ملاحظات: {$notes}" : ''),
            'old_values' => json_encode(['action' => $oldAction, 'notes' => $oldNotes]),
            'new_values' => json_encode(['action' => $action, 'notes' => $this->notes]),
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
        
        return $this;
    }

    /**
     * تحديث حالة الكفالة وتسجيل نشاط.
     */
    public function updateFundingStatus(string $status): self
    {
        $oldStatus = $this->funding_status;
        $this->funding_status = $status;
        $this->save();
        
        // تسجيل نشاط تغيير حالة الكفالة
        $this->activities()->create([
            'activity_type' => 'تحديث',
            'description' => "تم تحديث حالة الكفالة من '{$oldStatus}' إلى '{$status}'",
            'old_values' => json_encode(['funding_status' => $oldStatus]),
            'new_values' => json_encode(['funding_status' => $status]),
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
        
        return $this;
    }

    /**
     * الخصائص المُحسوبة.
     */
    protected $appends = [
        'total_needs',
    ];
}