<?php

namespace App\Http\Controllers;

use App\Models\StrategicPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StrategicPlanController extends Controller
{
    /**
     * عرض قائمة الخطط الاستراتيجية.
     */
    public function index()
    {
        $plans = StrategicPlan::orderBy('is_active', 'desc')
            ->orderByDesc('start_date')
            ->paginate(10);
        
        return view('strategic.plans.index', compact('plans'));
    }

    /**
     * عرض نموذج إنشاء خطة استراتيجية جديدة.
     */
    public function create()
    {
        return view('strategic.plans.create');
    }

    /**
     * تخزين خطة استراتيجية جديدة.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_active' => 'boolean',
        ]);
        
        $plan = new StrategicPlan();
        $plan->name = $request->name;
        $plan->description = $request->description;
        $plan->start_date = $request->start_date;
        $plan->end_date = $request->end_date;
        $plan->is_active = $request->has('is_active');
        $plan->created_by = Auth::id();
        $plan->save();
        
        return redirect()->route('strategic.plans.show', $plan)
            ->with('success', 'تم إنشاء الخطة الاستراتيجية بنجاح');
    }

    /**
     * عرض خطة استراتيجية محددة مع مؤشراتها.
     */
    public function show(StrategicPlan $strategicPlan)
    {
        // تحميل المؤشرات المرتبطة بالخطة
        $strategicPlan->load('indicators');
        
        // الحصول على السنة الحالية أو المحددة في الطلب
        $year = request('year', date('Y'));
        $quarter = request('quarter', null);
        
        // حساب نسبة الإنجاز للخطة
        $achievementPercentage = $strategicPlan->calculateAchievementPercentage($year, $quarter);
        
        return view('strategic.plans.show', compact('strategicPlan', 'year', 'quarter', 'achievementPercentage'));
    }

    /**
     * عرض نموذج تعديل خطة استراتيجية.
     */
    public function edit(StrategicPlan $strategicPlan)
    {
        return view('strategic.plans.edit', compact('strategicPlan'));
    }

    /**
     * تحديث خطة استراتيجية محددة.
     */
    public function update(Request $request, StrategicPlan $strategicPlan)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_active' => 'boolean',
        ]);
        
        // إذا كانت الخطة الحالية نشطة وتم إلغاء تنشيطها
        if ($strategicPlan->is_active && !$request->has('is_active')) {
            // تأكد من وجود خطة أخرى نشطة قبل إلغاء تنشيط الخطة الحالية
            $otherActivePlans = StrategicPlan::where('id', '!=', $strategicPlan->id)
                ->where('is_active', true)
                ->exists();
                
            if (!$otherActivePlans) {
                throw ValidationException::withMessages([
                    'is_active' => ['يجب أن تكون هناك خطة استراتيجية نشطة واحدة على الأقل'],
                ]);
            }
        }
        
        $strategicPlan->name = $request->name;
        $strategicPlan->description = $request->description;
        $strategicPlan->start_date = $request->start_date;
        $strategicPlan->end_date = $request->end_date;
        $strategicPlan->is_active = $request->has('is_active');
        $strategicPlan->save();
        
        return redirect()->route('strategic.plans.show', $strategicPlan)
            ->with('success', 'تم تحديث الخطة الاستراتيجية بنجاح');
    }

    /**
     * حذف خطة استراتيجية محددة.
     */
    public function destroy(StrategicPlan $strategicPlan)
    {
        // لا يمكن حذف الخطة إذا كانت نشطة
        if ($strategicPlan->is_active) {
            return redirect()->route('strategic.plans.index')
                ->with('error', 'لا يمكن حذف خطة استراتيجية نشطة');
        }
        
        try {
            DB::beginTransaction();
            
            // حذف المؤشرات المرتبطة بالخطة (سيقوم بحذف عمليات الرصد والمبادرات تلقائيًا)
            $strategicPlan->indicators()->delete();
            
            // حذف الخطة
            $strategicPlan->delete();
            
            DB::commit();
            
            return redirect()->route('strategic.plans.index')
                ->with('success', 'تم حذف الخطة الاستراتيجية بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->route('strategic.plans.index')
                ->with('error', 'حدث خطأ أثناء حذف الخطة الاستراتيجية: ' . $e->getMessage());
        }
    }

    /**
     * تبديل حالة نشاط الخطة الاستراتيجية.
     */
    public function toggleActive(StrategicPlan $strategicPlan)
    {
        // إذا كانت الخطة نشطة وتم طلب إلغاء تنشيطها
        if ($strategicPlan->is_active) {
            // تأكد من وجود خطة أخرى نشطة قبل إلغاء تنشيط الخطة الحالية
            $otherActivePlans = StrategicPlan::where('id', '!=', $strategicPlan->id)
                ->where('is_active', true)
                ->exists();
                
            if (!$otherActivePlans) {
                return redirect()->route('strategic.plans.index')
                    ->with('error', 'يجب أن تكون هناك خطة استراتيجية نشطة واحدة على الأقل');
            }
        }
        
        $strategicPlan->is_active = !$strategicPlan->is_active;
        $strategicPlan->save();
        
        $status = $strategicPlan->is_active ? 'تنشيط' : 'إلغاء تنشيط';
        
        return redirect()->route('strategic.plans.index')
            ->with('success', "تم {$status} الخطة الاستراتيجية بنجاح");
    }

    /**
     * عرض لوحة معلومات الخطة الاستراتيجية.
     */
    public function dashboard()
    {
        // الحصول على الخطة النشطة الحالية
        $activePlan = StrategicPlan::where('is_active', true)
            ->first();
            
        if (!$activePlan) {
            return redirect()->route('strategic.plans.index')
                ->with('error', 'لا توجد خطة استراتيجية نشطة');
        }
        
        // الحصول على السنة الحالية أو المحددة في الطلب
        $year = request('year', date('Y'));
        $quarter = request('quarter', null);
        
        // تحميل المؤشرات المرتبطة بالخطة
        $activePlan->load(['indicators.monitorings' => function ($query) use ($year, $quarter) {
            $query->where('year', $year);
            if ($quarter) {
                $query->where('period', $quarter);
            }
        }]);
        
        // حساب نسبة الإنجاز للخطة
        $achievementPercentage = $activePlan->calculateAchievementPercentage($year, $quarter);
        
        // بيانات لوحة المعلومات
        $dashboard = [
            'plan' => $activePlan,
            'year' => $year,
            'quarter' => $quarter,
            'achievementPercentage' => $achievementPercentage,
        ];
        
        return view('strategic.dashboard', $dashboard);
    }
}
