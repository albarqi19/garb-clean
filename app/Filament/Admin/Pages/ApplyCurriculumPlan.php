<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Models\CurriculumPlan;
use App\Models\Student;
use App\Models\StudentCurriculum;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ApplyCurriculumPlan extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    
    protected static ?string $navigationLabel = 'تطبيق خطط المناهج';
    
    protected static ?string $title = 'تطبيق خطط المناهج على الطلاب';
    
    protected static string $view = 'filament.admin.pages.apply-curriculum-plan';
    
    protected static ?string $slug = 'apply-curriculum-plan';
    
    protected static ?string $navigationGroup = 'إدارة المناهج';
    
    protected static ?int $navigationSort = 3;
    
    public ?array $data = [];
    
    public function mount(): void
    {
        $this->form->fill();
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('اختيار الخطة والطلاب')
                    ->description('اختر خطة المنهج والطلاب المراد تطبيقها عليهم')
                    ->schema([
                        Select::make('curriculum_plan_id')
                            ->label('خطة المنهج')
                            ->options(function () {
                                try {
                                    return CurriculumPlan::whereNotNull('name')
                                        ->get()
                                        ->mapWithKeys(function ($plan) {
                                            $name = $plan->name ?? 'خطة بدون اسم';
                                            $type = $plan->type ?? 'غير محدد';
                                            $days = $plan->total_days ?? 0;
                                            return [$plan->id => "{$name} ({$type}) - {$days} يوم"];
                                        });
                                } catch (\Exception $e) {
                                    return [];
                                }
                            })
                            ->required()
                            ->searchable()
                            ->preload(),
                            
                        Select::make('students')
                            ->label('الطلاب')
                            ->options(function () {
                                try {
                                    return Student::where('is_active', true)
                                        ->get()
                                        ->mapWithKeys(function ($student) {
                                            $name = $student->name ?? 'طالب بدون اسم';
                                            $circle = 'بدون حلقة';
                                            try {
                                                if ($student->quranCircle) {
                                                    $circle = $student->quranCircle->name ?? 'بدون حلقة';
                                                }
                                            } catch (\Exception $e) {
                                                // تجاهل خطأ العلاقة
                                            }
                                            return [$student->id => "{$name} - {$circle}"];
                                        });
                                } catch (\Exception $e) {
                                    return [];
                                }
                            })
                            ->multiple()
                            ->required()
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),
                    
                Section::make('إعدادات التطبيق')
                    ->schema([
                        DatePicker::make('start_date')
                            ->label('تاريخ البدء')
                            ->default(now())
                            ->required(),
                            
                        Textarea::make('notes')
                            ->label('ملاحظات')
                            ->placeholder('ملاحظات إضافية حول تطبيق الخطة')
                            ->rows(3),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }
    
    protected function getFormActions(): array
    {
        return [
            Action::make('apply')
                ->label('تطبيق الخطة')
                ->color('success')
                ->icon('heroicon-o-check')
                ->action('applyPlan'),
        ];
    }
    
    public function applyPlan(): void
    {
        try {
            $data = $this->form->getState();
            
            $plan = CurriculumPlan::find($data['curriculum_plan_id'] ?? null);
            $students = $data['students'] ?? [];
            
            if (!$plan || empty($students)) {
                Notification::make()
                    ->title('خطأ في البيانات')
                    ->body('يرجى التأكد من اختيار خطة المنهج والطلاب')
                    ->danger()
                    ->send();
                return;
            }
            
            DB::beginTransaction();
            
            $appliedCount = 0;
            
            foreach ($students as $studentId) {
                $student = Student::find($studentId);
                if (!$student) continue;
                
                // التحقق من عدم وجود خطة نشطة للطالب
                $existingCurriculum = StudentCurriculum::where('student_id', $studentId)
                    ->where('status', '!=', 'completed')
                    ->first();
                    
                if ($existingCurriculum) {
                    continue; // تخطي الطالب إذا كان لديه منهج نشط
                }
                
                // إنشاء منهج جديد للطالب
                StudentCurriculum::create([
                    'student_id' => $studentId,
                    'curriculum_id' => $plan->curriculum_id ?? 1,
                    'status' => 'active',
                    'start_date' => $data['start_date'] ?? now(),
                    'notes' => $data['notes'] ?? '',
                    'created_by' => Auth::id(),
                ]);
                
                $appliedCount++;
            }
            
            DB::commit();
            
            Notification::make()
                ->title('تم تطبيق الخطة بنجاح')
                ->body("تم تطبيق الخطة على {$appliedCount} طالب/طالبة")
                ->success()
                ->send();
                
            // إعادة تعيين النموذج
            $this->form->fill();
            
        } catch (\Exception $e) {
            DB::rollback();
            
            Notification::make()
                ->title('خطأ في تطبيق الخطة')
                ->body('حدث خطأ أثناء تطبيق الخطة: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}
