<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class WhatsAppConversation extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'whatsapp_conversations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'phone_number',
        'current_state',
        'context_data',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'context_data' => 'array',
        'expires_at' => 'datetime',
    ];

    /**
     * بدء محادثة جديدة أو الحصول على المحادثة الحالية
     *
     * @param string $phoneNumber
     * @param string $state
     * @param array $contextData
     * @param int $expiresInMinutes
     * @return static
     */
    public static function startConversation(
        string $phoneNumber, 
        string $state = 'idle', 
        array $contextData = [], 
        int $expiresInMinutes = 30
    ): static {
        // البحث عن محادثة نشطة
        $conversation = static::where('phone_number', $phoneNumber)
            ->where('expires_at', '>', now())
            ->first();

        if ($conversation) {
            // تحديث المحادثة الموجودة
            $conversation->current_state = $state;
            $conversation->context_data = array_merge($conversation->context_data ?? [], $contextData);
            $conversation->expires_at = now()->addMinutes($expiresInMinutes);
            $conversation->save();
        } else {
            // إنشاء محادثة جديدة
            $conversation = static::create([
                'phone_number' => $phoneNumber,
                'current_state' => $state,
                'context_data' => $contextData,
                'expires_at' => now()->addMinutes($expiresInMinutes),
            ]);
        }

        return $conversation;
    }

    /**
     * تحديث حالة المحادثة
     *
     * @param string $state
     * @param array $contextData
     * @return bool
     */
    public function updateState(string $state, array $contextData = []): bool
    {
        $this->current_state = $state;
        
        if (!empty($contextData)) {
            $this->context_data = array_merge($this->context_data ?? [], $contextData);
        }

        // تمديد وقت انتهاء الصلاحية
        $this->expires_at = now()->addMinutes(30);

        return $this->save();
    }

    /**
     * إنهاء المحادثة
     *
     * @return bool
     */
    public function endConversation(): bool
    {
        $this->current_state = 'idle';
        $this->context_data = [];
        $this->expires_at = now();

        return $this->save();
    }

    /**
     * التحقق من انتهاء صلاحية المحادثة
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expires_at < now();
    }

    /**
     * الحصول على بيانات السياق
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function getContextData(?string $key = null, $default = null)
    {
        if ($key) {
            return $this->context_data[$key] ?? $default;
        }

        return $this->context_data ?? [];
    }

    /**
     * البحث عن المحادثات النشطة
     */
    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * البحث عن المحادثات المنتهية الصلاحية
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * تنظيف المحادثات المنتهية الصلاحية
     *
     * @return int
     */
    public static function cleanExpiredConversations(): int
    {
        return static::expired()->delete();
    }

    /**
     * البحث حسب رقم الهاتف
     */
    public function scopeByPhone($query, string $phone)
    {
        return $query->where('phone_number', $phone);
    }

    /**
     * البحث حسب الحالة
     */
    public function scopeByState($query, string $state)
    {
        return $query->where('current_state', $state);
    }
}
