<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class ActivityLog extends Model
{
    use HasFactory;

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'user_name',
        'activity_type',
        'module',
        'subject_id',
        'subject_type',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    /**
     * الخصائص التي يجب تحويلها.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * المستخدم الذي قام بالنشاط
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * تسجيل نشاط جديد
     *
     * @param string $activityType نوع النشاط (إضافة، تعديل، حذف، إلخ)
     * @param string $module القسم أو الوحدة في النظام
     * @param string $description وصف النشاط
     * @param Model|null $subject العنصر المتأثر بالنشاط
     * @param array|null $oldValues القيم القديمة
     * @param array|null $newValues القيم الجديدة
     * @return ActivityLog
     */
    public static function logActivity(
        string $activityType,
        string $module,
        string $description,
        ?Model $subject = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): self {
        $user = Auth::user();
        $request = request();

        $data = [
            'user_id' => $user ? $user->id : null,
            'user_name' => $user ? $user->name : 'النظام',
            'activity_type' => $activityType,
            'module' => $module,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request ? $request->ip() : null,
            'user_agent' => $request ? $request->userAgent() : null,
        ];

        if ($subject) {
            $data['subject_id'] = $subject->id;
            $data['subject_type'] = get_class($subject);
        }

        return self::create($data);
    }

    /**
     * تسجيل نشاط إضافة
     *
     * @param string $module القسم
     * @param string $description الوصف
     * @param Model $subject العنصر المضاف
     * @param array|null $values القيم المضافة
     * @return ActivityLog
     */
    public static function logCreated(string $module, string $description, Model $subject, ?array $values = null): self
    {
        return self::logActivity('إضافة', $module, $description, $subject, null, $values ?? $subject->toArray());
    }

    /**
     * تسجيل نشاط تعديل
     *
     * @param string $module القسم
     * @param string $description الوصف
     * @param Model $subject العنصر المعدل
     * @param array $oldValues القيم القديمة
     * @param array $newValues القيم الجديدة
     * @return ActivityLog
     */
    public static function logUpdated(string $module, string $description, Model $subject, array $oldValues, array $newValues): self
    {
        return self::logActivity('تعديل', $module, $description, $subject, $oldValues, $newValues);
    }

    /**
     * تسجيل نشاط حذف
     *
     * @param string $module القسم
     * @param string $description الوصف
     * @param Model $subject العنصر المحذوف
     * @param array|null $values قيم العنصر قبل الحذف
     * @return ActivityLog
     */
    public static function logDeleted(string $module, string $description, Model $subject, ?array $values = null): self
    {
        return self::logActivity('حذف', $module, $description, $subject, $values ?? $subject->toArray(), null);
    }

    /**
     * تسجيل نشاط تسجيل دخول
     *
     * @param User $user المستخدم
     * @return ActivityLog
     */
    public static function logLogin(User $user): self
    {
        return self::logActivity(
            'تسجيل دخول',
            'المستخدمين',
            "تم تسجيل دخول المستخدم {$user->name}",
            $user
        );
    }

    /**
     * تسجيل نشاط تسجيل خروج
     *
     * @param User $user المستخدم
     * @return ActivityLog
     */
    public static function logLogout(User $user): self
    {
        return self::logActivity(
            'تسجيل خروج',
            'المستخدمين',
            "تم تسجيل خروج المستخدم {$user->name}",
            $user
        );
    }

    /**
     * الحصول على وصف النشاط مع التاريخ والوقت
     *
     * @return string
     */
    public function getFormattedDescriptionAttribute(): string
    {
        $date = $this->created_at->format('Y-m-d');
        $time = $this->created_at->format('H:i:s');
        
        if ($this->user_name) {
            return "{$this->description} بواسطة {$this->user_name} في {$date} الساعة {$time}";
        }
        
        return "{$this->description} في {$date} الساعة {$time}";
    }
}
