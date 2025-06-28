<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HasActivityLog;
use Carbon\Carbon;

class SocialMediaPost extends Model
{
    use HasFactory, SoftDeletes, HasActivityLog;

    // اسم العرض للنموذج في سجل الأنشطة
    public static $displayName = 'منشور';
    
    // اسم الوحدة للنموذج في سجل الأنشطة
    public static $moduleName = 'التسويق';

    /**
     * الحقول المستبعدة من تسجيل الأنشطة
     */
    protected $activityExcluded = [
        'updated_at', 
        'created_at',
        'deleted_at',
    ];

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'content',
        'post_type',
        'post_link',
        'publish_date',
        'publish_date_hijri',
        'views_count',
        'likes_count',
        'comments_count',
        'shares_count',
        'saves_count',
        'interaction_rate',
        'twitter',
        'instagram',
        'facebook',
        'telegram',
        'snapchat',
        'whatsapp',
        'youtube',
        'status',
        'target_interaction',
        'achievement_percentage',
        'created_by',
        'published_by',
        'marketing_activity_id',
        'marketing_kpi_id',
        'target_audience',
        'notes',
        'marketing_analysis',
    ];

    /**
     * الخصائص التي يجب تحويلها.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'publish_date' => 'date',
        'twitter' => 'boolean',
        'instagram' => 'boolean',
        'facebook' => 'boolean',
        'telegram' => 'boolean',
        'snapchat' => 'boolean',
        'whatsapp' => 'boolean',
        'youtube' => 'boolean',
        'interaction_rate' => 'float',
        'achievement_percentage' => 'float',
    ];

    /**
     * المستخدم الذي أنشأ المنشور
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * المستخدم الذي قام بنشر المنشور
     */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    /**
     * النشاط التسويقي المرتبط بالمنشور
     */
    public function marketingActivity(): BelongsTo
    {
        return $this->belongsTo(MarketingActivity::class);
    }

    /**
     * مؤشر الأداء المرتبط بالمنشور
     */
    public function marketingKpi(): BelongsTo
    {
        return $this->belongsTo(MarketingKpi::class);
    }
    
    /**
     * تحديث معدل التفاعل بناءً على الإحصائيات المتوفرة
     * 
     * @return float معدل التفاعل المحسوب
     */
    public function calculateInteractionRate(): float
    {
        // حساب إجمالي التفاعلات
        $totalInteractions = $this->likes_count + $this->comments_count + $this->shares_count + $this->saves_count;
        
        // تجنب القسمة على صفر
        if ($this->views_count <= 0) {
            $this->interaction_rate = 0;
        } else {
            // حساب معدل التفاعل كنسبة مئوية
            $this->interaction_rate = ($totalInteractions / $this->views_count) * 100;
        }
        
        $this->save();
        
        return $this->interaction_rate;
    }
    
    /**
     * تحديث نسبة تحقيق الهدف
     * 
     * @return float نسبة تحقيق الهدف
     */
    public function calculateAchievementPercentage(): float
    {
        // تجنب القسمة على صفر ومعالجة القيم الفارغة
        if (!$this->target_interaction || $this->target_interaction <= 0) {
            $this->achievement_percentage = 0;
        } else {
            // حساب إجمالي التفاعلات
            $totalInteractions = $this->likes_count + $this->comments_count + $this->shares_count + $this->saves_count;
            
            // حساب نسبة تحقيق الهدف
            $this->achievement_percentage = ($totalInteractions / $this->target_interaction) * 100;
        }
        
        $this->save();
        
        return $this->achievement_percentage;
    }
    
    /**
     * تحديث كل الإحصاءات دفعة واحدة
     * 
     * @param array $stats إحصاءات المنشور [views, likes, comments, shares, saves]
     * @return self
     */
    public function updateStats(array $stats): self
    {
        // تحديث الإحصاءات
        if (isset($stats['views'])) {
            $this->views_count = $stats['views'];
        }
        
        if (isset($stats['likes'])) {
            $this->likes_count = $stats['likes'];
        }
        
        if (isset($stats['comments'])) {
            $this->comments_count = $stats['comments'];
        }
        
        if (isset($stats['shares'])) {
            $this->shares_count = $stats['shares'];
        }
        
        if (isset($stats['saves'])) {
            $this->saves_count = $stats['saves'];
        }
        
        // تحديث معدل التفاعل ونسبة تحقيق الهدف
        $this->calculateInteractionRate();
        $this->calculateAchievementPercentage();
        
        return $this;
    }
    
    /**
     * نشر المنشور على منصة محددة
     * 
     * @param string $platform اسم المنصة (twitter, instagram, facebook, telegram, snapchat, whatsapp, youtube)
     * @param string|null $postLink رابط المنشور بعد النشر
     * @param int|null $publishedBy معرف المستخدم الذي قام بالنشر
     * @return self
     */
    public function publishOn(string $platform, ?string $postLink = null, ?int $publishedBy = null): self
    {
        // التأكد من أن المنصة موجودة في قائمة المنصات المدعومة
        $supportedPlatforms = ['twitter', 'instagram', 'facebook', 'telegram', 'snapchat', 'whatsapp', 'youtube'];
        
        if (in_array($platform, $supportedPlatforms)) {
            // تحديث حالة النشر على المنصة المحددة
            $this->{$platform} = true;
            
            // إذا كان هذا أول نشر، قم بتحديث حالة المنشور وتاريخ النشر
            if ($this->status === 'مجدول') {
                $this->status = 'منشور';
                $this->publish_date = Carbon::now();
                
                // تحديث التاريخ الهجري (يمكن استخدام مكتبة خارجية لحساب التاريخ الهجري بدقة)
                // هنا استخدمنا قيمة وهمية كمثال فقط
                $this->publish_date_hijri = 'تاريخ هجري';
            }
            
            // تحديث رابط المنشور إذا تم تمريره
            if ($postLink) {
                $this->post_link = $postLink;
            }
            
            // تحديث ناشر المنشور إذا تم تمريره
            if ($publishedBy) {
                $this->published_by = $publishedBy;
            }
            
            $this->save();
        }
        
        return $this;
    }
    
    /**
     * الحصول على قائمة المنصات التي تم النشر عليها
     * 
     * @return array قائمة بأسماء المنصات التي تم النشر عليها
     */
    public function getPublishedPlatforms(): array
    {
        $platforms = [];
        
        // دعم المنصات
        $supportedPlatforms = ['twitter', 'instagram', 'facebook', 'telegram', 'snapchat', 'whatsapp', 'youtube'];
        
        foreach ($supportedPlatforms as $platform) {
            if ($this->{$platform}) {
                $platforms[] = $platform;
            }
        }
        
        return $platforms;
    }
    
    /**
     * الحصول على عدد المنصات التي تم النشر عليها
     * 
     * @return int عدد المنصات التي تم النشر عليها
     */
    public function getPlatformsCountAttribute(): int
    {
        return count($this->getPublishedPlatforms());
    }
    
    /**
     * الحصول على إجمالي التفاعلات (الإعجابات + التعليقات + المشاركات + الحفظ)
     * 
     * @return int إجمالي التفاعلات
     */
    public function getTotalInteractionsAttribute(): int
    {
        return $this->likes_count + $this->comments_count + $this->shares_count + $this->saves_count;
    }
    
    /**
     * الإستعلامات المعرفة مسبقاً - المنشورات حسب النوع
     */
    public function scopeOfType($query, $postType)
    {
        return $query->where('post_type', $postType);
    }
    
    /**
     * الإستعلامات المعرفة مسبقاً - المنشورات المجدولة
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'مجدول');
    }
    
    /**
     * الإستعلامات المعرفة مسبقاً - المنشورات المنشورة
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'منشور');
    }
    
    /**
     * الإستعلامات المعرفة مسبقاً - المنشورات المجدولة للأسبوع الحالي
     */
    public function scopeScheduledForCurrentWeek($query)
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();
        
        return $query->where('status', 'مجدول')
                    ->whereBetween('publish_date', [$startOfWeek, $endOfWeek]);
    }
    
    /**
     * الإستعلامات المعرفة مسبقاً - المنشورات المنشورة في شهر معين
     */
    public function scopePublishedInMonth($query, $year, $month)
    {
        $startOfMonth = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endOfMonth = Carbon::createFromDate($year, $month, 1)->endOfMonth();
        
        return $query->where('status', 'منشور')
                    ->whereBetween('publish_date', [$startOfMonth, $endOfMonth]);
    }
    
    /**
     * الإستعلامات المعرفة مسبقاً - المنشورات الأكثر تفاعلاً
     */
    public function scopeMostInteractive($query, $limit = 10)
    {
        return $query->where('status', 'منشور')
                    ->orderBy('interaction_rate', 'desc')
                    ->limit($limit);
    }
    
    /**
     * الإستعلامات المعرفة مسبقاً - المنشورات التي حققت هدفها
     */
    public function scopeAchievedTarget($query)
    {
        return $query->where('achievement_percentage', '>=', 100);
    }
    
    /**
     * الإستعلامات المعرفة مسبقاً - المنشورات التي لم تحقق هدفها بعد
     */
    public function scopeBelowTarget($query)
    {
        return $query->where('status', 'منشور')
                    ->where('achievement_percentage', '<', 100);
    }
    
    /**
     * الحصول على وصف النشاط لكل حدث
     */
    public function getActivityDescriptionForEvent(string $event): string
    {
        return match($event) {
            'created' => "تم إنشاء منشور جديد: {$this->title}",
            'updated' => "تم تعديل المنشور: {$this->title}",
            'deleted' => "تم حذف المنشور: {$this->title}",
            default => parent::getActivityDescriptionForEvent($event),
        };
    }
}
