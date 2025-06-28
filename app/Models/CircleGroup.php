<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CircleGroup extends Model
{
    use HasFactory;    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'quran_circle_id',
        'teacher_id',
        'name',
        'status',
        'description',
        'meeting_days',
        'additional_info',
    ];
    
    /**
     * الخصائص التي يجب تحويلها.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'meeting_days' => 'array',
    ];

    /**
     * المدرسة القرآنية التي تنتمي إليها هذه الحلقة الفرعية
     */
    public function quranCircle(): BelongsTo
    {
        return $this->belongsTo(QuranCircle::class);
    }

    /**
     * معلم الحلقة الفرعية
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * الطلاب المنتمون لهذه الحلقة الفرعية
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }
}
