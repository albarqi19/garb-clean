<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'identity_number',
        'name',
        'nationality',
        'birth_date',
        'phone',
        'password',
        'plain_password',
        'password_changed_at',
        'must_change_password',
        'last_login_at',
        'is_active_user',
        'quran_circle_id',
        'circle_group_id',
        'mosque_id',
        'neighborhood',
        'enrollment_date',
        'absence_count',
        'parts_count',
        'last_exam',
        'memorization_plan',
        'review_plan',
        'teacher_notes',
        'supervisor_notes',
        'center_notes',
        'guardian_name',
        'guardian_phone',
        'education_level',
        'is_active',
    ];

    /**
     * الخصائص التي يجب تحويلها.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'birth_date' => 'date',
        'enrollment_date' => 'date',
        'is_active' => 'boolean',
        'must_change_password' => 'boolean',
        'is_active_user' => 'boolean',
        'password_changed_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * الحقول المخفية
     */
    protected $hidden = [
        'password',
    ];

    /**
     * توليد رقم سري عشوائي للطالب
     */
    public static function generateRandomPassword(): string
    {
        return str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * تعيين كلمة مرور مشفرة للطالب
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
     * تسجيل آخر دخول للطالب
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
        $student = self::where('identity_number', $identityNumber)
                      ->where('is_active_user', true)
                      ->where('is_active', true)
                      ->first();

        if ($student && $student->checkPassword($password)) {
            $student->updateLastLogin();
            return $student;
        }

        return null;
    }

    /**
     * الحلقة القرآنية التي ينتمي إليها الطالب
     */
    public function quranCircle(): BelongsTo
    {
        return $this->belongsTo(QuranCircle::class);
    }

    /**
     * المسجد الذي ينتمي إليه الطالب
     */
    public function mosque(): BelongsTo
    {
        return $this->belongsTo(Mosque::class);
    }

    /**
     * الحلقة الفرعية التي ينتمي إليها الطالب (إذا كان في حلقة جماعية)
     */
    public function circleGroup(): BelongsTo
    {
        return $this->belongsTo(CircleGroup::class);
    }

    /**
     * حساب عمر الطالب
     */
    public function getAgeAttribute(): ?int
    {
        if (!$this->birth_date) return null;
        return $this->birth_date->age;
    }

    /**
     * حساب المدة التي قضاها الطالب في المركز
     */
    public function getEnrollmentPeriodAttribute(): ?string
    {
        if (!$this->enrollment_date) return null;
        
        $years = $this->enrollment_date->diffInYears(now());
        $months = $this->enrollment_date->diffInMonths(now()) % 12;
        
        if ($years > 0) {
            return $years . ' سنة و ' . $months . ' شهر';
        }
        
        return $months . ' شهر';
    }

    /**
     * الحصول على اسم المعلم المسؤول عن الطالب
     */
    public function getTeacherNameAttribute(): ?string
    {
        $circle = $this->quranCircle;
        
        if (!$circle) return null;
        
        if ($circle->is_individual) {
            return $circle->teacher ? $circle->teacher->name : null;
        }
        
        // إذا كان الطالب في حلقة فرعية، نعرض اسم معلم الحلقة الفرعية
        if ($this->circleGroup && $this->circleGroup->teacher) {
            return $this->circleGroup->teacher->name;
        }
        
        return null;
    }
    
    /**
     * العلاقة: الطالب له العديد من المناهج
     */
    public function curricula(): HasMany
    {
        return $this->hasMany(StudentCurriculum::class);
    }
    
    /**
     * سجلات الحضور الخاصة بهذا الطالب
     */
    public function attendances(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Attendance::class, 'attendable');
    }
    
    /**
     * المناهج النشطة للطالب (قيد التنفيذ)
     */
    public function activeCurricula()
    {
        return $this->curricula()->where('status', 'قيد التنفيذ');
    }
    
    /**
     * المناهج المكتملة للطالب
     */
    public function completedCurricula()
    {
        return $this->curricula()->where('status', 'مكتمل');
    }
    
    /**
     * الحصول على منهج التلقين الحالي للطالب
     */
    public function getCurrentRecitationCurriculumAttribute()
    {
        return $this->curricula()
            ->whereHas('curriculum', function ($query) {
                $query->where('type', 'منهج تلقين');
            })
            ->where('status', 'قيد التنفيذ')
            ->first();
    }
    
    /**
     * الحصول على منهج الطالب الحالي
     */
    public function getCurrentStudentCurriculumAttribute()
    {
        return $this->curricula()
            ->whereHas('curriculum', function ($query) {
                $query->where('type', 'منهج طالب');
            })
            ->where('status', 'قيد التنفيذ')
            ->first();
    }

    /**
     * علاقة تقدم الطالب
     */
    public function progressRecords(): HasMany
    {
        return $this->hasMany(StudentProgress::class);
    }

    /**
     * السجلات النشطة لتقدم الطالب
     */
    public function activeProgressRecords()
    {
        return $this->progressRecords()->whereIn('status', ['not_started', 'in_progress']);
    }

    /**
     * السجلات المكتملة لتقدم الطالب
     */
    public function completedProgressRecords()
    {
        return $this->progressRecords()->whereIn('status', ['completed', 'reviewed', 'mastered']);
    }

    /**
     * السجلات التي تحتاج مراجعة
     */
    public function progressRecordsNeedingReview()
    {
        return $this->progressRecords()->where('status', 'needs_revision');
    }

    /**
     * تقدم الطالب في منهج معين
     */
    public function progressInCurriculum(int $curriculumId)
    {
        return $this->progressRecords()->where('curriculum_id', $curriculumId);
    }

    /**
     * إحصائيات التقدم الإجمالية للطالب
     */
    public function getProgressStatsAttribute(): array
    {
        $total = $this->progressRecords()->count();
        $completed = $this->completedProgressRecords()->count();
        $inProgress = $this->activeProgressRecords()->count();
        $needsReview = $this->progressRecordsNeedingReview()->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'in_progress' => $inProgress,
            'needs_review' => $needsReview,
            'completion_percentage' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
        ];
    }

    /**
     * متوسط درجات الأداء للطالب
     */
    public function getAveragePerformanceScoreAttribute(): ?float
    {
        return $this->progressRecords()
            ->whereNotNull('performance_score')
            ->avg('performance_score');
    }

    /**
     * آخر نشاط تسميع للطالب
     */
    public function getLastRecitationActivityAttribute(): ?StudentProgress
    {
        return $this->progressRecords()
            ->whereNotNull('last_recitation_at')
            ->orderBy('last_recitation_at', 'desc')
            ->first();
    }

    /**
     * جلسات التسميع للطالب
     */
    public function recitationSessions(): HasMany
    {
        return $this->hasMany(RecitationSession::class);
    }

    /**
     * آخر جلسة تسميع للطالب
     */
    public function getLastRecitationSessionAttribute(): ?RecitationSession
    {
        return $this->recitationSessions()
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * جلسات التسميع للطالب في فترة معينة
     */
    public function recitationSessionsInPeriod($startDate, $endDate)
    {
        return $this->recitationSessions()
            ->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * متوسط درجات التسميع للطالب
     */
    public function getAverageRecitationGradeAttribute(): ?float
    {
        return $this->recitationSessions()
            ->whereNotNull('grade')
            ->avg('grade');
    }

    /**
     * عدد جلسات التسميع مع أخطاء
     */
    public function getRecitationSessionsWithErrorsCountAttribute(): int
    {
        return $this->recitationSessions()
            ->where('has_errors', true)
            ->count();
    }
}
