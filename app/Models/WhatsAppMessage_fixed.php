<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class WhatsAppMessage extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'whatsapp_messages';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_type',
        'user_id',
        'phone_number',
        'message_content',
        'message_type',
        'status',
        'external_id',
        'api_response',
        'error_message',
        'template_data',
        'metadata',
        'sent_at',
        'failed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'template_data' => 'array',
        'metadata' => 'array',
        'api_response' => 'array',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    /**
     * Create a new notification message.
     *
     * @param string $userType
     * @param int|null $userId
     * @param string $phoneNumber
     * @param string $content
     * @param string $messageType
     * @param array|null $metadata
     * @return static
     */
    public static function createNotification(
        string $userType,
        ?int $userId,
        string $phoneNumber,
        string $content,
        string $messageType = 'notification',
        ?array $metadata = null
    ): static {
        return static::create([
            'user_type' => $userType,
            'user_id' => $userId,
            'phone_number' => static::formatPhoneNumber($phoneNumber),
            'message_content' => $content,
            'message_type' => $messageType,
            'status' => 'pending',
            'metadata' => $metadata,
        ]);
    }

    /**
     * Mark message as failed.
     *
     * @param string $errorMessage
     * @return bool
     */
    public function markAsFailed(string $errorMessage): bool
    {
        return $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'failed_at' => now(),
        ]);
    }

    /**
     * Mark message as sent.
     *
     * @param array|null $apiResponse
     * @param string|null $externalId
     * @return bool
     */
    public function markAsSent(?array $apiResponse = null, ?string $externalId = null): bool
    {
        return $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'api_response' => $apiResponse,
            'external_id' => $externalId,
            'error_message' => null,
        ]);
    }

    /**
     * Format phone number for WhatsApp.
     *
     * @param string $phoneNumber
     * @return string
     */
    protected static function formatPhoneNumber(string $phoneNumber): string
    {
        // إزالة المسافات والرموز غير المرغوبة
        $phone = preg_replace('/[^\d+]/', '', $phoneNumber);
        
        // إضافة رمز الدولة للسعودية إذا لم يكن موجوداً
        if (!str_starts_with($phone, '+') && !str_starts_with($phone, '966')) {
            if (str_starts_with($phone, '05')) {
                $phone = '+966' . substr($phone, 1);
            } else {
                $phone = '+966' . $phone;
            }
        }
        
        return $phone;
    }

    /**
     * Scope for pending messages.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for sent messages.
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope for failed messages.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for messages by user type.
     */
    public function scopeByUserType($query, string $userType)
    {
        return $query->where('user_type', $userType);
    }

    /**
     * Scope for messages by phone number.
     */
    public function scopeByPhone($query, string $phone)
    {
        return $query->where('phone_number', static::formatPhoneNumber($phone));
    }

    /**
     * Scope for messages in date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Get the user who the message was sent to.
     */
    public function user()
    {
        return $this->morphTo('user', 'user_type', 'user_id');
    }

    /**
     * Check if message is successful.
     */
    public function getIsSuccessfulAttribute(): bool
    {
        return $this->status === 'sent';
    }

    /**
     * Check if message has failed.
     */
    public function getHasFailedAttribute(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if message is pending.
     */
    public function getIsPendingAttribute(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Get formatted status for display.
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'في انتظار الإرسال',
            'sent' => 'تم الإرسال',
            'failed' => 'فشل الإرسال',
            'delivered' => 'تم التسليم',
            'read' => 'تم القراءة',
            default => 'غير محدد'
        };
    }

    /**
     * Get user type label for display.
     */
    public function getUserTypeLabelAttribute(): string
    {
        return match($this->user_type) {
            'teacher' => 'معلم',
            'student' => 'طالب',
            'parent' => 'ولي أمر',
            'admin' => 'إداري',
            'custom' => 'مخصص',
            default => 'غير محدد'
        };
    }

    /**
     * Get message type label for display.
     */
    public function getMessageTypeLabelAttribute(): string
    {
        return match($this->message_type) {
            'welcome' => 'ترحيب',
            'notification' => 'إشعار',
            'attendance' => 'حضور',
            'announcement' => 'إعلان',
            'reminder' => 'تذكير',
            'test' => 'اختبار',
            'custom' => 'مخصص',
            default => 'غير محدد'
        };
    }
}
