<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingActivity extends Model
{
    use HasFactory;

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'type',
        'description',
        'activity_date',
        'target_audience',
        'status',
        'platform',
        'reach_count',
        'interaction_count',
        'file_attachment',
        'created_by',
        'notes',
    ];

    /**
     * الخصائص التي يجب تحويلها.
     *
     * @var array
     */
    protected $casts = [
        'activity_date' => 'date',
        'reach_count' => 'integer',
        'interaction_count' => 'integer',
    ];

    /**
     * علاقة مع المستخدم الذي أنشأ النشاط.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * حساب معدل التفاعل (نسبة التفاعلات إلى الوصول)
     */
    public function getInteractionRateAttribute(): ?float
    {
        if (!$this->reach_count || $this->reach_count <= 0) {
            return null;
        }

        return ($this->interaction_count / $this->reach_count) * 100;
    }

    /**
     * نطاق الاستعلام للأنشطة حسب النوع.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * نطاق الاستعلام للأنشطة حسب الحالة.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * نطاق الاستعلام للمنشورات الإعلامية.
     */
    public function scopePosts($query)
    {
        return $query->where('type', 'منشور إعلامي');
    }

    /**
     * نطاق الاستعلام للرسائل للداعمين.
     */
    public function scopeDonorMessages($query)
    {
        return $query->where('type', 'رسالة للداعمين');
    }

    /**
     * نطاق الاستعلام للمشاريع المطروحة عبر المتجر الإلكتروني.
     */
    public function scopeStoreProjects($query)
    {
        return $query->where('type', 'مشروع متجر إلكتروني');
    }

    /**
     * نطاق الاستعلام للمشاريع المرفوعة للمؤسسات المانحة.
     */
    public function scopeDonorProjects($query)
    {
        return $query->where('type', 'مشروع مؤسسة مانحة');
    }

    /**
     * نطاق الاستعلام للأنشطة المكتملة.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'مكتمل');
    }

    /**
     * نطاق الاستعلام للأنشطة في فترة زمنية معينة.
     */
    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('activity_date', [$startDate, $endDate]);
    }

    /**
     * نطاق الاستعلام للأنشطة في الشهر الحالي.
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('activity_date', now()->month)
                     ->whereYear('activity_date', now()->year);
    }

    /**
     * الخصائص المحسوبة.
     */
    protected $appends = [
        'interaction_rate',
    ];
}