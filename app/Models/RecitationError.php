<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Services\QuranService;

class RecitationError extends Model
{
    protected $fillable = [
        'recitation_session_id',
        'session_id',
        'surah_number',
        'verse_number',
        'word_text',
        'error_type',
        'correction_note',
        'teacher_note',
        'is_repeated',
        'severity_level'
    ];

    protected $casts = [
        'is_repeated' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * العلاقات
     */
    public function recitationSession(): BelongsTo
    {
        return $this->belongsTo(RecitationSession::class);
    }

    /**
     * علاقة مع جلسة التسميع عبر session_id
     */
    public function sessionBySessionId(): BelongsTo
    {
        return $this->belongsTo(RecitationSession::class, 'session_id', 'session_id');
    }

    /**
     * الوظائف المساعدة
     */
    
    /**
     * الحصول على اسم السورة
     */
    public function getSurahNameAttribute(): string
    {
        return QuranService::getSurahName($this->surah_number);
    }

    /**
     * الحصول على موقع الخطأ بشكل نصي
     */
    public function getLocationTextAttribute(): string
    {
        return sprintf(
            'سورة %s - آية %d',
            $this->surah_name,
            $this->verse_number
        );
    }

    /**
     * الحصول على وصف مفصل للخطأ
     */
    public function getErrorDescriptionAttribute(): string
    {
        $description = sprintf(
            'خطأ %s في كلمة "%s" - %s',
            $this->error_type,
            $this->word_text,
            $this->location_text
        );

        if ($this->is_repeated) {
            $description .= ' (خطأ متكرر)';
        }

        return $description;
    }

    /**
     * تحديث حالة الأخطاء في جلسة التسميع
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($error) {
            $error->recitationSession->updateErrorsStatus();
        });

        static::deleted(function ($error) {
            $error->recitationSession->updateErrorsStatus();
        });
    }
}
