<?php

namespace App\Http\Controllers;

use App\Models\StrategicInitiative;
use App\Models\StrategicMonitoring;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StrategicInitiativeController extends Controller
{
    /**
     * عرض قائمة المبادرات الاستراتيجية.
     */
    public function index()
    {
        // الحصول على معلمات التصفية
        $monitoringId = request('monitoring_id');
        $status = request('status');
        
        $query = StrategicInitiative::with(['strategicMonitoring.strategicIndicator', 'responsible']);
        
        if ($monitoringId) {
            $query->where('strategic_monitoring_id', $monitoringId);
        }
        
        if ($status) {
            $query->where('status', $status);
        }
        
        $initiatives = $query->orderByDesc('created_at')->paginate(15);
        
        // الحصول على قوائم التصفية
        $statuses = [
            'planned' => 'مخطط',
            'in_progress' => 'قيد التنفيذ',
            'completed' => 'مكتملة',
            'delayed' => 'متأخرة',
            'cancelled' => 'ملغاة',
        ];
        
        return view('strategic.initiatives.index', compact('initiatives', 'statuses', 'status'));
    }

    /**
     * عرض نموذج إنشاء مبادرة استراتيجية جديدة.
     */
    public function create()
    {
        // يجب تحديد عملية الرصد التي سترتبط بها المبادرة
        $monitoringId = request('monitoring_id');
        
        if (!$monitoringId) {
            return redirect()->route('strategic.monitorings.index')
                ->with('error', 'يجب تحديد عملية الرصد لإنشاء مبادرة');
        }
        
        $monitoring = StrategicMonitoring::with('strategicIndicator')->findOrFail($monitoringId);
        
        // الحصول على قائمة المستخدمين
        $users = User::orderBy('name')->get();
        
        return view('strategic.initiatives.create', compact('monitoring', 'users'));
    }

    /**
     * تخزين مبادرة استراتيجية جديدة.
     */
    public function store(Request $request)
    {
        $request->validate([
            'strategic_monitoring_id' => 'required|exists:strategic_monitorings,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:planned,in_progress,completed,delayed,cancelled',
            'responsible_id' => 'nullable|exists:users,id',
            'progress_percentage' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
        ]);
        
        $initiative = new StrategicInitiative();
        $initiative->strategic_monitoring_id = $request->strategic_monitoring_id;
        $initiative->name = $request->name;
        $initiative->description = $request->description;
        $initiative->start_date = $request->start_date;
        $initiative->end_date = $request->end_date;
        $initiative->status = $request->status;
        $initiative->responsible_id = $request->responsible_id;
        $initiative->progress_percentage = $request->progress_percentage ?? 0;
        $initiative->notes = $request->notes;
        $initiative->created_by = Auth::id();
        $initiative->save();
        
        return redirect()->route('strategic.initiatives.show', $initiative)
            ->with('success', 'تم إنشاء المبادرة الاستراتيجية بنجاح');
    }

    /**
     * عرض مبادرة استراتيجية محددة.
     */
    public function show(StrategicInitiative $strategicInitiative)
    {
        // تحميل العلاقات
        $strategicInitiative->load(['strategicMonitoring.strategicIndicator', 'responsible', 'creator']);
        
        return view('strategic.initiatives.show', compact('strategicInitiative'));
    }

    /**
     * عرض نموذج تعديل مبادرة استراتيجية.
     */
    public function edit(StrategicInitiative $strategicInitiative)
    {
        // تحميل العلاقات
        $strategicInitiative->load(['strategicMonitoring.strategicIndicator', 'responsible']);
        
        // الحصول على قائمة المستخدمين
        $users = User::orderBy('name')->get();
        
        return view('strategic.initiatives.edit', compact('strategicInitiative', 'users'));
    }

    /**
     * تحديث مبادرة استراتيجية محددة.
     */
    public function update(Request $request, StrategicInitiative $strategicInitiative)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:planned,in_progress,completed,delayed,cancelled',
            'responsible_id' => 'nullable|exists:users,id',
            'progress_percentage' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
        ]);
        
        $strategicInitiative->name = $request->name;
        $strategicInitiative->description = $request->description;
        $strategicInitiative->start_date = $request->start_date;
        $strategicInitiative->end_date = $request->end_date;
        $strategicInitiative->status = $request->status;
        $strategicInitiative->responsible_id = $request->responsible_id;
        $strategicInitiative->progress_percentage = $request->progress_percentage ?? $strategicInitiative->progress_percentage;
        $strategicInitiative->notes = $request->notes;
        $strategicInitiative->save();
        
        return redirect()->route('strategic.initiatives.show', $strategicInitiative)
            ->with('success', 'تم تحديث المبادرة الاستراتيجية بنجاح');
    }

    /**
     * حذف مبادرة استراتيجية محددة.
     */
    public function destroy(StrategicInitiative $strategicInitiative)
    {
        $monitoringId = $strategicInitiative->strategic_monitoring_id;
        
        $strategicInitiative->delete();
        
        return redirect()->route('strategic.monitorings.show', $monitoringId)
            ->with('success', 'تم حذف المبادرة الاستراتيجية بنجاح');
    }

    /**
     * تحديث حالة المبادرة.
     */
    public function updateStatus(Request $request, StrategicInitiative $strategicInitiative)
    {
        $request->validate([
            'status' => 'required|in:planned,in_progress,completed,delayed,cancelled',
            'progress_percentage' => 'required|numeric|min:0|max:100',
        ]);
        
        $strategicInitiative->status = $request->status;
        $strategicInitiative->progress_percentage = $request->progress_percentage;
        $strategicInitiative->save();
        
        return redirect()->back()
            ->with('success', 'تم تحديث حالة المبادرة بنجاح');
    }
}
