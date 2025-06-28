<?php

namespace App\Http\Controllers;

use App\Models\StrategicIndicator;
use App\Models\StrategicPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StrategicIndicatorController extends Controller
{
    /**
     * عرض قائمة المؤشرات الاستراتيجية.
     */
    public function index()
    {
        // الحصول على الخطة النشطة افتراضيًا
        $activePlan = StrategicPlan::where('is_active', true)->first();
        
        // الحصول على الخطة المطلوبة من المعلمات
        $planId = request('plan_id', $activePlan ? $activePlan->id : null);
        
        if (!$planId) {
            return redirect()->route('strategic.plans.index')
                ->with('error', 'الرجاء تحديد خطة استراتيجية');
        }
        
        $plan = StrategicPlan::findOrFail($planId);
        
        $indicators = StrategicIndicator::where('strategic_plan_id', $planId)
            ->orderBy('code')
            ->paginate(15);
            
        // الحصول على قائمة الخطط للتصفية
        $plans = StrategicPlan::orderByDesc('start_date')->get();
        
        return view('strategic.indicators.index', compact('indicators', 'plans', 'plan'));
    }

    /**
     * عرض نموذج إنشاء مؤشر استراتيجي جديد.
     */
    public function create()
    {
        // الحصول على الخطة من المعلمات
        $planId = request('plan_id');
        $plan = null;
        
        if ($planId) {
            $plan = StrategicPlan::findOrFail($planId);
        } else {
            // إذا لم يتم تحديد خطة، استخدام الخطة النشطة
            $plan = StrategicPlan::where('is_active', true)->first();
        }
        
        if (!$plan) {
            return redirect()->route('strategic.plans.index')
                ->with('error', 'الرجاء تحديد خطة استراتيجية');
        }
        
        // الحصول على قائمة الخطط للاختيار منها
        $plans = StrategicPlan::orderByDesc('start_date')->get();
        
        return view('strategic.indicators.create', compact('plan', 'plans'));
    }

    /**
     * تخزين مؤشر استراتيجي جديد.
     */
    public function store(Request $request)
    {
        $request->validate([
            'strategic_plan_id' => 'required|exists:strategic_plans,id',
            'code' => 'required|string|max:20',
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'reference_number' => 'nullable|string|max:50',
            'target_value' => 'required|numeric|min:0',
            'result_type' => 'required|in:number,percentage',
            'monitoring_type' => 'required|in:cumulative,non_cumulative',
            'unit' => 'nullable|string|max:50',
            'responsible_department' => 'nullable|string|max:100',
        ]);
        
        // التحقق من عدم وجود مؤشر بنفس الرمز للخطة المحددة
        $exists = StrategicIndicator::where('strategic_plan_id', $request->strategic_plan_id)
            ->where('code', $request->code)
            ->exists();
            
        if ($exists) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['code' => 'يوجد مؤشر آخر بنفس الرمز لهذه الخطة']);
        }
        
        $indicator = new StrategicIndicator();
        $indicator->strategic_plan_id = $request->strategic_plan_id;
        $indicator->code = $request->code;
        $indicator->name = $request->name;
        $indicator->description = $request->description;
        $indicator->reference_number = $request->reference_number;
        $indicator->target_value = $request->target_value;
        $indicator->result_type = $request->result_type;
        $indicator->monitoring_type = $request->monitoring_type;
        $indicator->unit = $request->unit;
        $indicator->responsible_department = $request->responsible_department;
        $indicator->created_by = Auth::id();
        $indicator->save();
        
        return redirect()->route('strategic.indicators.show', $indicator)
            ->with('success', 'تم إنشاء المؤشر الاستراتيجي بنجاح');
    }

    /**
     * عرض مؤشر استراتيجي محدد مع عمليات الرصد.
     */
    public function show(StrategicIndicator $strategicIndicator)
    {
        // تحميل العلاقات
        $strategicIndicator->load('strategicPlan');
        
        // الحصول على السنة المحددة أو الافتراضية
        $year = request('year', date('Y'));
        
        // الحصول على عمليات الرصد للسنة المحددة
        $monitorings = $strategicIndicator->monitorings()
            ->where('year', $year)
            ->orderBy('period')
            ->get();
            
        // تنظيم عمليات الرصد حسب الفترة
        $monitoringsByPeriod = [
            'first_quarter' => $monitorings->where('period', 'first_quarter')->first(),
            'second_quarter' => $monitorings->where('period', 'second_quarter')->first(),
            'third_quarter' => $monitorings->where('period', 'third_quarter')->first(),
            'fourth_quarter' => $monitorings->where('period', 'fourth_quarter')->first(),
        ];
        
        return view('strategic.indicators.show', compact('strategicIndicator', 'year', 'monitoringsByPeriod'));
    }

    /**
     * عرض نموذج تعديل مؤشر استراتيجي.
     */
    public function edit(StrategicIndicator $strategicIndicator)
    {
        // تحميل العلاقات
        $strategicIndicator->load('strategicPlan');
        
        // الحصول على قائمة الخطط الاستراتيجية
        $plans = StrategicPlan::orderByDesc('start_date')->get();
        
        return view('strategic.indicators.edit', compact('strategicIndicator', 'plans'));
    }

    /**
     * تحديث مؤشر استراتيجي محدد.
     */
    public function update(Request $request, StrategicIndicator $strategicIndicator)
    {
        $request->validate([
            'strategic_plan_id' => 'required|exists:strategic_plans,id',
            'code' => 'required|string|max:20',
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'reference_number' => 'nullable|string|max:50',
            'target_value' => 'required|numeric|min:0',
            'result_type' => 'required|in:number,percentage',
            'monitoring_type' => 'required|in:cumulative,non_cumulative',
            'unit' => 'nullable|string|max:50',
            'responsible_department' => 'nullable|string|max:100',
        ]);
        
        // التحقق من عدم وجود مؤشر آخر بنفس الرمز للخطة المحددة
        $exists = StrategicIndicator::where('strategic_plan_id', $request->strategic_plan_id)
            ->where('code', $request->code)
            ->where('id', '!=', $strategicIndicator->id)
            ->exists();
            
        if ($exists) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['code' => 'يوجد مؤشر آخر بنفس الرمز لهذه الخطة']);
        }
        
        $strategicIndicator->strategic_plan_id = $request->strategic_plan_id;
        $strategicIndicator->code = $request->code;
        $strategicIndicator->name = $request->name;
        $strategicIndicator->description = $request->description;
        $strategicIndicator->reference_number = $request->reference_number;
        $strategicIndicator->target_value = $request->target_value;
        $strategicIndicator->result_type = $request->result_type;
        $strategicIndicator->monitoring_type = $request->monitoring_type;
        $strategicIndicator->unit = $request->unit;
        $strategicIndicator->responsible_department = $request->responsible_department;
        $strategicIndicator->save();
        
        return redirect()->route('strategic.indicators.show', $strategicIndicator)
            ->with('success', 'تم تحديث المؤشر الاستراتيجي بنجاح');
    }

    /**
     * حذف مؤشر استراتيجي محدد.
     */
    public function destroy(StrategicIndicator $strategicIndicator)
    {
        try {
            DB::beginTransaction();
            
            // حذف عمليات الرصد والمبادرات المرتبطة
            $monitoringIds = $strategicIndicator->monitorings()->pluck('id');
            
            // حذف المؤشر الاستراتيجي
            $strategicIndicator->delete();
            
            DB::commit();
            
            return redirect()->route('strategic.indicators.index', ['plan_id' => $strategicIndicator->strategic_plan_id])
                ->with('success', 'تم حذف المؤشر الاستراتيجي بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء حذف المؤشر الاستراتيجي: ' . $e->getMessage());
        }
    }

    /**
     * عرض تقرير مؤشر استراتيجي.
     */
    public function report(StrategicIndicator $strategicIndicator)
    {
        // الحصول على السنوات التي تم فيها رصد المؤشر
        $years = $strategicIndicator->monitorings()
            ->select('year')
            ->distinct()
            ->orderBy('year')
            ->pluck('year')
            ->toArray();
            
        // إذا لم يتم تحديد سنة، استخدام السنة الحالية أو آخر سنة متاحة
        $selectedYear = request('year', date('Y'));
        if (!in_array($selectedYear, $years) && !empty($years)) {
            $selectedYear = end($years);
        }
        
        // الحصول على بيانات الرصد للسنة المحددة
        $monitorings = $strategicIndicator->monitorings()
            ->where('year', $selectedYear)
            ->orderBy('period')
            ->get();
            
        // تحضير البيانات للرسم البياني
        $chartLabels = [
            'الربع الأول',
            'الربع الثاني',
            'الربع الثالث',
            'الربع الرابع'
        ];
        
        $chartData = [
            'achieved' => [],   // القيم المتحققة
            'target' => [],     // القيم المستهدفة
            'percentage' => [], // نسب التحقق
        ];
        
        $periods = ['first_quarter', 'second_quarter', 'third_quarter', 'fourth_quarter'];
        foreach ($periods as $period) {
            $monitoring = $monitorings->firstWhere('period', $period);
            
            if ($monitoring) {
                $chartData['achieved'][] = $monitoring->achieved_value;
                $chartData['percentage'][] = $monitoring->achievement_percentage;
            } else {
                $chartData['achieved'][] = null;
                $chartData['percentage'][] = null;
            }
            
            // القيمة المستهدفة ثابتة لجميع الفترات
            $chartData['target'][] = $strategicIndicator->target_value;
        }
        
        return view('strategic.indicators.report', compact(
            'strategicIndicator', 
            'years', 
            'selectedYear', 
            'monitorings', 
            'chartLabels', 
            'chartData'
        ));
    }
}
