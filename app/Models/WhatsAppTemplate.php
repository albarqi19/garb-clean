<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WhatsAppTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_key',
        'template_name',
        'template_content',
        'description',
        'variables',
        'category',
        'is_active',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * الحصول على محتوى القالب مع استبدال المتغيرات
     */
    public function getProcessedContent(array $variables = []): string
    {
        // أولاً، محاولة الحصول على المحتوى من content
        $content = $this->content ?? $this->template_content ?? '';
        
        // إضافة تشخيص لمعرفة المتغيرات المرسلة
        \Log::info('WhatsApp Template Processing', [
            'template_key' => $this->template_key,
            'variables' => $variables,
            'original_content' => $content
        ]);
        
        foreach ($variables as $key => $value) {
            // استبدال المتغيرات بالأقواس المفردة {key}
            $content = str_replace('{' . $key . '}', $value, $content);
            // استبدال المتغيرات بالأقواس المزدوجة {{key}}
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }
        
        // تشخيص النتيجة النهائية
        \Log::info('WhatsApp Template Result', [
            'processed_content' => $content
        ]);
        
        return $content;
    }

    /**
     * البحث عن قالب بالمفتاح
     */
    public static function findByKey(string $key): ?self
    {
        return self::where('template_key', $key)->where('is_active', true)->first();
    }

    /**
     * الحصول على القوالب حسب التصنيف
     */
    public static function getByCategory(string $category): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('category', $category)->where('is_active', true)->get();
    }
}
