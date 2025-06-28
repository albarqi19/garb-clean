<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CircleCostSetting extends Model
{
    use HasFactory;

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'role_type',
        'nationality',
        'period',
        'monthly_cost',
        'academic_term_id',
        'valid_from',
        'valid_until',
        'is_active',
        'notes',
    ];

    /**
     * الخصائص التي يجب تحويلها.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'monthly_cost' => 'float',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * الفصل الدراسي المرتبط بهذه الإعدادات
     */
    public function academicTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    /**
     * الحصول على تكلفة المعلم/المشرف حسب النوع والجنسية والفترة
     * 
     * @param string $roleType نوع الدور (معلم، مشرف، مساعد مشرف، الخ)
     * @param string $nationality الجنسية (سعودي، غير سعودي)
     * @param string $period الفترة (فجر، عصر، مغرب، عشاء، جميع الفترات)
     * @param int|null $academicTermId معرّف الفصل الدراسي (اختياري)
     * @param \DateTime|null $date التاريخ المرجعي للبحث عن الإعدادات السارية (اختياري)
     * @return float التكلفة الشهرية
     */
    public static function getCost($roleType, $nationality, $period, $academicTermId = null, $date = null): float
    {
        $date = $date ?? now();
        
        $query = self::where('role_type', $roleType)
            ->where('nationality', $nationality)
            ->where('period', $period)
            ->where('is_active', true)
            ->where('valid_from', '<=', $date)
            ->where(function($q) use ($date) {
                $q->whereNull('valid_until')
                  ->orWhere('valid_until', '>=', $date);
            });
        
        if ($academicTermId) {
            $query->where(function($q) use ($academicTermId) {
                $q->where('academic_term_id', $academicTermId)
                  ->orWhereNull('academic_term_id');
            });
        }
        
        // ترتيب النتائج لأخذ الإعدادات المرتبطة بفصل دراسي محدد أولاً، ثم الإعدادات العامة
        $setting = $query->orderByRaw('academic_term_id IS NULL')
                         ->orderBy('valid_from', 'desc')
                         ->first();
        
        return $setting ? $setting->monthly_cost : 0;
    }
    
    /**
     * الحصول على جميع إعدادات التكلفة السارية حالياً
     * 
     * @param int|null $academicTermId معرّف الفصل الدراسي (اختياري)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getCurrentSettings($academicTermId = null)
    {
        $now = now();
        
        $query = self::where('is_active', true)
            ->where('valid_from', '<=', $now)
            ->where(function($q) use ($now) {
                $q->whereNull('valid_until')
                  ->orWhere('valid_until', '>=', $now);
            });
        
        if ($academicTermId) {
            $query->where(function($q) use ($academicTermId) {
                $q->where('academic_term_id', $academicTermId)
                  ->orWhereNull('academic_term_id');
            });
        }
        
        return $query->orderBy('role_type')
                     ->orderBy('nationality')
                     ->orderBy('period')
                     ->get();
    }
}
