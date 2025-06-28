<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\CirclesByTypeWidget;
use App\Filament\Admin\Widgets\CirclesPerMosqueWidget;
use App\Filament\Admin\Widgets\MosquesByRegionWidget;
use App\Filament\Admin\Widgets\OccupancyRatesWidget;
use Filament\Pages\Page;
use App\Models\Mosque;
use App\Models\QuranCircle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MosquesAndCirclesDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-library';
    
    protected static ?string $navigationGroup = 'لوحة المعلومات';
    
    protected static ?string $title = 'المساجد والحلقات';
    
    protected static ?string $navigationLabel = 'المساجد والحلقات';
    
    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.admin.pages.mosques-and-circles-dashboard';

    public function getViewData(): array
    {
        $data = [
            'mosquesCount' => 0,
            'circlesCount' => 0,
            'activeCirclesCount' => 0,
            'inactiveCirclesCount' => 0,
            'circlesByRegion' => [],
            'circlesByPeriod' => [],
            'mosquesByRegion' => [],
            'circlesPerMosque' => [],
        ];
        
        try {
            // حساب عدد المساجد
            if (Schema::hasTable('mosques')) {
                $data['mosquesCount'] = Mosque::count();
                
                // توزيع المساجد حسب المنطقة
                if (Schema::hasColumn('mosques', 'region')) {
                    $data['mosquesByRegion'] = Mosque::query()
                        ->select('region', DB::raw('count(*) as count'))
                        ->groupBy('region')
                        ->pluck('count', 'region')
                        ->toArray();
                }
            }
            
            // حساب عدد الحلقات القرآنية
            if (Schema::hasTable('quran_circles')) {
                $data['circlesCount'] = QuranCircle::count();
                
                // حساب عدد الحلقات النشطة وغير النشطة
                if (Schema::hasColumn('quran_circles', 'status')) {
                    $data['activeCirclesCount'] = QuranCircle::where('status', 'active')->count();
                    $data['inactiveCirclesCount'] = QuranCircle::where('status', '!=', 'active')->count();
                }
                
                // توزيع الحلقات حسب نوع الفترة
                if (Schema::hasColumn('quran_circles', 'period')) {
                    $data['circlesByPeriod'] = QuranCircle::query()
                        ->select('period', DB::raw('count(*) as count'))
                        ->groupBy('period')
                        ->pluck('count', 'period')
                        ->toArray();
                }
                
                // توزيع الحلقات حسب المسجد
                if (Schema::hasColumn('quran_circles', 'mosque_id')) {
                    $data['circlesPerMosque'] = QuranCircle::query()
                        ->select('mosque_id', DB::raw('count(*) as count'))
                        ->groupBy('mosque_id')
                        ->orderByDesc('count')
                        ->limit(10)
                        ->get()
                        ->toArray();
                }
            }
        } catch (\Exception $e) {
            // تجاهل الأخطاء وعرض البيانات الافتراضية
        }

        return $data;
    }

    public function getWidgets(): array
    {
        return [
            MosquesByRegionWidget::class,
            CirclesByTypeWidget::class,
            CirclesPerMosqueWidget::class,
            OccupancyRatesWidget::class,
        ];
    }
    
    public function getColumns(): int|array
    {
        return 2; // عدد الأعمدة في لوحة المعلومات
    }
    
    public static function getNavigationBadge(): ?string
    {
        try {
            // يمكن هنا إضافة شارة رقمية تعرض عدد المساجد مثلاً
            if (class_exists(Mosque::class)) {
                return Mosque::count();
            }
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}