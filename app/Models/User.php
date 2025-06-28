<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'phone',
        'is_active',
        'identity_number',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * تعيينات الإشراف على الحلقات
     */
    public function circleSupervisors(): HasMany
    {
        return $this->hasMany(CircleSupervisor::class, 'supervisor_id');
    }

    /**
     * جلسات التسميع التي أشرف عليها المعلم
     */
    public function recitationSessions(): HasMany
    {
        return $this->hasMany(RecitationSession::class, 'teacher_id');
    }

    /**
     * آخر جلسة تسميع أشرف عليها المعلم
     */
    public function getLastRecitationSessionAttribute(): ?RecitationSession
    {
        return $this->recitationSessions()
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * جلسات التسميع للمعلم في فترة معينة
     */
    public function recitationSessionsInPeriod($startDate, $endDate)
    {
        return $this->recitationSessions()
            ->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * متوسط درجات جلسات التسميع التي أشرف عليها المعلم
     */
    public function getAverageSessionGradeAttribute(): ?float
    {
        return $this->recitationSessions()
            ->whereNotNull('grade')
            ->avg('grade');
    }

    /**
     * عدد الجلسات التي أشرف عليها المعلم
     */
    public function getRecitationSessionsCountAttribute(): int
    {
        return $this->recitationSessions()->count();
    }
}
