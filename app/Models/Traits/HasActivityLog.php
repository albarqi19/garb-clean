<?php

namespace App\Models\Traits;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasActivityLog
{
    /**
     * تسجيل الأحداث في دورة حياة النموذج
     */
    protected static function bootHasActivityLog()
    {
        // تسجيل حدث الإنشاء
        static::created(function (Model $model) {
            if (method_exists($model, 'shouldLogActivity') && !$model->shouldLogActivity('created')) {
                return;
            }
            
            $moduleName = static::getModuleName();
            $modelName = static::getModelDisplayName();
            $description = "تم إنشاء {$modelName} جديد";
            
            if (method_exists($model, 'getActivityDescriptionForEvent')) {
                $description = $model->getActivityDescriptionForEvent('created');
            }
            
            ActivityLog::logCreated($moduleName, $description, $model);
        });

        // تسجيل حدث التعديل
        static::updated(function (Model $model) {
            if (method_exists($model, 'shouldLogActivity') && !$model->shouldLogActivity('updated')) {
                return;
            }
            
            $moduleName = static::getModuleName();
            $modelName = static::getModelDisplayName();
            $description = "تم تعديل بيانات {$modelName}";
            
            if (method_exists($model, 'getActivityDescriptionForEvent')) {
                $description = $model->getActivityDescriptionForEvent('updated');
            }
            
            // الحصول على البيانات المتغيرة فقط
            $changed = $model->getDirty();
            $old = [];
            $new = [];
            
            foreach ($changed as $key => $value) {
                if (in_array($key, $model->getActivityExcluded() ?? [])) {
                    continue;
                }
                
                $old[$key] = $model->getOriginal($key);
                $new[$key] = $value;
            }
            
            if (!empty($changed)) {
                ActivityLog::logUpdated($moduleName, $description, $model, $old, $new);
            }
        });

        // تسجيل حدث الحذف
        static::deleted(function (Model $model) {
            if (method_exists($model, 'shouldLogActivity') && !$model->shouldLogActivity('deleted')) {
                return;
            }
            
            $moduleName = static::getModuleName();
            $modelName = static::getModelDisplayName();
            $description = "تم حذف {$modelName}";
            
            if (method_exists($model, 'getActivityDescriptionForEvent')) {
                $description = $model->getActivityDescriptionForEvent('deleted');
            }
            
            ActivityLog::logDeleted($moduleName, $description, $model);
        });

        // تسجيل حدث استعادة المحذوف (إذا كان النموذج يدعم SoftDeletes)
        if (method_exists(static::class, 'restored')) {
            static::restored(function (Model $model) {
                if (method_exists($model, 'shouldLogActivity') && !$model->shouldLogActivity('restored')) {
                    return;
                }
                
                $moduleName = static::getModuleName();
                $modelName = static::getModelDisplayName();
                $description = "تمت استعادة {$modelName}";
                
                if (method_exists($model, 'getActivityDescriptionForEvent')) {
                    $description = $model->getActivityDescriptionForEvent('restored');
                }
                
                ActivityLog::logActivity('استعادة', $moduleName, $description, $model);
            });
        }
    }

    /**
     * الحقول المستبعدة من تسجيل الأنشطة
     *
     * @return array
     */
    public function getActivityExcluded(): array
    {
        return $this->activityExcluded ?? ['updated_at', 'created_at', 'deleted_at'];
    }

    /**
     * الحصول على اسم الوحدة للنموذج
     *
     * @return string
     */
    public static function getModuleName(): string
    {
        if (property_exists(static::class, 'moduleName')) {
            return static::$moduleName;
        }
        
        return Str::plural(class_basename(static::class));
    }

    /**
     * الحصول على اسم العرض للنموذج
     *
     * @return string
     */
    public static function getModelDisplayName(): string
    {
        if (property_exists(static::class, 'displayName')) {
            return static::$displayName;
        }
        
        return class_basename(static::class);
    }

    /**
     * تسجيل نشاط للنموذج الحالي
     *
     * @param string $activityType نوع النشاط
     * @param string $description وصف النشاط
     * @param array|null $oldValues البيانات القديمة
     * @param array|null $newValues البيانات الجديدة
     * @return ActivityLog
     */
    public function logModelActivity(string $activityType, string $description, ?array $oldValues = null, ?array $newValues = null): ActivityLog
    {
        $moduleName = static::getModuleName();
        return ActivityLog::logActivity($activityType, $moduleName, $description, $this, $oldValues, $newValues);
    }
}