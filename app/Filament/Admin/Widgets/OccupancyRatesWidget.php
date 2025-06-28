<?php

namespace App\Filament\Admin\Widgets;

use App\Models\QuranCircle;
use App\Models\Student;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Facades\Schema;

class OccupancyRatesWidget extends ChartWidget
{
    protected static ?string $heading = 'معدلات الإشغال في الحلقات';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $maxHeight = '300px';
    
    protected static ?string $pollingInterval = null;
    
    protected static bool $isLazy = false;
    
    public static function canView(): bool
    {
        return request()->routeIs('filament.admin.pages.mosques-and-circles-dashboard');
    }

    protected function getData(): array
    {
        // بيانات افتراضية للسعة والتسجيل
        $months = [
            'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 
            'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'
        ];
        
        $datasets = [];
        $occupancyRates = [];
        
        try {
            if (Schema::hasTable('quran_circles') && Schema::hasTable('students')) {
                if (Schema::hasColumn('quran_circles', 'capacity') && Schema::hasColumn('students', 'circle_id')) {
                    // حساب معدلات الإشغال لكل شهر في العام الحالي
                    $year = Carbon::now()->year;
                    $totalCapacity = QuranCircle::sum('capacity') ?: 100; // القيمة الافتراضية إذا لم تكن هناك بيانات
                    
                    $calculatedRates = [];
                    for ($month = 1; $month <= 12; $month++) {
                        $studentsCount = Student::whereMonth('created_at', $month)
                            ->whereYear('created_at', $year)
                            ->count();
                        
                        $occupancyRate = $totalCapacity > 0 ? min(($studentsCount / $totalCapacity) * 100, 100) : 0;
                        $calculatedRates[$month] = round($occupancyRate, 1);
                    }
                    
                    $occupancyRates = array_values($calculatedRates);
                }
            }
        } catch (\Exception $e) {
            // تجاهل الأخطاء وعرض البيانات الافتراضية
        }
        
        // في حالة عدم وجود بيانات، استخدام بيانات افتراضية
        if (empty($occupancyRates)) {
            $occupancyRates = [30, 35, 40, 45, 50, 55, 60, 65, 70, 75, 80, 75];
        }
        
        $datasets[] = [
            'label' => 'نسبة الإشغال',
            'data' => $occupancyRates,
            'fill' => 'start',
            'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
            'borderColor' => 'rgba(59, 130, 246, 0.8)',
            'tension' => 0.3,
        ];
        
        return [
            'datasets' => $datasets,
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
    
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'max' => 100,
                    'min' => 0,
                    'ticks' => [
                        'callback' => '(value) => `${value}%`',
                    ],
                ],
            ],
        ];
    }
}