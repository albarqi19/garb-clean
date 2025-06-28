<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Mosque;
use App\Models\QuranCircle;
use App\Models\Employee;
use App\Models\Teacher;
use App\Models\Student;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EducationalStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    
    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        // تهيئة المتغيرات الافتراضية
        $mosquesCount = 0;
        $circlesCount = 0;
        $teachersCount = 0;
        $studentsCount = 0;
        $circlesByPeriod = [];
        
        try {
            // حساب عدد المساجد
            if (Schema::hasTable('mosques')) {
                $mosquesCount = Mosque::count();
            }
            
            // حساب عدد الحلقات القرآنية
            if (Schema::hasTable('quran_circles')) {
                $circlesCount = QuranCircle::count();
            }
            
            // حساب عدد المعلمين
            if (Schema::hasTable('teachers')) {
                $teachersCount = Teacher::count();
            }
            
            // حساب عدد الطلاب
            if (Schema::hasTable('students')) {
                $studentsCount = Student::count();
            }

            // حساب عدد الحلقات حسب الفترة
            if (Schema::hasTable('quran_circles') && Schema::hasColumn('quran_circles', 'period')) {
                $circlesByPeriod = QuranCircle::query()
                    ->select('period', DB::raw('count(*) as count'))
                    ->groupBy('period')
                    ->pluck('count', 'period')
                    ->toArray();
            }
        } catch (\Exception $e) {
            // تجاهل الاستثناءات وعرض قيم افتراضية
            // يمكن إضافة تسجيل للخطأ هنا إذا لزم الأمر
        }

        return [
            Stat::make('المساجد', $mosquesCount)
                ->description('إجمالي عدد المساجد')
                ->descriptionIcon('heroicon-m-building-library')
                ->color('success'),

            Stat::make('الحلقات القرآنية', $circlesCount)
                ->description('إجمالي عدد الحلقات')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('primary'),

            Stat::make('المعلمين', $teachersCount)
                ->description('إجمالي عدد المعلمين')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('warning'),
                
            Stat::make('الطلاب', $studentsCount)
                ->description('إجمالي عدد الطلاب')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),
        ];
    }
}