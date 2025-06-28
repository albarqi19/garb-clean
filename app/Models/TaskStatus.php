<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskStatus extends Model
{
    use HasFactory;

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'task_id',
        'user_id',
        'from_status',
        'to_status',
        'comment',
        'completion_percentage',
    ];

    /**
     * العلاقة مع المهمة
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * العلاقة مع المستخدم الذي غيّر الحالة
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * التحقق مما إذا كان التغيير يعني إكمال المهمة
     *
     * @return bool
     */
    public function isCompletion(): bool
    {
        return $this->to_status === 'مكتملة';
    }

    /**
     * التحقق مما إذا كانت حالة المهمة قد تغيرت للأسوأ
     *
     * @return bool
     */
    public function isRegression(): bool
    {
        $statusOrder = [
            'جديدة' => 1,
            'قيد التنفيذ' => 2,
            'مكتملة' => 3,
            'متأخرة' => 0,
            'ملغاة' => -1,
        ];
        
        return $statusOrder[$this->to_status] < $statusOrder[$this->from_status];
    }
}
