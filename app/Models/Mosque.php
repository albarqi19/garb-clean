<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mosque extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'neighborhood',
        'street',
        'location_lat',
        'location_long',
        'contact_number',
    ];

    /**
     * الحلقات المرتبطة بهذا المسجد
     */
    public function quranCircles(): HasMany
    {
        return $this->hasMany(QuranCircle::class);
    }

    /**
     * المهام المرتبطة بهذا المسجد
     */
    public function tasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'taskable');
    }

    /**
     * جداول المعلمين في هذا المسجد
     */
    public function teacherSchedules(): HasMany
    {
        return $this->hasMany(TeacherMosqueSchedule::class);
    }

    /**
     * جداول المعلمين النشطة في هذا المسجد
     */
    public function activeTeacherSchedules(): HasMany
    {
        return $this->hasMany(TeacherMosqueSchedule::class)->where('is_active', true);
    }

    /**
     * المهام النشطة المرتبطة بهذا المسجد
     */
    public function activeTasks()
    {
        return $this->tasks()->whereNotIn('status', ['مكتملة', 'ملغاة']);
    }

    /**
     * المهام المتأخرة المرتبطة بهذا المسجد
     */
    public function overdueTasks()
    {
        return $this->tasks()->where('status', 'متأخرة')
                 ->orWhere(function($query) {
                     $query->where('due_date', '<', now())
                           ->whereNotIn('status', ['مكتملة', 'ملغاة']);
                 });
    }

    /**
     * حساب عدد الحلق في المسجد
     */
    public function getCirclesCountAttribute(): int
    {
        return $this->quranCircles()->count();
    }

    /**
     * الحصول على العنوان الكامل للمسجد
     */
    public function getAddressAttribute(): string
    {
        $address = '';
        if ($this->neighborhood) {
            $address .= $this->neighborhood;
        }
        if ($this->street) {
            $address .= ($address ? '، ' : '') . $this->street;
        }
        return $address ?: 'غير محدد';
    }

    /**
     * حساب عدد المعلمين النشطين في المسجد
     */
    public function getActiveTeachersCountAttribute(): int
    {
        return $this->activeTeacherSchedules()->distinct('teacher_id')->count('teacher_id');
    }

    /**
     * الحصول على المعلمين النشطين في يوم معين
     */
    public function getActiveTeachersForDay(string $day): \Illuminate\Database\Eloquent\Collection
    {
        return $this->activeTeacherSchedules()
                    ->where('day_of_week', $day)
                    ->with('teacher')
                    ->get()
                    ->pluck('teacher');
    }
    
    /**
     * الحصول على رابط الموقع في خرائط جوجل
     */
    public function getGoogleMapsUrlAttribute(): ?string
    {
        if (!$this->location_lat || !$this->location_long) {
            return null;
        }
        
        $query = [];
        
        if ($this->name) {
            $query[] = 'q=' . urlencode($this->name);
        }
        
        // إضافة المنطقة والشارع إلى العنوان في الرابط
        $address = '';
        if ($this->neighborhood) {
            $address .= $this->neighborhood;
        }
        if ($this->street) {
            $address .= ($address ? '، ' : '') . $this->street;
        }
        
        if ($address) {
            $query[] = 'address=' . urlencode($address);
        }
        
        $query[] = 'll=' . $this->location_lat . ',' . $this->location_long;
        
        return 'https://www.google.com/maps?' . implode('&', $query);
    }
}
