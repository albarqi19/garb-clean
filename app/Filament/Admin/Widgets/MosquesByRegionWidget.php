<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Mosque;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MosquesByRegionWidget extends ChartWidget
{
    protected static ?string $heading = 'توزيع المساجد حسب المنطقة';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $maxHeight = '300px';
    
    protected static bool $isLazy = false;
    
    protected static string $view = 'filament.admin.widgets.mosques-by-region-widget';

    public static function canView(): bool
    {
        return request()->routeIs('filament.admin.pages.mosques-and-circles-dashboard');
    }

    protected function getData(): array
    {
        $data = [
            'المنطقة الشرقية' => 0,
            'المنطقة الغربية' => 0,
            'المنطقة الشمالية' => 0,
            'المنطقة الجنوبية' => 0,
            'المنطقة الوسطى' => 0,
        ];
        
        try {
            if (Schema::hasTable('mosques') && Schema::hasColumn('mosques', 'region')) {
                $regions = Mosque::query()
                    ->select('region', DB::raw('count(*) as count'))
                    ->groupBy('region')
                    ->pluck('count', 'region')
                    ->toArray();
                
                foreach ($regions as $region => $count) {
                    if (!empty($region)) {
                        $data[$region] = $count;
                    }
                }
            }
        } catch (\Exception $e) {
            // تجاهل الأخطاء وعرض البيانات الافتراضية
        }
        
        return [
            'datasets' => [
                [
                    'data' => array_values($data),
                    'backgroundColor' => [
                        '#3b82f6',  // أزرق
                        '#10b981',  // أخضر
                        '#f59e0b',  // برتقالي
                        '#ef4444',  // أحمر
                        '#6366f1',  // أرجواني
                    ],
                ],
            ],
            'labels' => array_keys($data),
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}