<?php

namespace App\Filament\Admin\Pages;

use App\Models\Teacher;
use App\Models\TeacherEvaluation;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class TeacherEvaluationPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-star';
    protected static ?string $title = 'تقييم المعلمين';
    protected static ?string $navigationLabel = 'تقييم المعلمين';
    protected static ?string $navigationGroup = 'التعليمية';
    protected static ?int $navigationSort = 26;

    protected static string $view = 'filament.admin.pages.teacher-evaluation-page';

    public ?array $data = [];
    public ?Teacher $selectedTeacher = null;
    public ?TeacherEvaluation $existingEvaluation = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    /**
     * تمرير البيانات للـ view
     */
    protected function getViewData(): array
    {
        // إحصائيات عامة
        $totalEvaluations = TeacherEvaluation::count();
        $averageScore = TeacherEvaluation::where('status', 'معتمد')->avg('total_score') ?? 0;
        $monthlyEvaluations = TeacherEvaluation::whereMonth('evaluation_date', now()->month)
                                            ->whereYear('evaluation_date', now()->year)
                                            ->count();
        $pendingApproval = TeacherEvaluation::where('status', 'مراجعة')->count();

        // أفضل المعلمين
        $topTeachers = Teacher::whereHas('evaluations', function ($query) {
                    $query->where('status', 'معتمد');
                })
                ->withAvg(['evaluations as average_evaluation' => function ($query) {
                    $query->where('status', 'معتمد');
                }], 'total_score')
                ->withCount(['evaluations as completed_evaluations_count' => function ($query) {
                    $query->whereIn('status', ['مكتمل', 'معتمد']);
                }])
                ->orderBy('average_evaluation', 'desc')
                ->limit(5)
                ->get();

        // آخر التقييمات
        $recentEvaluations = TeacherEvaluation::with(['teacher', 'evaluator'])
                                            ->orderBy('created_at', 'desc')
                                            ->limit(5)
                                            ->get();

        // توزيع التقييمات حسب الدرجات
        $distributionData = [
            'ممتاز (90-100%)' => TeacherEvaluation::where('total_score', '>=', 90)->count(),
            'جيد جداً (80-89%)' => TeacherEvaluation::whereBetween('total_score', [80, 89])->count(),
            'جيد (70-79%)' => TeacherEvaluation::whereBetween('total_score', [70, 79])->count(),
            'مقبول (60-69%)' => TeacherEvaluation::whereBetween('total_score', [60, 69])->count(),
            'ضعيف (أقل من 60%)' => TeacherEvaluation::where('total_score', '<', 60)->count(),
        ];

        // الاتجاهات الشهرية للتقييمات
        $monthlyTrends = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthName = $date->locale('ar')->format('F Y');
            $monthlyTrends[$monthName] = TeacherEvaluation::whereMonth('evaluation_date', $date->month)
                                                       ->whereYear('evaluation_date', $date->year)
                                                       ->where('status', 'معتمد')
                                                       ->avg('total_score') ?? 0;
        }

        // متوسط المعايير
        $criteriaAverages = [
            'performance' => TeacherEvaluation::where('status', 'معتمد')->avg('performance_score') ?? 0,
            'attendance' => TeacherEvaluation::where('status', 'معتمد')->avg('attendance_score') ?? 0,
            'interaction' => TeacherEvaluation::where('status', 'معتمد')->avg('student_interaction_score') ?? 0,
            'behavior' => TeacherEvaluation::where('status', 'معتمد')->avg('behavior_cooperation_score') ?? 0,
            'memorization' => TeacherEvaluation::where('status', 'معتمد')->avg('memorization_recitation_score') ?? 0,
            'general' => TeacherEvaluation::where('status', 'معتمد')->avg('general_evaluation_score') ?? 0,
        ];

        return [
            'selectedTeacher' => $this->selectedTeacher,
            'existingEvaluation' => $this->existingEvaluation,
            'totalEvaluations' => $totalEvaluations,
            'averageScore' => round($averageScore, 1),
            'monthlyEvaluations' => $monthlyEvaluations,
            'pendingApproval' => $pendingApproval,
            'topTeachers' => $topTeachers,
            'recentEvaluations' => $recentEvaluations,
            'distributionData' => $distributionData,
            'monthlyTrends' => $monthlyTrends,
            'criteriaAverages' => $criteriaAverages,
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('اختيار المعلم')
                    ->description('اختر المعلم المراد تقييمه')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Forms\Components\Select::make('teacher_id')
                            ->label('المعلم')
                            ->options(Teacher::all()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                if ($state) {
                                    $this->selectedTeacher = Teacher::find($state);
                                    $this->loadExistingEvaluation();
                                }
                            })
                            ->getOptionLabelFromRecordUsing(fn (Teacher $record) => "{$record->name} - {$record->mosque?->name}"),

                        Forms\Components\DatePicker::make('evaluation_date')
                            ->label('تاريخ التقييم')
                            ->required()
                            ->default(now())
                            ->maxDate(now()),

                        Forms\Components\Select::make('evaluation_period')
                            ->label('فترة التقييم')
                            ->options([
                                'شهري' => 'تقييم شهري',
                                'فصلي' => 'تقييم فصلي',
                                'نصف سنوي' => 'تقييم نصف سنوي',
                                'سنوي' => 'تقييم سنوي',
                                'تقييم خاص' => 'تقييم خاص',
                            ])
                            ->required()
                            ->default('شهري'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('معايير التقييم التفصيلية')
                    ->description('قم بتقييم المعلم وفقاً للمعايير التالية (كل معيار من 20 نقطة)')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('performance_score')
                                    ->label('تقييم الأداء (0-20)')
                                    ->helperText('جودة التدريس والالتزام بالمنهج')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(20)
                                    ->step(0.5)
                                    ->suffix('/ 20')
                                    ->default(0)
                                    ->live()
                                    ->afterStateUpdated(fn () => $this->updateTotalScore()),

                                Forms\Components\TextInput::make('attendance_score')
                                    ->label('تقييم الالتزام بالحضور (0-20)')
                                    ->helperText('انتظام الحضور والالتزام بالمواعيد')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(20)
                                    ->step(0.5)
                                    ->suffix('/ 20')
                                    ->default(0)
                                    ->live()
                                    ->afterStateUpdated(fn () => $this->updateTotalScore()),

                                Forms\Components\TextInput::make('student_interaction_score')
                                    ->label('تقييم التفاعل مع الطلاب (0-20)')
                                    ->helperText('التواصل مع الطلاب وحل مشاكلهم')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(20)
                                    ->step(0.5)
                                    ->suffix('/ 20')
                                    ->default(0)
                                    ->live()
                                    ->afterStateUpdated(fn () => $this->updateTotalScore()),

                                Forms\Components\TextInput::make('behavior_cooperation_score')
                                    ->label('تقييم السمت والتعاون (0-20)')
                                    ->helperText('الأخلاق والتعامل مع الزملاء')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(20)
                                    ->step(0.5)
                                    ->suffix('/ 20')
                                    ->default(0)
                                    ->live()
                                    ->afterStateUpdated(fn () => $this->updateTotalScore()),

                                Forms\Components\TextInput::make('memorization_recitation_score')
                                    ->label('تقييم الحفظ والتلاوة (0-20)')
                                    ->helperText('إتقان القرآن وجودة التلاوة')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(20)
                                    ->step(0.5)
                                    ->suffix('/ 20')
                                    ->default(0)
                                    ->live()
                                    ->afterStateUpdated(fn () => $this->updateTotalScore()),

                                Forms\Components\TextInput::make('general_evaluation_score')
                                    ->label('التقييم العام (0-20)')
                                    ->helperText('التقييم الشامل للأداء العام')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(20)
                                    ->step(0.5)
                                    ->suffix('/ 20')
                                    ->default(0)
                                    ->live()
                                    ->afterStateUpdated(fn () => $this->updateTotalScore()),
                            ]),

                        Forms\Components\TextInput::make('total_score')
                            ->label('النتيجة الإجمالية')
                            ->suffix('/ 100')
                            ->disabled()
                            ->default(0)
                            ->extraAttributes(['class' => 'font-bold text-lg'])
                            ->helperText('يتم حسابها تلقائياً'),
                    ]),

                Forms\Components\Section::make('ملاحظات وتفاصيل إضافية')
                    ->description('أضف ملاحظاتك حول أداء المعلم')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات التقييم')
                            ->rows(4)
                            ->placeholder('اكتب ملاحظاتك حول أداء المعلم، نقاط القوة، المجالات التي تحتاج تحسين...')
                            ->columnSpanFull(),

                        Forms\Components\Select::make('evaluator_role')
                            ->label('صفة المقيم')
                            ->options([
                                'مدير' => 'مدير',
                                'مشرف' => 'مشرف',
                                'مشرف تربوي' => 'مشرف تربوي',
                                'معلم أول' => 'معلم أول',
                                'أخرى' => 'أخرى',
                            ])
                            ->default('مشرف')
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('حالة التقييم')
                            ->options([
                                'مسودة' => 'مسودة (يمكن تعديلها لاحقاً)',
                                'مكتمل' => 'مكتمل (جاهز للمراجعة)',
                                'معتمد' => 'معتمد (نهائي)',
                            ])
                            ->default('مسودة')
                            ->required()
                            ->helperText('اختر حالة التقييم حسب مرحلة الإنجاز'),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function updateTotalScore(): void
    {
        $total = ($this->data['performance_score'] ?? 0) +
                 ($this->data['attendance_score'] ?? 0) +
                 ($this->data['student_interaction_score'] ?? 0) +
                 ($this->data['behavior_cooperation_score'] ?? 0) +
                 ($this->data['memorization_recitation_score'] ?? 0) +
                 ($this->data['general_evaluation_score'] ?? 0);

        $this->data['total_score'] = $total;
    }

    public function loadExistingEvaluation(): void
    {
        if ($this->selectedTeacher) {
            $this->existingEvaluation = $this->selectedTeacher->latestEvaluation();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save_draft')
                ->label('حفظ كمسودة')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->action('saveDraft'),

            Action::make('save_complete')
                ->label('حفظ مكتمل')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->action('saveComplete'),

            Action::make('save_approved')
                ->label('حفظ واعتماد')
                ->icon('heroicon-o-shield-check')
                ->color('primary')
                ->action('saveApproved')
                ->requiresConfirmation()
                ->modalHeading('اعتماد التقييم')
                ->modalDescription('هل أنت متأكد من اعتماد هذا التقييم؟ لن يمكن تعديله بعد الاعتماد.'),

            Action::make('clear_form')
                ->label('مسح النموذج')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->action('clearForm')
                ->requiresConfirmation(),
        ];
    }

    public function saveDraft(): void
    {
        $this->saveEvaluation('مسودة');
    }

    public function saveComplete(): void
    {
        $this->saveEvaluation('مكتمل');
    }

    public function saveApproved(): void
    {
        $this->saveEvaluation('معتمد');
    }

    private function saveEvaluation(string $status): void
    {
        $this->validate();

        $data = $this->form->getState();
        $data['evaluator_id'] = Auth::id();
        $data['status'] = $status;
        
        // حساب النتيجة الإجمالية
        $data['total_score'] = ($data['performance_score'] ?? 0) +
                               ($data['attendance_score'] ?? 0) +
                               ($data['student_interaction_score'] ?? 0) +
                               ($data['behavior_cooperation_score'] ?? 0) +
                               ($data['memorization_recitation_score'] ?? 0) +
                               ($data['general_evaluation_score'] ?? 0);

        $evaluation = TeacherEvaluation::create($data);

        Notification::make()
            ->title('تم حفظ التقييم بنجاح')
            ->body("تم حفظ تقييم المعلم {$this->selectedTeacher->name} بحالة: {$status}")
            ->success()
            ->send();

        $this->clearForm();
    }

    public function clearForm(): void
    {
        $this->data = [];
        $this->selectedTeacher = null;
        $this->existingEvaluation = null;
        $this->form->fill();
    }
}
