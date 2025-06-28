<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvaluationPeriod extends Model
{
    use HasFactory;

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'description',
        'status',
        'is_active',
    ];

    /**
     * الخصائص التي يجب تحويلها.
     *
     * @var array
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // تحديث حالة فترة التقييم تلقائياً بناءً على التواريخ
        static::creating(function ($period) {
            $period->updateStatus();
        });

        static::updating(function ($period) {
            $period->updateStatus();
        });
    }

    /**
     * تحديث حالة فترة التقييم بناءً على التواريخ.
     */
    public function updateStatus(): void
    {
        $today = now()->startOfDay();

        if ($today < $this->start_date) {
            $this->status = 'قادمة';
        } elseif ($today <= $this->end_date) {
            $this->status = 'جارية';
        } else {
            $this->status = 'منتهية';
        }
    }

    /**
     * علاقة مع زيارات المشرفين.
     */
    public function supervisorVisits(): HasMany
    {
        return $this->hasMany(SupervisorVisit::class);
    }

    /**
     * الحصول على عدد الزيارات في هذه الفترة.
     */
    public function getVisitsCountAttribute(): int
    {
        return $this->supervisorVisits()->count();
    }

    /**
     * الحصول على عدد الزيارات المكتملة في هذه الفترة.
     */
    public function getCompletedVisitsCountAttribute(): int
    {
        return $this->supervisorVisits()->completed()->count();
    }

    /**
     * نسبة اكتمال الزيارات في هذه الفترة.
     */
    public function getCompletionRateAttribute(): float
    {
        $totalVisits = $this->visits_count;
        if ($totalVisits <= 0) {
            return 0;
        }

        return ($this->completed_visits_count / $totalVisits) * 100;
    }

    /**
     * نطاق الاستعلام للفترات النشطة.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * نطاق الاستعلام للفترات حسب الحالة.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * الخصائص الملحقة.
     */
    protected $appends = [
        'visits_count',
        'completed_visits_count',
        'completion_rate',
    ];
}