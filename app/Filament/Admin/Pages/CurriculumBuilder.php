<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Actions;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Response;

class CurriculumBuilder extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static ?string $navigationIcon = 'heroicon-o-table-cells';
    
    protected static ?string $navigationGroup = 'إدارة المناهج';
    
    protected static ?string $title = 'بناء المناهج الثلاثية';
    
    protected static ?string $navigationLabel = 'بناء المناهج';
    
    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.admin.pages.curriculum-builder';
    
    public ?array $data = [];
    
    public function mount(): void
    {
        $this->form->fill([
            'curriculum_rows' => [
                [
                    'memorization_enabled' => false,
                    'memorization_from_surah' => '',
                    'memorization_from_verse' => null,
                    'memorization_to_surah' => '',
                    'memorization_to_verse' => null,
                    'minor_review_enabled' => false,
                    'minor_review_from_surah' => '',
                    'minor_review_from_verse' => null,
                    'minor_review_to_surah' => '',
                    'minor_review_to_verse' => null,
                    'major_review_enabled' => false,
                    'major_review_from_surah' => '',
                    'major_review_from_verse' => null,
                    'major_review_to_surah' => '',
                    'major_review_to_verse' => null,
                ]
            ]
        ]);
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('إدخال المنهج الثلاثي')
                    ->description('يمكنك إدخال المنهج اليومي للطلاب بتحديد الحفظ والمراجعة الصغرى والكبرى')
                    ->schema([
                        Repeater::make('curriculum_rows')
                            ->label('صفوف المنهج')
                            ->schema([
                                // قسم الحفظ
                                Section::make('الحفظ')
                                    ->schema([
                                        Checkbox::make('memorization_enabled')
                                            ->label('تفعيل الحفظ')
                                            ->reactive(),
                                        Select::make('memorization_from_surah')
                                            ->label('من السورة')
                                            ->options($this->getSurahOptions())
                                            ->searchable()
                                            ->visible(fn (callable $get) => $get('memorization_enabled')),
                                        TextInput::make('memorization_from_verse')
                                            ->label('من آية')
                                            ->numeric()
                                            ->minValue(1)
                                            ->visible(fn (callable $get) => $get('memorization_enabled')),
                                        Select::make('memorization_to_surah')
                                            ->label('إلى السورة')
                                            ->options($this->getSurahOptions())
                                            ->searchable()
                                            ->visible(fn (callable $get) => $get('memorization_enabled')),
                                        TextInput::make('memorization_to_verse')
                                            ->label('إلى آية')
                                            ->numeric()
                                            ->minValue(1)
                                            ->visible(fn (callable $get) => $get('memorization_enabled')),
                                    ])
                                    ->columns(5)
                                    ->columnSpan(1),
                                
                                // قسم المراجعة الصغرى
                                Section::make('المراجعة الصغرى')
                                    ->schema([
                                        Checkbox::make('minor_review_enabled')
                                            ->label('تفعيل المراجعة الصغرى')
                                            ->reactive(),
                                        Select::make('minor_review_from_surah')
                                            ->label('من السورة')
                                            ->options($this->getSurahOptions())
                                            ->searchable()
                                            ->visible(fn (callable $get) => $get('minor_review_enabled')),
                                        TextInput::make('minor_review_from_verse')
                                            ->label('من آية')
                                            ->numeric()
                                            ->minValue(1)
                                            ->visible(fn (callable $get) => $get('minor_review_enabled')),
                                        Select::make('minor_review_to_surah')
                                            ->label('إلى السورة')
                                            ->options($this->getSurahOptions())
                                            ->searchable()
                                            ->visible(fn (callable $get) => $get('minor_review_enabled')),
                                        TextInput::make('minor_review_to_verse')
                                            ->label('إلى آية')
                                            ->numeric()
                                            ->minValue(1)
                                            ->visible(fn (callable $get) => $get('minor_review_enabled')),
                                    ])
                                    ->columns(5)
                                    ->columnSpan(1),
                                
                                // قسم المراجعة الكبرى
                                Section::make('المراجعة الكبرى')
                                    ->schema([
                                        Checkbox::make('major_review_enabled')
                                            ->label('تفعيل المراجعة الكبرى')
                                            ->reactive(),
                                        Select::make('major_review_from_surah')
                                            ->label('من السورة')
                                            ->options($this->getSurahOptions())
                                            ->searchable()
                                            ->visible(fn (callable $get) => $get('major_review_enabled')),
                                        TextInput::make('major_review_from_verse')
                                            ->label('من آية')
                                            ->numeric()
                                            ->minValue(1)
                                            ->visible(fn (callable $get) => $get('major_review_enabled')),
                                        Select::make('major_review_to_surah')
                                            ->label('إلى السورة')
                                            ->options($this->getSurahOptions())
                                            ->searchable()
                                            ->visible(fn (callable $get) => $get('major_review_enabled')),
                                        TextInput::make('major_review_to_verse')
                                            ->label('إلى آية')
                                            ->numeric()
                                            ->minValue(1)
                                            ->visible(fn (callable $get) => $get('major_review_enabled')),
                                    ])
                                    ->columns(5)
                                    ->columnSpan(1),
                            ])
                            ->columns(3)
                            ->addActionLabel('إضافة يوم جديد')
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(function (array $state): ?string {
                                $index = array_search($state, $this->data['curriculum_rows'] ?? []);
                                return 'يوم ' . (is_numeric($index) ? $index + 1 : 1);
                            })
                            ->defaultItems(1)
                            ->minItems(1)
                            ->maxItems(30),
                    ]),
                
                Actions::make([
                    Action::make('save')
                        ->label('حفظ المنهج')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action('saveCurriculum'),
                    Action::make('export')
                        ->label('تصدير إلى Excel')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->action('exportToExcel'),
                    Action::make('import')
                        ->label('استيراد من Excel')
                        ->icon('heroicon-o-arrow-up-tray')
                        ->color('warning')
                        ->action('importFromExcel'),
                    Action::make('clear')
                        ->label('مسح الكل')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action('clearAll'),
                    Action::make('addWeek')
                        ->label('إضافة أسبوع كامل (7 أيام)')
                        ->icon('heroicon-o-calendar-days')
                        ->color('info')
                        ->action('addWeek'),
                    Action::make('addMonth')
                        ->label('إضافة شهر كامل (30 يوم)')
                        ->icon('heroicon-o-calendar')
                        ->color('secondary')
                        ->action('addMonth'),
                ])
            ])
            ->statePath('data');
    }
    
    protected function getSurahOptions(): array
    {
        return [
            'الفاتحة' => 'الفاتحة',
            'البقرة' => 'البقرة',
            'آل عمران' => 'آل عمران',
            'النساء' => 'النساء',
            'المائدة' => 'المائدة',
            'الأنعام' => 'الأنعام',
            'الأعراف' => 'الأعراف',
            'الأنفال' => 'الأنفال',
            'التوبة' => 'التوبة',
            'يونس' => 'يونس',
            'هود' => 'هود',
            'يوسف' => 'يوسف',
            'الرعد' => 'الرعد',
            'إبراهيم' => 'إبراهيم',
            'الحجر' => 'الحجر',
            'النحل' => 'النحل',
            'الإسراء' => 'الإسراء',
            'الكهف' => 'الكهف',
            'مريم' => 'مريم',
            'طه' => 'طه',
            'الأنبياء' => 'الأنبياء',
            'الحج' => 'الحج',
            'المؤمنون' => 'المؤمنون',
            'النور' => 'النور',
            'الفرقان' => 'الفرقان',
            'الشعراء' => 'الشعراء',
            'النمل' => 'النمل',
            'القصص' => 'القصص',
            'العنكبوت' => 'العنكبوت',
            'الروم' => 'الروم',
            'لقمان' => 'لقمان',
            'السجدة' => 'السجدة',
            'الأحزاب' => 'الأحزاب',
            'سبأ' => 'سبأ',
            'فاطر' => 'فاطر',
            'يس' => 'يس',
            'الصافات' => 'الصافات',
            'ص' => 'ص',
            'الزمر' => 'الزمر',
            'غافر' => 'غافر',
            'فصلت' => 'فصلت',
            'الشورى' => 'الشورى',
            'الزخرف' => 'الزخرف',
            'الدخان' => 'الدخان',
            'الجاثية' => 'الجاثية',
            'الأحقاف' => 'الأحقاف',
            'محمد' => 'محمد',
            'الفتح' => 'الفتح',
            'الحجرات' => 'الحجرات',
            'ق' => 'ق',
            'الذاريات' => 'الذاريات',
            'الطور' => 'الطور',
            'النجم' => 'النجم',
            'القمر' => 'القمر',
            'الرحمن' => 'الرحمن',
            'الواقعة' => 'الواقعة',
            'الحديد' => 'الحديد',
            'المجادلة' => 'المجادلة',
            'الحشر' => 'الحشر',
            'الممتحنة' => 'الممتحنة',
            'الصف' => 'الصف',
            'الجمعة' => 'الجمعة',
            'المنافقون' => 'المنافقون',
            'التغابن' => 'التغابن',
            'الطلاق' => 'الطلاق',
            'التحريم' => 'التحريم',
            'الملك' => 'الملك',
            'القلم' => 'القلم',
            'الحاقة' => 'الحاقة',
            'المعارج' => 'المعارج',
            'نوح' => 'نوح',
            'الجن' => 'الجن',
            'المزمل' => 'المزمل',
            'المدثر' => 'المدثر',
            'القيامة' => 'القيامة',
            'الإنسان' => 'الإنسان',
            'المرسلات' => 'المرسلات',
            'النبأ' => 'النبأ',
            'النازعات' => 'النازعات',
            'عبس' => 'عبس',
            'التكوير' => 'التكوير',
            'الانفطار' => 'الانفطار',
            'المطففين' => 'المطففين',
            'الانشقاق' => 'الانشقاق',
            'البروج' => 'البروج',
            'الطارق' => 'الطارق',
            'الأعلى' => 'الأعلى',
            'الغاشية' => 'الغاشية',
            'الفجر' => 'الفجر',
            'البلد' => 'البلد',
            'الشمس' => 'الشمس',
            'الليل' => 'الليل',
            'الضحى' => 'الضحى',
            'الشرح' => 'الشرح',
            'التين' => 'التين',
            'العلق' => 'العلق',
            'القدر' => 'القدر',
            'البينة' => 'البينة',
            'الزلزلة' => 'الزلزلة',
            'العاديات' => 'العاديات',
            'القارعة' => 'القارعة',
            'التكاثر' => 'التكاثر',
            'العصر' => 'العصر',
            'الهمزة' => 'الهمزة',
            'الفيل' => 'الفيل',
            'قريش' => 'قريش',
            'الماعون' => 'الماعون',
            'الكوثر' => 'الكوثر',
            'الكافرون' => 'الكافرون',
            'النصر' => 'النصر',
            'المسد' => 'المسد',
            'الإخلاص' => 'الإخلاص',
            'الفلق' => 'الفلق',
            'الناس' => 'الناس',
        ];
    }
    
    public function saveCurriculum(): void
    {
        $data = $this->form->getState();
        $curriculumData = $data['curriculum_rows'] ?? [];
        
        try {
            DB::beginTransaction();
            
            // إنشاء خطة منهج جديدة
            $curriculumPlan = \App\Models\CurriculumPlan::create([
                'name' => 'منهج ثلاثي - ' . now()->format('Y-m-d H:i'),
                'description' => 'منهج ثلاثي تم إنشاؤه باستخدام منشئ المناهج',
                'type' => 'ثلاثي',
                'total_days' => count($curriculumData),
                'created_by' => Auth::id(),
                'is_active' => true,
            ]);
            
            // حفظ تفاصيل كل يوم
            foreach ($curriculumData as $index => $dayData) {
                \App\Models\CurriculumPlanDay::create([
                    'curriculum_plan_id' => $curriculumPlan->id,
                    'day_number' => $index + 1,
                    
                    // بيانات الحفظ
                    'memorization_enabled' => $dayData['memorization_enabled'] ?? false,
                    'memorization_from_surah' => $dayData['memorization_from_surah'] ?? null,
                    'memorization_from_verse' => $dayData['memorization_from_verse'] ?? null,
                    'memorization_to_surah' => $dayData['memorization_to_surah'] ?? null,
                    'memorization_to_verse' => $dayData['memorization_to_verse'] ?? null,
                    
                    // بيانات المراجعة الصغرى
                    'minor_review_enabled' => $dayData['minor_review_enabled'] ?? false,
                    'minor_review_from_surah' => $dayData['minor_review_from_surah'] ?? null,
                    'minor_review_from_verse' => $dayData['minor_review_from_verse'] ?? null,
                    'minor_review_to_surah' => $dayData['minor_review_to_surah'] ?? null,
                    'minor_review_to_verse' => $dayData['minor_review_to_verse'] ?? null,
                    
                    // بيانات المراجعة الكبرى
                    'major_review_enabled' => $dayData['major_review_enabled'] ?? false,
                    'major_review_from_surah' => $dayData['major_review_from_surah'] ?? null,
                    'major_review_from_verse' => $dayData['major_review_from_verse'] ?? null,
                    'major_review_to_surah' => $dayData['major_review_to_surah'] ?? null,
                    'major_review_to_verse' => $dayData['major_review_to_verse'] ?? null,
                ]);
            }
            
            DB::commit();
            
            Notification::make()
                ->title('تم حفظ المنهج بنجاح')
                ->body("تم إنشاء خطة منهج جديدة بـ " . count($curriculumData) . " أيام (ID: {$curriculumPlan->id})")
                ->success()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('view')
                        ->label('عرض الخطة')
                        ->url(route('filament.admin.resources.curriculum-plans.view', $curriculumPlan))
                        ->openUrlInNewTab()
                ])
                ->send();
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            Notification::make()
                ->title('خطأ في حفظ المنهج')
                ->body('حدث خطأ أثناء حفظ المنهج: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function exportToExcel(): void
    {
        $data = $this->form->getState();
        $curriculumData = $data['curriculum_rows'] ?? [];
        
        // إنشاء بيانات للتصدير
        $exportData = [];
        $exportData[] = [
            'اليوم',
            'الحفظ - من السورة',
            'الحفظ - من آية',
            'الحفظ - إلى السورة',
            'الحفظ - إلى آية',
            'المراجعة الصغرى - من السورة',
            'المراجعة الصغرى - من آية',
            'المراجعة الصغرى - إلى السورة',
            'المراجعة الصغرى - إلى آية',
            'المراجعة الكبرى - من السورة',
            'المراجعة الكبرى - من آية',
            'المراجعة الكبرى - إلى السورة',
            'المراجعة الكبرى - إلى آية'
        ];
        
        foreach ($curriculumData as $index => $dayData) {
            $row = [
                'اليوم ' . ($index + 1),
                ($dayData['memorization_enabled'] ?? false) ? ($dayData['memorization_from_surah'] ?? '') : '',
                ($dayData['memorization_enabled'] ?? false) ? ($dayData['memorization_from_verse'] ?? '') : '',
                ($dayData['memorization_enabled'] ?? false) ? ($dayData['memorization_to_surah'] ?? '') : '',
                ($dayData['memorization_enabled'] ?? false) ? ($dayData['memorization_to_verse'] ?? '') : '',
                ($dayData['minor_review_enabled'] ?? false) ? ($dayData['minor_review_from_surah'] ?? '') : '',
                ($dayData['minor_review_enabled'] ?? false) ? ($dayData['minor_review_from_verse'] ?? '') : '',
                ($dayData['minor_review_enabled'] ?? false) ? ($dayData['minor_review_to_surah'] ?? '') : '',
                ($dayData['minor_review_enabled'] ?? false) ? ($dayData['minor_review_to_verse'] ?? '') : '',
                ($dayData['major_review_enabled'] ?? false) ? ($dayData['major_review_from_surah'] ?? '') : '',
                ($dayData['major_review_enabled'] ?? false) ? ($dayData['major_review_from_verse'] ?? '') : '',
                ($dayData['major_review_enabled'] ?? false) ? ($dayData['major_review_to_verse'] ?? '') : '',
            ];
            $exportData[] = $row;
        }
        
        // حفظ البيانات في ملف CSV
        $filename = 'منهج_ثلاثي_' . now()->format('Y_m_d_H_i_s') . '.csv';
        $csvContent = '';
        
        foreach ($exportData as $row) {
            $csvContent .= implode(',', array_map(function($field) {
                return '"' . str_replace('"', '""', $field) . '"';
            }, $row)) . "\n";
        }
        
        // إضافة BOM للدعم العربي
        $csvContent = "\xEF\xBB\xBF" . $csvContent;
        
        Storage::disk('local')->put('exports/' . $filename, $csvContent);
        
        Notification::make()
            ->title('تم تصدير المنهج بنجاح')
            ->body('تم حفظ الملف: ' . $filename)
            ->success()
            ->actions([
                \Filament\Notifications\Actions\Action::make('download')
                    ->label('تحميل الملف')
                    ->url(Storage::url('exports/' . $filename))
                    ->openUrlInNewTab()
            ])
            ->send();
    }
    
    public function importFromExcel(): void
    {
        // في الوقت الحالي، نعرض رسالة توضيحية
        // يمكن تطوير هذه الوظيفة لاحقاً لتدعم رفع الملفات
        
        Notification::make()
            ->title('استيراد من Excel')
            ->body('لاستيراد البيانات من Excel، تأكد من أن الملف يحتوي على الأعمدة التالية:
                   اليوم، الحفظ-من السورة، الحفظ-من آية، الحفظ-إلى السورة، الحفظ-إلى آية، 
                   المراجعة الصغرى-من السورة، المراجعة الصغرى-من آية، المراجعة الصغرى-إلى السورة، المراجعة الصغرى-إلى آية،
                   المراجعة الكبرى-من السورة، المراجعة الكبرى-من آية، المراجعة الكبرى-إلى السورة، المراجعة الكبرى-إلى آية')
            ->warning()
            ->persistent()
            ->send();
    }
    
    public function clearAll(): void
    {
        $this->form->fill([
            'curriculum_rows' => [
                [
                    'memorization_enabled' => false,
                    'memorization_from_surah' => '',
                    'memorization_from_verse' => null,
                    'memorization_to_surah' => '',
                    'memorization_to_verse' => null,
                    'minor_review_enabled' => false,
                    'minor_review_from_surah' => '',
                    'minor_review_from_verse' => null,
                    'minor_review_to_surah' => '',
                    'minor_review_to_verse' => null,
                    'major_review_enabled' => false,
                    'major_review_from_surah' => '',
                    'major_review_from_verse' => null,
                    'major_review_to_surah' => '',
                    'major_review_to_verse' => null,
                ]
            ]
        ]);
        
        Notification::make()
            ->title('تم مسح جميع البيانات')
            ->success()
            ->send();
    }
    
    public function addWeek(): void
    {
        $currentData = $this->form->getState();
        $currentRows = $currentData['curriculum_rows'] ?? [];
        
        // إضافة 7 أيام جديدة
        for ($i = 0; $i < 7; $i++) {
            $currentRows[] = [
                'memorization_enabled' => false,
                'memorization_from_surah' => '',
                'memorization_from_verse' => null,
                'memorization_to_surah' => '',
                'memorization_to_verse' => null,
                'minor_review_enabled' => false,
                'minor_review_from_surah' => '',
                'minor_review_from_verse' => null,
                'minor_review_to_surah' => '',
                'minor_review_to_verse' => null,
                'major_review_enabled' => false,
                'major_review_from_surah' => '',
                'major_review_from_verse' => null,
                'major_review_to_surah' => '',
                'major_review_to_verse' => null,
            ];
        }
        
        $this->form->fill(['curriculum_rows' => $currentRows]);
        
        Notification::make()
            ->title('تم إضافة أسبوع كامل')
            ->body('تم إضافة 7 أيام جديدة للمنهج')
            ->success()
            ->send();
    }
    
    public function addMonth(): void
    {
        $currentData = $this->form->getState();
        $currentRows = $currentData['curriculum_rows'] ?? [];
        
        // إضافة 30 يوم جديد
        for ($i = 0; $i < 30; $i++) {
            $currentRows[] = [
                'memorization_enabled' => false,
                'memorization_from_surah' => '',
                'memorization_from_verse' => null,
                'memorization_to_surah' => '',
                'memorization_to_verse' => null,
                'minor_review_enabled' => false,
                'minor_review_from_surah' => '',
                'minor_review_from_verse' => null,
                'minor_review_to_surah' => '',
                'minor_review_to_verse' => null,
                'major_review_enabled' => false,
                'major_review_from_surah' => '',
                'major_review_from_verse' => null,
                'major_review_to_surah' => '',
                'major_review_to_verse' => null,
            ];
        }
        
        $this->form->fill(['curriculum_rows' => $currentRows]);
        
        Notification::make()
            ->title('تم إضافة شهر كامل')
            ->body('تم إضافة 30 يوم جديد للمنهج')
            ->success()
            ->send();
    }
}
