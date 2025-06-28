<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class WhatsAppMessage extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_messages';

    protected $fillable = [
        'user_type',
        'user_id', 
        'phone_number',
        'message_type',
        'content',
        'direction',
        'status',
        'webhook_id',
        'response_to',
        'metadata',
        'sent_at',
        'delivered_at',
        'read_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime', 
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope للرسائل المرسلة
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope للرسائل المعلقة
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope للرسائل الفاشلة
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope حسب نوع المستخدم
     */
    public function scopeByUserType($query, $userType)
    {
        return $query->where('user_type', $userType);
    }

    /**
     * Scope حسب رقم الهاتف
     */
    public function scopeByPhone($query, $phone)
    {
        return $query->where('phone_number', $phone);
    }    /**
     * العلاقة مع المستخدم (ديناميكية حسب نوع المستخدم)
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        switch ($this->user_type) {
            case 'teacher':
                return $this->belongsTo(Teacher::class, 'user_id');
            case 'student':
                return $this->belongsTo(Student::class, 'user_id');
            case 'admin':
            case 'supervisor':
            default:
                return $this->belongsTo(User::class, 'user_id');
        }
    }

    /**
     * العلاقة مع الرسالة المرجعية
     */
    public function responseToMessage(): BelongsTo
    {
        return $this->belongsTo(WhatsAppMessage::class, 'response_to');
    }

    /**
     * العلاقة مع الردود
     */
    public function responses()
    {
        return $this->hasMany(WhatsAppMessage::class, 'response_to');
    }

    /**
     * إنشاء رسالة إشعار
     */
    public static function createNotification(
        string $userType,
        ?int $userId,
        string $phoneNumber,
        string $content,
        string $messageType = 'notification',
        array $metadata = []
    ): self {
        return self::create([
            'user_type' => $userType,
            'user_id' => $userId,
            'phone_number' => $phoneNumber,
            'message_type' => $messageType,
            'content' => $content,
            'direction' => 'outgoing',
            'status' => 'pending',
            'metadata' => $metadata,
        ]);
    }    /**
     * تحديد الرسالة كفاشلة
     */
    public function markAsFailed(?string $error = null): bool
    {
        $metadata = $this->metadata ?? [];
        if ($error) {
            $metadata['error'] = $error;
            $metadata['failed_at'] = now()->toISOString();
        }

        return $this->update([
            'status' => 'failed',
            'metadata' => $metadata,
        ]);
    }    /**
     * تحديد الرسالة كمرسلة
     */
    public function markAsSent(?string $webhookId = null): bool
    {
        $updates = [
            'status' => 'sent',
            'sent_at' => now(),
        ];

        if ($webhookId) {
            $updates['webhook_id'] = $webhookId;
        }

        return $this->update($updates);
    }

    /**
     * تحديد الرسالة كمستلمة
     */
    public function markAsDelivered(): bool
    {
        return $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }

    /**
     * تحديد الرسالة كمقروءة
     */
    public function markAsRead(): bool
    {
        return $this->update([
            'status' => 'read',
            'read_at' => now(),
        ]);
    }

    /**
     * الحصول على تسمية الحالة
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'في الانتظار',
            'sent' => 'مرسلة',
            'delivered' => 'مستلمة',
            'read' => 'مقروءة',
            'failed' => 'فاشلة',
            default => 'غير معروف',
        };
    }

    /**
     * الحصول على تسمية نوع المستخدم
     */
    public function getUserTypeLabelAttribute(): string
    {
        return match ($this->user_type) {
            'teacher' => 'معلم',
            'student' => 'طالب',
            'parent' => 'ولي أمر',
            'admin' => 'مدير',
            default => 'غير معروف',
        };
    }

    /**
     * الحصول على تسمية نوع الرسالة
     */    public function getMessageTypeLabelAttribute(): string
    {
        return match ($this->message_type) {
            'notification' => 'إشعار',
            'command' => 'أمر',
            'response' => 'رد',
            'reminder' => 'تذكير',
            default => 'غير معروف',
        };
    }

    /**
     * الحصول على تسمية الاتجاه
     */
    public function getDirectionLabelAttribute(): string
    {        return match ($this->direction) {
            'outgoing' => 'صادرة',
            'incoming' => 'واردة',
            default => 'غير معروف',
        };
    }

    /**
     * الحصول على اسم المستخدم
     */
    public function getUserNameAttribute(): ?string
    {
        $user = $this->user;
        if ($user) {
            return $user->name ?? $user->full_name ?? null;
        }
        return null;
    }

    /**
     * فحص ما إذا كانت الرسالة قديمة (أكثر من ساعة ولم ترسل)
     */
    public function isStale(): bool
    {
        return $this->status === 'pending' && 
               $this->created_at->lt(Carbon::now()->subHour());
    }

    /**
     * فحص ما إذا كانت الرسالة قابلة لإعادة الإرسال
     */
    public function canBeRetried(): bool
    {
        return in_array($this->status, ['pending', 'failed']);
    }
}