<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Services\QuranService;
use App\Models\Curriculum;

class RecitationSession extends Model
{
    protected $fillable = [
        'session_id',
        'student_id',
        'teacher_id',
        'quran_circle_id',
        'curriculum_id',
        'start_surah_number',
        'start_verse',
        'end_surah_number',
        'end_verse',
        'recitation_type',
        'duration_minutes',
        'grade',
        'evaluation',
        'teacher_notes',
        'has_errors',
        'total_verses',
        'status',
        'completed_at'
    ];

    protected $casts = [
        'grade' => 'decimal:2',
        'has_errors' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * العلاقات
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function circle(): BelongsTo
    {
        return $this->belongsTo(QuranCircle::class, 'quran_circle_id');
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function errors(): HasMany
    {
        return $this->hasMany(RecitationError::class);
    }

    /**
     * الوظائف المساعدة
     */
    
    /**
     * حساب التقدير التلقائي بناءً على الدرجة
     */
    public function calculateEvaluation(): string
    {
        $grade = (float) $this->grade;
        
        if ($grade >= 9.0) return 'ممتاز';
        if ($grade >= 8.0) return 'جيد جداً';
        if ($grade >= 7.0) return 'جيد';
        if ($grade >= 6.0) return 'مقبول';
        
        return 'ضعيف';
    }

    /**
     * حساب إجمالي عدد الآيات المسمعة
     */
    public function calculateTotalVerses(): int
    {
        return QuranService::calculateMultiSurahVerseCount(
            $this->start_surah_number,
            $this->start_verse,
            $this->end_surah_number,
            $this->end_verse
        );
    }

    /**
     * الحصول على نص النطاق القرآني
     */
    public function getQuranRangeAttribute(): string
    {
        return QuranService::formatMultiSurahContent(
            $this->start_surah_number,
            $this->start_verse,
            $this->end_surah_number,
            $this->end_verse
        );
    }

    /**
     * الحصول على ملخص النطاق
     */
    public function getRangeSummaryAttribute(): array
    {
        return QuranService::getMultiSurahRangeSummary(
            $this->start_surah_number,
            $this->start_verse,
            $this->end_surah_number,
            $this->end_verse
        );
    }

    /**
     * تحديث حالة الأخطاء
     */
    public function updateErrorsStatus(): void
    {
        $this->update([
            'has_errors' => $this->errors()->count() > 0
        ]);
    }

    /**
     * أحداث النموذج
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($session) {
            // حساب التقدير التلقائي
            $session->evaluation = $session->calculateEvaluation();
            
            // حساب إجمالي الآيات
            if (!$session->total_verses) {
                $session->total_verses = $session->calculateTotalVerses();
            }

            if (empty($session->session_id)) {
                $session->session_id = self::generateSessionId($session->student_id);
            }
        });

        static::updating(function ($session) {
            // إعادة حساب التقدير إذا تغيرت الدرجة
            if ($session->isDirty('grade')) {
                $session->evaluation = $session->calculateEvaluation();
            }
        });
    }

    /**
     * توليد معرف جلسة فريد
     */
    public static function generateSessionId(int $studentId): string
    {
        $date = now()->format('Ymd');
        $time = now()->format('His');
        $studentPadded = str_pad($studentId, 4, '0', STR_PAD_LEFT);
        
        // نمط: RS-YYYYMMDD-HHMMSS-SSSS (RS = Recitation Session)
        $baseId = "RS-{$date}-{$time}-{$studentPadded}";
        
        // التأكد من عدم التكرار
        $counter = 1;
        $sessionId = $baseId;
        
        while (self::where('session_id', $sessionId)->exists()) {
            $sessionId = $baseId . '-' . str_pad($counter, 2, '0', STR_PAD_LEFT);
            $counter++;
        }
        
        return $sessionId;
    }
}
