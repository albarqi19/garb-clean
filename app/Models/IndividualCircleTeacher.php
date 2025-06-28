<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndividualCircleTeacher extends Model
{
    use HasFactory;

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'circle_id',
        'name',
        'phone',
    ];

    /**
     * الحلقة المرتبطة بهذا المعلم
     */
    public function circle(): BelongsTo
    {
        return $this->belongsTo(QuranCircle::class, 'circle_id');
    }
}
