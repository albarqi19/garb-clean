<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Curriculum extends Model
{
    use HasFactory;
    
    /**
     * نموذج المنهج التعليمي
     */
    protected $table = 'curricula';
    
    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'type',
        'description',
        'is_active',
    ];
    
    /**
     * الخصائص التي يجب تحويلها إلى أنواع محددة.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];
    
    /**
     * العلاقة: المنهج له العديد من المستويات
     */
    public function levels(): HasMany
    {
        return $this->hasMany(CurriculumLevel::class);
    }
    
    /**
     * العلاقة: المنهج له العديد من الخطط
     */
    public function plans(): HasMany
    {
        return $this->hasMany(CurriculumPlan::class);
    }
      /**
     * العلاقة: المنهج له العديد من بيانات تقدم الطلاب
     */
    public function studentCurricula(): HasMany
    {
        return $this->hasMany(StudentCurriculum::class);
    }
    
    /**
     * العلاقة: المنهج له العديد من سجلات تقدم الطلاب
     */
    public function studentProgress(): HasMany
    {
        return $this->hasMany(StudentProgress::class);
    }
    
    /**
     * نطاق: المناهج النشطة فقط
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * نطاق: مناهج التلقين فقط
     */
    public function scopeRecitation($query)
    {
        return $query->where('type', 'منهج تلقين');
    }
    
    /**
     * نطاق: مناهج الطلاب فقط
     */
    public function scopeStudent($query)
    {
        return $query->where('type', 'منهج طالب');
    }
}
