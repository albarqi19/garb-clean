<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'identity_number',
        'job_title',
        'cost_center',
        'association_employee_number',
        'afternoon_shift',
        'maghrib_shift',
        'isha_shift',
        'appointment_decision_link',
        'amendment_form_link',
        'circle_permit_link',
        'branch_notes',
        'hr_notes',
        'data_entry_notes',
        'hire_date',
        'phone',
        'email',
        'salary', // إضافة عمود الراتب للحقول القابلة للملء
    ];

    /**
     * الخصائص التي يجب تحويلها
     *
     * @var array<string, string>
     */
    protected $casts = [
        'afternoon_shift' => 'boolean',
        'maghrib_shift' => 'boolean',
        'isha_shift' => 'boolean',
        'hire_date' => 'date',
        'salary' => 'decimal:2', // إضافة تحويل الراتب إلى رقم عشري
    ];

    /**
     * الحصول على فترات عمل الموظف كنص
     *
     * @return string
     */
    public function getWorkShiftsAttribute(): string
    {
        $shifts = [];
        
        if ($this->afternoon_shift) {
            $shifts[] = 'العصر';
        }
        
        if ($this->maghrib_shift) {
            $shifts[] = 'المغرب';
        }
        
        if ($this->isha_shift) {
            $shifts[] = 'العشاء';
        }
        
        return empty($shifts) ? 'غير محدد' : implode(' و ', $shifts);
    }

    /**
     * التحقق مما إذا كان الموظف مديرًا
     *
     * @return bool
     */
    public function getIsManagerAttribute(): bool
    {
        return str_contains(strtolower($this->job_title), 'مدير');
    }

    /**
     * التحقق مما إذا كان الموظف مراقبًا
     *
     * @return bool
     */
    public function getIsMonitorAttribute(): bool
    {
        return str_contains(strtolower($this->job_title), 'مراقب');
    }
}
