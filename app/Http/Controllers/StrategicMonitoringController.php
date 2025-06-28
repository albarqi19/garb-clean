<?php

namespace App\Http\Controllers;

use App\Models\StrategicIndicator;
use App\Models\StrategicMonitoring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StrategicMonitoringController extends Controller
{
    /**
     * عرض قائمة عمليات الرصد.
     */
    public function index()
    {
        // الحصول على المؤشر من المعلمات (إذا وجد)
        $indicatorId = request('indicator_id');
        $year = request('year', date('Y'));
        
        if ($indicatorId) {
            $indicator = StrategicIndicator::findOrFail($indicatorId);
            $monitorings = StrategicMonitoring::where('strategic_indicator_id', $indicatorId)
                ->where('year', $year)
                ->orderBy('period')
                ->get();
                
            return view('strategic.monitorings.indicator', compact('indicator', 'monitorings', 'year'));
        }
        
        // إذا لم يتم تحديد مؤشر، عرض جميع عمليات الرصد للسنة المحددة مصنفة حسب الربع
        $monitorings = StrategicMonitoring::with('strategicIndicator.strategicPlan')
            ->where('year', $year)
            ->orderBy('period')
            ->paginate(20);
            
        $years = StrategicMonitoring::select('year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');
            
        return view('strategic.monitorings.index', compact('monitorings', 'year', 'years'));
    }

    /**
     * عرض نموذج إنشاء عملية رصد جديدة.
     */
    public function create()
    {
        // يتم إنشاء عملية الرصد من خلال صفحة المؤشر، لذا نحتاج إلى معرفة المؤشر
        $indicatorId = request('indicator_id');
        $period = request('period');
        $year = request('year', date('Y'));
        
        if (!$indicatorId || !$period) {
            return redirect()->route('strategic.indicators.index')
                ->with('error', 'يجب تحديد المؤشر والفترة لإنشاء عملية رصد');
        }
        
        $indicator = StrategicIndicator::findOrFail($indicatorId);
        
        // التحقق من عدم وجود عملية رصد سابقة لنفس المؤشر والفترة والسنة
        $existingMonitoring = StrategicMonitoring::where('strategic_indicator_id', $indicatorId)
            ->where('period', $period)
            ->where('year', $year)
            ->first();
            
        if ($existingMonitoring) {
            return redirect()->route('strategic.monitorings.edit', $existingMonitoring)
                ->with('warning', 'توجد عملية رصد سابقة لهذا المؤشر في هذه الفترة. يمكنك تعديلها.');
        }
        
        // تحويل رمز الفترة إلى اسم عربي لعرضه
        $periodNames = [
            'first_quarter' => 'الربع الأول',
            'second_quarter' => 'الربع الثاني',
            'third_quarter' => 'الربع الثالث',
            'fourth_quarter' => 'الربع الرابع',
        ];
        
        $periodName = $periodNames[$period] ?? $period;
        
        return view('strategic.monitorings.create', compact('indicator', 'period', 'periodName', 'year'));
    }

    /**
     * تخزين عملية رصد جديدة.
     */
    public function store(Request $request)
    {
        $request->validate([
            'strategic_indicator_id' => 'required|exists:strategic_indicators,id',
            'period' => 'required|in:first_quarter,second_quarter,third_quarter,fourth_quarter',
            'year' => 'required|integer|min:2020|max:2100',
            'achieved_value' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);
        
        // التحقق من عدم وجود عملية رصد سابقة لنفس المؤشر والفترة والسنة
        $exists = StrategicMonitoring::where('strategic_indicator_id', $request->strategic_indicator_id)
            ->where('period', $request->period)
            ->where('year', $request->year)
            ->exists();
            
        if ($exists) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['period' => 'توجد عملية رصد سابقة لهذا المؤشر في هذه الفترة']);
        }
        
        // الحصول على المؤشر
        $indicator = StrategicIndicator::find($request->strategic_indicator_id);
        
        if (!$indicator) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['strategic_indicator_id' => 'المؤشر المحدد غير موجود']);
        }
        
        try {
            DB::beginTransaction();
            
            $monitoring = new StrategicMonitoring();
            $monitoring->strategic_indicator_id = $request->strategic_indicator_id;
            $monitoring->period = $request->period;
            $monitoring->year = $request->year;
            $monitoring->achieved_value = $request->achieved_value;
            $monitoring->notes = $request->notes;
            $monitoring->created_by = Auth::id();
            
            // حساب نسبة الإنجاز
            if ($indicator->isPercentageResult()) {
                // إذا كان المؤشر من نوع النسبة المئوية، نستخدم القيمة المتحققة كنسبة إنجاز
                $monitoring->achievement_percentage = $request->achieved_value;
            } else {
                // حساب نسبة الإنجاز
                if ($indicator->target_value > 0) {
                    $achievedValue = $request->achieved_value;
                    
                    // إذا كان المؤشر تراكميًا، نضيف قيم الفترات السابقة
                    if ($indicator->isCumulative()) {
                        $periods = ['first_quarter', 'second_quarter', 'third_quarter', 'fourth_quarter'];
                        $currentPeriodIndex = array_search($request->period, $periods);
                        
                        if ($currentPeriodIndex > 0) {
                            $previousPeriods = array_slice($periods, 0, $currentPeriodIndex);
                            
                            $previousMonitorings = StrategicMonitoring::where('strategic_indicator_id', $request->strategic_indicator_id)
                                ->where('year', $request->year)
                                ->whereIn('period', $previousPeriods)
                                ->get();
                                
                            foreach ($previousMonitorings as $prevMonitoring) {
                                $achievedValue += $prevMonitoring->achieved_value;
                            }
                        }
                    }
                    
                    $monitoring->achievement_percentage = ($achievedValue / $indicator->target_value) * 100;
                }
            }
            
            $monitoring->save();
            
            DB::commit();
            
            return redirect()->route('strategic.indicators.show', ['strategicIndicator' => $indicator->id, 'year' => $request->year])
                ->with('success', 'تم تسجيل عملية الرصد بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء تسجيل عملية الرصد: ' . $e->getMessage());
        }
    }

    /**
     * عرض عملية رصد محددة.
     */
    public function show(StrategicMonitoring $strategicMonitoring)
    {
        // تحميل العلاقات
        $strategicMonitoring->load(['strategicIndicator.strategicPlan', 'initiatives', 'creator']);
        
        return view('strategic.monitorings.show', compact('strategicMonitoring'));
    }

    /**
     * عرض نموذج تعديل عملية رصد.
     */
    public function edit(StrategicMonitoring $strategicMonitoring)
    {
        // تحميل العلاقات
        $strategicMonitoring->load('strategicIndicator');
        
        // تحويل رمز الفترة إلى اسم عربي لعرضه
        $periodNames = [
            'first_quarter' => 'الربع الأول',
            'second_quarter' => 'الربع الثاني',
            'third_quarter' => 'الربع الثالث',
            'fourth_quarter' => 'الربع الرابع',
        ];
        
        $periodName = $periodNames[$strategicMonitoring->period] ?? $strategicMonitoring->period;
        
        return view('strategic.monitorings.edit', compact('strategicMonitoring', 'periodName'));
    }

    /**
     * تحديث عملية رصد محددة.
     */
    public function update(Request $request, StrategicMonitoring $strategicMonitoring)
    {
        $request->validate([
            'achieved_value' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);
        
        try {
            DB::beginTransaction();
            
            $indicator = $strategicMonitoring->strategicIndicator;
            
            $strategicMonitoring->achieved_value = $request->achieved_value;
            $strategicMonitoring->notes = $request->notes;
            
            // إعادة حساب نسبة الإنجاز
            if ($indicator->isPercentageResult()) {
                // إذا كان المؤشر من نوع النسبة المئوية، نستخدم القيمة المتحققة كنسبة إنجاز
                $strategicMonitoring->achievement_percentage = $request->achieved_value;
            } else {
                // حساب نسبة الإنجاز
                if ($indicator->target_value > 0) {
                    $achievedValue = $request->achieved_value;
                    
                    // إذا كان المؤشر تراكميًا، نضيف قيم الفترات السابقة
                    if ($indicator->isCumulative()) {
                        $periods = ['first_quarter', 'second_quarter', 'third_quarter', 'fourth_quarter'];
                        $currentPeriodIndex = array_search($strategicMonitoring->period, $periods);
                        
                        if ($currentPeriodIndex > 0) {
                            $previousPeriods = array_slice($periods, 0, $currentPeriodIndex);
                            
                            $previousMonitorings = StrategicMonitoring::where('strategic_indicator_id', $indicator->id)
                                ->where('year', $strategicMonitoring->year)
                                ->whereIn('period', $previousPeriods)
                                ->get();
                                
                            foreach ($previousMonitorings as $prevMonitoring) {
                                $achievedValue += $prevMonitoring->achieved_value;
                            }
                        }
                    }
                    
                    $strategicMonitoring->achievement_percentage = ($achievedValue / $indicator->target_value) * 100;
                }
            }
            
            $strategicMonitoring->save();
            
            DB::commit();
            
            return redirect()->route('strategic.indicators.show', ['strategicIndicator' => $indicator->id, 'year' => $strategicMonitoring->year])
                ->with('success', 'تم تحديث عملية الرصد بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء تحديث عملية الرصد: ' . $e->getMessage());
        }
    }

    /**
     * حذف عملية رصد محددة.
     */
    public function destroy(StrategicMonitoring $strategicMonitoring)
    {
        try {
            $indicatorId = $strategicMonitoring->strategic_indicator_id;
            $year = $strategicMonitoring->year;
            
            // حذف المبادرات المرتبطة أولاً
            $strategicMonitoring->initiatives()->delete();
            
            // ثم حذف عملية الرصد
            $strategicMonitoring->delete();
            
            return redirect()->route('strategic.indicators.show', ['strategicIndicator' => $indicatorId, 'year' => $year])
                ->with('success', 'تم حذف عملية الرصد بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء حذف عملية الرصد: ' . $e->getMessage());
        }
    }
}
