<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CircleNeedsRequestActivity extends Model
{
    use HasFactory;

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array
     */
    protected $fillable = [
        'request_id',
        'user_id',
        'activity_type',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    /**
     * الخصائص التي يجب تحويلها.
     *
     * @var array
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * العلاقة مع طلب احتياج الحلقة.
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(CircleNeedsRequest::class, 'request_id');
    }

    /**
     * العلاقة مع المستخدم الذي قام بالنشاط.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * الحصول على نص قابل للعرض للتغيير بين القيم القديمة والجديدة.
     */
    public function getChangesDisplayAttribute(): string
    {
        if (empty($this->old_values) || empty($this->new_values)) {
            return '';
        }

        $changes = [];
        foreach ($this->new_values as $key => $newValue) {
            $oldValue = $this->old_values[$key] ?? null;
            if ($newValue !== $oldValue) {
                $changes[] = "تغيير {$key} من '{$oldValue}' إلى '{$newValue}'";
            }
        }

        return implode(', ', $changes);
    }

    /**
     * الحصول على اسم المستخدم الذي قام بالنشاط.
     */
    public function getUserNameAttribute(): string
    {
        return $this->user ? $this->user->name : 'النظام';
    }
}