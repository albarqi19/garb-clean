<?php

namespace App\Models;

use App\Models\Traits\HasActivityLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Teacher extends Model
{
    use HasFactory, HasActivityLog;

    // اسم العرض للنموذج في سجل الأنشطة
    public static $displayName = 'معلم';
    
    // اسم الوحدة للنموذج في سجل الأنشطة
    public static $moduleName = 'المعلمين';

    /**
     * الحقول المستبعدة من تسجيل الأنشطة
     */
    protected $activityExcluded = [
        'updated_at', 
        'created_at', 
        'remember_token',
    ];

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'identity_number',
        'name',
        'nationality',
        'mosque_id',
        'system_number',
        'phone',
        'password',
        'plain_password',
        'password_changed_at',
        'must_change_password',
        'last_login_at',
        'is_active_user',
        'job_title',
        'task_type',
        'iban',
        'circle_type',
        'work_time',
        'absence_count',
        'ratel_activated',
        'start_date',
        'evaluation',
        'quran_circle_id',
    ];

    /**
     * الخصائص التي يجب تحويلها.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'ratel_activated' => 'boolean',
        'start_date' => 'date',
        'must_change_password' => 'boolean',
        'is_active_user' => 'boolean',
        'password_changed_at' => 'datetime',
        'last_login_at' => 'datetime',
    ];

    /**
     * الحقول المخفية
     */
    protected $hidden = [
        'password',
    ];

    /**
     * توليد رقم سري عشوائي للمعلم
     */
    public static function generateRandomPassword(): string
    {
        return str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * تعيين كلمة مرور مشفرة للمعلم
     */
    public function setPasswordAttribute($value): void
    {
        if ($value) {
            // حفظ كلمة المرور الأصلية
            $this->attributes['plain_password'] = $value;
            // حفظ كلمة المرور المشفرة
            $this->attributes['password'] = \Illuminate\Support\Facades\Hash::make($value);
        }
    }

    /**
     * التحقق من كلمة المرور
     */
    public function checkPassword(string $password): bool
    {
        return \Illuminate\Support\Facades\Hash::check($password, $this->password);
    }

    /**
     * تسجيل آخر دخول للمعلم
     */
    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    /**
     * تغيير كلمة المرور
     */
    public function changePassword(string $newPassword): void
    {
        $this->update([
            'password' => $newPassword,
            'password_changed_at' => now(),
            'must_change_password' => false,
        ]);
    }

    /**
     * التحقق من صحة رقم الهوية وكلمة المرور للمصادقة
     */
    public static function authenticate(string $identityNumber, string $password): ?self
    {
        $teacher = self::where('identity_number', $identityNumber)
                      ->where('is_active_user', true)
                      ->first();

        if ($teacher && $teacher->checkPassword($password)) {
            $teacher->updateLastLogin();
            return $teacher;
        }

        return null;
    }

    /**
     * الحصول على المسجد الذي يعمل فيه المعلم
     */
    public function mosque(): BelongsTo
    {
        return $this->belongsTo(Mosque::class);
    }

    /**
     * الحصول على الحلقة التي يدرس فيها المعلم
     */
    public function quranCircle(): BelongsTo
    {
        return $this->belongsTo(QuranCircle::class);
    }

    /**
     * تكليفات المعلم في الحلقات المختلفة (النظام الجديد)
     */
    public function circleAssignments(): HasMany
    {
        return $this->hasMany(TeacherCircleAssignment::class);
    }

    /**
     * الحلقات النشطة التي يعمل فيها المعلم (النظام الجديد)
     */
    public function activeCircles()
    {
        return $this->belongsToMany(QuranCircle::class, 'teacher_circle_assignments')
                    ->wherePivot('is_active', true)
                    ->withPivot(['start_date', 'end_date', 'notes'])
                    ->withTimestamps();
    }

    /**
     * جميع الحلقات التي عمل فيها المعلم (بما في ذلك المنتهية)
     */
    public function allCircles()
    {
        return $this->belongsToMany(QuranCircle::class, 'teacher_circle_assignments')
                    ->withPivot(['is_active', 'start_date', 'end_date', 'notes'])
                    ->withTimestamps();
    }

    /**
     * جداول عمل المعلم في المساجد المختلفة
     */
    public function mosqueSchedules(): HasMany
    {
        return $this->hasMany(TeacherMosqueSchedule::class);
    }

    /**
     * جداول العمل النشطة للمعلم
     */
    public function activeMosqueSchedules(): HasMany
    {
        return $this->mosqueSchedules()->where('is_active', true);
    }

    /**
     * الحصول على جداول عمل المعلم ليوم معين
     */
    public function getScheduleForDay(string $day)
    {
        return $this->activeMosqueSchedules()->where('day_of_week', $day)->get();
    }

    /**
     * الحصول على جميع المساجد التي يعمل فيها المعلم
     */
    public function getMosquesWorkedIn()
    {
        return $this->activeMosqueSchedules()
                    ->with('mosque')
                    ->get()
                    ->pluck('mosque')
                    ->unique('id');
    }

    /**
     * التحقق ما إذا كان المعلم لديه راتب (بمكافأة)
     */
    public function getHasSalaryAttribute(): bool
    {
        return $this->task_type === 'معلم بمكافأة' || $this->task_type === 'مشرف';
    }

    /**
     * التحقق ما إذا كان المعلم محتسب (متطوع)
     */
    public function getIsVolunteerAttribute(): bool
    {
        return $this->task_type === 'معلم محتسب';
    }

    /**
     * التحقق ما إذا كان مشرفًا
     */
    public function getIsSupervisorAttribute(): bool
    {
        return $this->task_type === 'مشرف' || $this->task_type === 'مساعد مشرف';
    }

    /**
     * الحصول على اسم الحي من خلال المسجد
     */
    public function getNeighborhoodAttribute(): ?string
    {
        return $this->mosque ? $this->mosque->neighborhood : null;
    }

    /**
     * الحوافز التي تلقاها هذا المعلم
     */
    public function incentives(): HasMany
    {
        return $this->hasMany(TeacherIncentive::class);
    }

    /**
     * سجلات الحضور الخاصة بهذا المعلم
     */
    public function attendances(): MorphMany
    {
        return $this->morphMany(Attendance::class, 'attendable');
    }

    /**
     * رواتب المعلم
     */
    public function salaries(): MorphMany
    {
        return $this->morphMany(Salary::class, 'payee');
    }

    /**
     * المهام المرتبطة بالمعلم
     */
    public function tasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'taskable');
    }

    /**
     * المهام قيد التنفيذ للمعلم
     */
    public function activeTasks()
    {
        return $this->tasks()->whereNotIn('status', ['مكتملة', 'ملغاة']);
    }

    /**
     * المهام المتأخرة للمعلم
     */
    public function overdueTasks()
    {
        return $this->tasks()->where('status', 'متأخرة')
                 ->orWhere(function($query) {
                     $query->where('due_date', '<', now())
                           ->whereNotIn('status', ['مكتملة', 'ملغاة']);
                 });
    }
    
    /**
     * الحلقات الفرعية التي يشرف عليها المعلم
     */
    public function circleGroups(): HasMany
    {
        return $this->hasMany(CircleGroup::class);
    }

    /**
     * الحصول على إجمالي الحوافز التي تلقاها المعلم في شهر محدد
     *
     * @param string $month الشهر (مثل "يناير 2025")
     * @return float إجمالي الحوافز
     */
    public function getTotalIncentivesForMonth($month): float
    {
        return $this->incentives()
            ->whereHas('circleIncentive', function ($query) use ($month) {
                $query->where('month', $month);
            })
            ->sum('amount');
    }

    /**
     * الحصول على عدد أيام الحضور في فترة محددة
     *
     * @param string $period الفترة ('الفجر', 'العصر', 'المغرب', 'العشاء')
     * @param \DateTime $startDate تاريخ البداية
     * @param \DateTime $endDate تاريخ النهاية
     * @return int عدد أيام الحضور
     */
    public function getAttendanceDaysCount($period, $startDate, $endDate): int
    {
        return Attendance::countEligibleDays(
            Teacher::class,
            $this->id,
            $period,
            $startDate,
            $endDate
        );
    }

    /**
     * الحصول على وصف النشاط لكل حدث
     */
    public function getActivityDescriptionForEvent(string $event): string
    {
        return match($event) {
            'created' => "تم إضافة معلم جديد: {$this->name}",
            'updated' => "تم تعديل بيانات المعلم: {$this->name}",
            'deleted' => "تم حذف المعلم: {$this->name}",
            default => parent::getActivityDescriptionForEvent($event),
        };
    }

    /**
     * إرسال كلمة المرور عبر واتساب
     *
     * @param string|null $customPassword كلمة مرور مخصصة (اختيارية)
     * @return bool حالة الإرسال
     */
    public function sendPasswordViaWhatsApp(?string $customPassword = null): bool
    {
        // استخدام كلمة المرور المخصصة أو الأصلية
        $password = $customPassword ?? $this->plain_password;
        
        if (!$password) {
            return false;
        }

        return \App\Helpers\WhatsAppHelper::sendCustomPasswordMessage($this, $password);
    }

    /**
     * إرسال رسالة ترحيب مع كلمة المرور عبر واتساب
     *
     * @return bool حالة الإرسال
     */
    public function sendWelcomeWithPassword(): bool
    {
        return \App\Helpers\WhatsAppHelper::sendTeacherWelcomeWithPassword($this);
    }

    /**
     * إعادة إرسال كلمة المرور الحالية
     *
     * @return bool حالة الإرسال
     */
    public function resendPassword(): bool
    {
        if (!$this->plain_password) {
            return false;
        }

        return $this->sendPasswordViaWhatsApp($this->plain_password);
    }

    /**
     * تقييمات المعلم
     */
    public function evaluations(): HasMany
    {
        return $this->hasMany(TeacherEvaluation::class);
    }

    /**
     * آخر تقييم للمعلم
     */
    public function latestEvaluation()
    {
        return $this->evaluations()
                    ->orderBy('evaluation_date', 'desc')
                    ->first();
    }

    /**
     * متوسط تقييمات المعلم
     */
    public function getAverageEvaluationAttribute(): ?float
    {
        return $this->evaluations()
                    ->where('status', 'معتمد')
                    ->avg('total_score');
    }

    /**
     * عدد التقييمات المكتملة
     */
    public function getCompletedEvaluationsCountAttribute(): int
    {
        return $this->evaluations()
                    ->whereIn('status', ['مكتمل', 'معتمد'])
                    ->count();
    }
}
