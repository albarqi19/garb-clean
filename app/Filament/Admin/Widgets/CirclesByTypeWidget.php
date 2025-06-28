<?php

namespace App\Filament\Admin\Widgets;

use App\Models\QuranCircle;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CirclesByTypeWidget extends ChartWidget
{
    protected static ?string $heading = 'توزيع الحلقات حسب النوع والفترة';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $maxHeight = '300px';
    
    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return request()->routeIs('filament.admin.pages.mosques-and-circles-dashboard');
    }

    protected function getData(): array
    {
        $periods = [
            'صباحية' => [],
            'مسائية' => [],
            'عصرية' => [],
        ];
        
        $types = [
            'بنين' => 'rgba(59, 130, 246, 0.8)', // أزرق
            'بنات' => 'rgba(236, 72, 153, 0.8)', // وردي
            'رجال' => 'rgba(16, 185, 129, 0.8)', // أخضر
            'نساء' => 'rgba(245, 158, 11, 0.8)', // برتقالي
            'أطفال' => 'rgba(99, 102, 241, 0.8)', // بنفسجي
        ];
        
        $datasets = [];
        $labels = array_keys($periods);
        
        try {
            if (Schema::hasTable('quran_circles')) {
                if (Schema::hasColumn('quran_circles', 'type') && Schema::hasColumn('quran_circles', 'period')) {
                    $circleStats = QuranCircle::query()
                        ->select('type', 'period', DB::raw('count(*) as count'))
                        ->groupBy('type', 'period')
                        ->get();
                    
                    foreach ($types as $type => $color) {
                        $data = array_fill_keys($labels, 0);
                        
                        foreach ($circleStats as $stat) {
                            if ($stat->type === $type && isset($data[$stat->period])) {
                                $data[$stat->period] = $stat->count;
                            }
                        }
                        
                        $datasets[] = [
                            'label' => $type,
                            'data' => array_values($data),
                            'backgroundColor' => $color,
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            // تجاهل الأخطاء وعرض البيانات الافتراضية
        }
        
        // إذا لم تكن هناك بيانات، إنشاء بيانات افتراضية للعرض
        if (empty($datasets)) {
            foreach ($types as $type => $color) {
                $datasets[] = [
                    'label' => $type,
                    'data' => [0, 0, 0],
                    'backgroundColor' => $color,
                ];
            }
        }
        
        return [
            'datasets' => $datasets,
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}