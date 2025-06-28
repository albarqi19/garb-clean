<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * نموذج تتبع التسميع اليومي
 * يوفر طرق مبسطة لإدارة التتبع اليومي لكل طالب
 */
class DailyRecitationTracker extends Model
{
    use HasFactory;

    protected $table = 'student_progress';

    protected $fillable = [
        'student_id',
        'curriculum_id',
        'current_page',
        'current_verse',
        'current_surah_id',
        'progress_percentage',
        'today_recitation_content',
        'tomorrow_recitation_content',
        'today_date',
        'tomorrow_date',
        'today_status',
        'today_completion_percentage',
        'last_recitation_session_id',
        'daily_tracking_updated_at',
    ];

    protected $casts = [
        'today_recitation_content' => 'array',
        'tomorrow_recitation_content' => 'array',
        'today_date' => 'date',
        'tomorrow_date' => 'date',
        'today_completion_percentage' => 'decimal:2',
        'daily_tracking_updated_at' => 'datetime',
    ];

    /**
     * العلاقات
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function lastRecitationSession(): BelongsTo
    {
        return $this->belongsTo(RecitationSession::class, 'last_recitation_session_id');
    }

    /**
     * البحث عن طالب محدد
     */
    public static function forStudent(int $studentId): ?self
    {
        return static::where('student_id', $studentId)->first();
    }

    /**
     * الحصول على الطلاب الذين لديهم تسميع اليوم
     */
    public static function getStudentsWithTodayRecitation()
    {
        return static::where('today_date', Carbon::today())
            ->whereNotNull('today_recitation_content')
            ->with(['student', 'curriculum'])
            ->get();
    }

    /**
     * الحصول على الطلاب الذين لم يكملوا تسميع اليوم
     */
    public static function getStudentsWithPendingTodayRecitation()
    {
        return static::where('today_date', Carbon::today())
            ->where('today_status', '!=', 'completed')
            ->whereNotNull('today_recitation_content')
            ->with(['student', 'curriculum'])
            ->get();
    }

    /**
     * فحص إذا كان الطالب لديه تسميع اليوم
     */
    public function hasTodayRecitation(): bool
    {
        return $this->today_date && 
               $this->today_date->isToday() && 
               !empty($this->today_recitation_content);
    }

    /**
     * فحص إذا كان تسميع اليوم مكتمل
     */
    public function isTodayRecitationCompleted(): bool
    {
        return $this->today_status === 'completed';
    }

    /**
     * فحص إذا كان تسميع اليوم قيد التقدم
     */
    public function isTodayRecitationInProgress(): bool
    {
        return $this->today_status === 'in_progress';
    }

    /**
     * الحصول على محتوى تسميع اليوم بشكل منسق
     */
    public function getTodayRecitationFormatted(): string
    {
        if (empty($this->today_recitation_content)) {
            return 'لا يوجد تسميع اليوم';
        }

        $content = $this->today_recitation_content;
        
        if (isset($content['type']) && $content['type'] === 'pages') {
            return "من صفحة {$content['from_page']} إلى صفحة {$content['to_page']}";
        } elseif (isset($content['type']) && $content['type'] === 'verses') {
            return "من الآية {$content['from_verse']} إلى الآية {$content['to_verse']} - سورة {$content['surah_name']}";
        }

        return 'محتوى تسميع غير محدد';
    }

    /**
     * الحصول على محتوى تسميع الغد بشكل منسق
     */
    public function getTomorrowRecitationFormatted(): string
    {
        if (empty($this->tomorrow_recitation_content)) {
            return 'لم يتم تحديد تسميع الغد بعد';
        }

        $content = $this->tomorrow_recitation_content;
        
        if (isset($content['type']) && $content['type'] === 'pages') {
            return "من صفحة {$content['from_page']} إلى صفحة {$content['to_page']}";
        } elseif (isset($content['type']) && $content['type'] === 'verses') {
            return "من الآية {$content['from_verse']} إلى الآية {$content['to_verse']} - سورة {$content['surah_name']}";
        }

        return 'محتوى تسميع غير محدد';
    }

    /**
     * تحديث حالة تسميع اليوم
     */
    public function updateTodayStatus(string $status, ?float $completionPercentage = null): bool
    {
        $validStatuses = ['pending', 'in_progress', 'completed', 'missed'];
        
        if (!in_array($status, $validStatuses)) {
            return false;
        }

        $this->today_status = $status;
        
        if ($completionPercentage !== null) {
            $this->today_completion_percentage = $completionPercentage;
        }

        $this->daily_tracking_updated_at = Carbon::now();
        
        return $this->save();
    }

    /**
     * إعداد تسميع اليوم والغد
     */
    public function setupDailyRecitation(array $todayContent, array $tomorrowContent): bool
    {
        $this->today_recitation_content = $todayContent;
        $this->tomorrow_recitation_content = $tomorrowContent;
        $this->today_date = Carbon::today();
        $this->tomorrow_date = Carbon::tomorrow();
        $this->today_status = 'pending';
        $this->today_completion_percentage = 0;
        $this->daily_tracking_updated_at = Carbon::now();

        return $this->save();
    }

    /**
     * الانتقال إلى اليوم التالي (تحويل تسميع الغد إلى اليوم)
     */
    public function moveToNextDay(): bool
    {
        if (empty($this->tomorrow_recitation_content)) {
            return false;
        }

        $this->today_recitation_content = $this->tomorrow_recitation_content;
        $this->today_date = $this->tomorrow_date ?? Carbon::today();
        $this->today_status = 'pending';
        $this->today_completion_percentage = 0;
        
        // إعادة تعيين محتوى الغد
        $this->tomorrow_recitation_content = null;
        $this->tomorrow_date = null;
        
        $this->daily_tracking_updated_at = Carbon::now();

        return $this->save();
    }

    /**
     * فحص إذا كان التتبع اليومي محدث
     */
    public function isDailyTrackingUpdated(): bool
    {
        return $this->daily_tracking_updated_at && 
               $this->daily_tracking_updated_at->isToday();
    }

    /**
     * الحصول على آخر نشاط للتتبع اليومي
     */
    public function getLastTrackingActivity(): string
    {
        if (!$this->daily_tracking_updated_at) {
            return 'لم يتم التحديث بعد';
        }

        return $this->daily_tracking_updated_at->diffForHumans();
    }

    /**
     * إحصائيات سريعة للطالب
     */
    public function getQuickStats(): array
    {
        return [
            'has_today_recitation' => $this->hasTodayRecitation(),
            'today_status' => $this->today_status ?? 'not_set',
            'today_completion' => $this->today_completion_percentage ?? 0,
            'today_content' => $this->getTodayRecitationFormatted(),
            'tomorrow_content' => $this->getTomorrowRecitationFormatted(),
            'last_update' => $this->getLastTrackingActivity(),
            'overall_progress' => $this->progress_percentage ?? 0,
        ];
    }
}
