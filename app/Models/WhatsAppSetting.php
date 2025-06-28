<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppSetting extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'whatsapp_settings';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'setting_key',
        'setting_value',
        'description',
        'is_active'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, $default = null)
    {
        $setting = static::where('setting_key', $key)
                         ->where('is_active', true)
                         ->first();
        
        $value = $setting ? $setting->setting_value : $default;
        
        // Convert string boolean values back to actual boolean
        if ($value === '1' || $value === 'true' || $value === true) {
            return true;
        } elseif ($value === '0' || $value === 'false' || $value === false) {
            return false;
        }
        
        return $value;
    }

    /**
     * Set a setting value by key.
     */
    public static function set(string $key, $value, ?string $description = null): bool
    {
        // Convert boolean values to string for consistent storage
        if (is_bool($value)) {
            $value = $value ? '1' : '0';
        }
        
        return static::updateOrCreate(
            ['setting_key' => $key],
            [
                'setting_value' => $value,
                'description' => $description,
                'is_active' => true
            ]
        ) ? true : false;
    }

    /**
     * Check if notifications are enabled.
     */
    public static function notificationsEnabled(): bool
    {
        return static::get('notifications_enabled', false) === true;
    }

    /**
     * Check if a specific notification type is enabled.
     */
    public static function isNotificationEnabled(string $notificationType): bool
    {
        return static::get($notificationType, false) === true;
    }

    /**
     * Get API configuration.
     */
    public static function getApiConfig(): array
    {
        return [
            'url' => static::get('api_url'),
            'token' => static::get('api_token'),
            'webhook_verify_token' => static::get('webhook_verify_token'),
        ];
    }
}
