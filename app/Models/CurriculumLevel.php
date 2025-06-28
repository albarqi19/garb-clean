<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CurriculumLevel extends Model
{
    use HasFactory;
    
    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'curriculum_id',
        'name',
        'level_order',
        'description',
        'is_active',
    ];
    
    /**
     * الخصائص التي يجب تحويلها إلى أنواع محددة.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'level_order' => 'integer',
        'is_active' => 'boolean',
    ];
    
    /**
     * العلاقة: المستوى ينتمي إلى منهج
     */
    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }
    
    /**
     * العلاقة: المستوى له العديد من الخطط
     */
    public function plans(): HasMany
    {
        return $this->hasMany(CurriculumPlan::class);
    }
    
    /**
     * العلاقة: المستوى له العديد من بيانات تقدم الطلاب
     */
    public function studentCurricula(): HasMany
    {
        return $this->hasMany(StudentCurriculum::class);
    }
    
    /**
     * نطاق: المستويات النشطة فقط
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * نطاق: ترتيب المستويات تصاعدياً
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('level_order', 'asc');
    }
}
