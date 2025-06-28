<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchFollower extends Model
{
    use HasFactory;

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'phone',
        'email',
        'source',
        'is_donor',
        'registration_date',
        'notes',
        'registered_by',
    ];

    /**
     * الخصائص التي يجب تحويلها.
     *
     * @var array
     */
    protected $casts = [
        'registration_date' => 'date',
        'is_donor' => 'boolean',
    ];

    /**
     * علاقة مع المستخدم الذي سجل المتابع.
     */
    public function registeredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    /**
     * نطاق الاستعلام للمتابعين حسب الفرع المصدر.
     */
    public function scopeBySource($query, $source)
    {
        return $query->where('source', $source);
    }

    /**
     * نطاق الاستعلام للمتابعين الذين هم بالفعل متبرعين.
     */
    public function scopeDonors($query)
    {
        return $query->where('is_donor', true);
    }

    /**
     * نطاق الاستعلام للمتابعين الذين ليسوا متبرعين بعد.
     */
    public function scopeNonDonors($query)
    {
        return $query->where('is_donor', false);
    }

    /**
     * نطاق الاستعلام للمتابعين المسجلين في فترة زمنية معينة.
     */
    public function scopeRegisteredBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('registration_date', [$startDate, $endDate]);
    }

    /**
     * نطاق الاستعلام للمتابعين المسجلين في السنة الحالية.
     */
    public function scopeCurrentYear($query)
    {
        return $query->whereYear('registration_date', now()->year);
    }

    /**
     * نطاق الاستعلام للمتابعين المسجلين في الشهر الحالي.
     */
    public function scopeCurrentMonth($query)
    {
        return $query->whereMonth('registration_date', now()->month)
                     ->whereYear('registration_date', now()->year);
    }
}