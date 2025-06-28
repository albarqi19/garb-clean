<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class TaskAttachment extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'task_id',
        'user_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'description',
    ];

    /**
     * العلاقة مع المهمة
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * العلاقة مع المستخدم الذي أضاف المرفق
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * الحصول على رابط الملف
     *
     * @return string
     */
    public function getFileUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    /**
     * الحصول على حجم الملف بتنسيق مقروء
     *
     * @return string
     */
    public function getHumanFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    /**
     * دالة للحذف تقوم أيضاً بإزالة الملف نفسه
     */
    public function deleteWithFile(): bool
    {
        if (Storage::exists($this->file_path)) {
            Storage::delete($this->file_path);
        }
        
        return $this->delete();
    }
}
