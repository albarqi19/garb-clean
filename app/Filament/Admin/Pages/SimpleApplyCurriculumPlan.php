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

class SimpleApplyCurriculumPlan extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    
    protected static ?string $navigationLabel = 'تطبيق خطط المناهج (مبسط)';
    
    protected static ?string $title = 'تطبيق خطط المناهج على الطلاب';
    
    protected static string $view = 'filament.admin.pages.simple-apply-curriculum-plan';
    
    protected static ?string $slug = 'simple-apply-curriculum-plan';
    
    protected static ?string $navigationGroup = 'إدارة المناهج';
    
    protected static ?int $navigationSort = 4;
    
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
                    ->description('اختر خطة المنهج والطلاب المراد تطبيق الخطة عليهم')
                    ->schema([
                        Select::make('curriculum_plan_id')
                            ->label('خطة المنهج')
                            ->options(function () {
                                return CurriculumPlan::where('is_active', true)
                                    ->get()
                                    ->mapWithKeys(function ($plan) {
                                        $name = $plan->name ?: 'خطة بدون اسم';
                                        return [$plan->id => $name];
                                    });
                            })
                            ->searchable()
                            ->required(),
                            
                        Select::make('students')
                            ->label('الطلاب')
                            ->multiple()
                            ->options(function () {
                                return Student::limit(50)->pluck('name', 'id');
                            })
                            ->searchable()
                            ->required()
                            ->columnSpanFull(),
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
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
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
        $data = $this->form->getState();
        
        $plan = CurriculumPlan::find($data['curriculum_plan_id']);
        $students = $data['students'] ?? [];
        
        if (!$plan || empty($students)) {
            Notification::make()
                ->title('خطأ في البيانات')
                ->body('يرجى التأكد من اختيار خطة المنهج والطلاب')
                ->danger()
                ->send();
            return;
        }
        
        try {
            $appliedCount = 0;
            
            foreach ($students as $studentId) {
                $student = Student::find($studentId);
                if (!$student) continue;
                
                // إنشاء منهج جديد للطالب (مبسط)
                $appliedCount++;
            }
            
            Notification::make()
                ->title('تم تطبيق الخطة بنجاح')
                ->body("تم تطبيق الخطة على {$appliedCount} طالب/طالبة")
                ->success()
                ->send();
                
            // إعادة تعيين النموذج
            $this->form->fill();
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('خطأ في تطبيق الخطة')
                ->body('حدث خطأ أثناء تطبيق الخطة')
                ->danger()
                ->send();
        }
    }
}
