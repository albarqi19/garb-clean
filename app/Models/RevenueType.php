<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RevenueType extends Model
{
    use HasFactory;

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    /**
     * الخصائص التي يجب تحويلها.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * الإيرادات المرتبطة بهذا النوع
     */
    public function revenues(): HasMany
    {
        return $this->hasMany(Revenue::class);
    }

    /**
     * الحصول على أنواع الإيرادات النشطة فقط
     */
    public static function getActive()
    {
        return self::where('is_active', true)->get();
    }
}
