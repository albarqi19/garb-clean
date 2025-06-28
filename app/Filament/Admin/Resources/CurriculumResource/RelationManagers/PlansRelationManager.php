<?php

namespace App\Filament\Admin\Resources\CurriculumResource\RelationManagers;

use App\Models\CurriculumLevel;
use App\Services\QuranService;
use App\Services\CurriculumTemplateService;
use App\Filament\Forms\Components\QuranContentSelector;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PlansRelationManager extends RelationManager
{
    protected static string $relationship = 'plans';
    
    protected static ?string $title = 'خطط المنهج';
    protected static ?string $label = 'خطة';
    protected static ?string $pluralLabel = 'الخطط';    public function form(Form $form): Form
    {
        $curriculumType = $this->ownerRecord->type;
        
        $levelField = [];
        
        // إضافة حقل المستوى فقط لمنهج الطالب
        if ($curriculumType === 'منهج طالب') {
            $levelField[] = Forms\Components\Select::make('curriculum_level_id')
                ->label('المستوى')
                ->options(
                    CurriculumLevel::where('curriculum_id', $this->ownerRecord->id)
                        ->orderBy('level_order')
                        ->pluck('name', 'id')
                )
                ->required();
        }
                
        return $form
            ->schema([
                Forms\Components\Section::make('بيانات الخطة الأساسية')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم الخطة')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('plan_type')
                            ->label('نوع الخطة')
                            ->options([
                                'الدرس' => 'الدرس',
                                'المراجعة الصغرى' => 'المراجعة الصغرى',
                                'المراجعة الكبرى' => 'المراجعة الكبرى',
                            ])
                            ->required(),
                        ...$levelField,
                        Forms\Components\TextInput::make('expected_days')
                            ->label('عدد الأيام المتوقعة')
                            ->numeric()
                            ->minValue(1)
                            ->nullable(),
                    ])->columns(2),

                Forms\Components\Section::make('محتوى الخطة')
                    ->schema([
                        // استخدام المكون الجديد لاختيار المحتوى القرآني
                        Forms\Components\Toggle::make('use_quran_content')
                            ->label('استخدام محتوى قرآني منظم')
                            ->helperText('فعل هذا الخيار لاستخدام محدد السور والآيات بدلاً من النص الحر')
                            ->default(false)
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                if ($state) {
                                    $set('content_type', 'quran');
                                } else {
                                    $set('content_type', 'text');
                                    // إعادة تعيين القيم القرآنية
                                    $set('surah_number', null);
                                    $set('start_verse', null);
                                    $set('end_verse', null);
                                    $set('calculated_verses', null);
                                    $set('formatted_content', null);
                                }
                            }),                        // المحتوى النصي التقليدي
                        Forms\Components\Textarea::make('content')
                            ->label('محتوى الخطة')
                            ->required()  // Always required
                            ->default('') // Add default empty string
                            ->helperText('حدد السور أو الآيات أو الأجزاء المطلوب دراستها في هذه الخطة')
                            ->rows(3)
                            ->columnSpanFull()
                            ->visible(fn (Forms\Get $get) => !$get('use_quran_content')),                        // قسم المحتوى القرآني المنظم
                        Forms\Components\Group::make([
                            // Toggle for multi-surah range
                            Forms\Components\Toggle::make('use_multi_surah_range')
                                ->label('نطاق سور متعددة')
                                ->helperText('فعل هذا الخيار لتحديد نطاق يشمل عدة سور')
                                ->default(false)
                                ->live()
                                ->afterStateUpdated(function (Forms\Set $set, $state) {
                                    if ($state) {
                                        $set('range_type', 'multi_surah');
                                        // Clear single surah fields
                                        $set('surah_number', null);
                                        $set('start_verse', null);
                                        $set('end_verse', null);
                                        $set('calculated_verses', null);
                                        $set('formatted_content', null);
                                    } else {
                                        $set('range_type', 'single_surah');
                                        // Clear multi-surah fields
                                        $set('start_surah_number', null);
                                        $set('end_surah_number', null);
                                        $set('start_surah_verse', null);
                                        $set('end_surah_verse', null);
                                        $set('total_verses_calculated', null);
                                        $set('multi_surah_formatted_content', null);
                                    }
                                })
                                ->visible(fn (Forms\Get $get) => $get('use_quran_content')),
                        ])->columnSpanFull(),

                        // Single Surah Section
                        Forms\Components\Group::make([
                            Forms\Components\Select::make('surah_number')
                                ->label('السورة')
                                ->options(QuranService::getSurahOptions())
                                ->searchable()
                                ->preload()
                                ->live()                                ->afterStateUpdated(function (Forms\Set $set, $state) {
                                    if ($state) {
                                        // إعادة تعيين آيات البداية والنهاية
                                        $set('start_verse', 1);
                                        $verseCount = QuranService::getVerseCount($state);
                                        $set('end_verse', $verseCount);
                                        $set('calculated_verses', $verseCount);
                                        
                                        // تحديث المحتوى المنسق
                                        $formatted = QuranService::formatSurahContent($state, 1, $verseCount);
                                        $set('formatted_content', $formatted);
                                        $set('content', $formatted ?: 'سورة ' . QuranService::getSurahName($state)); // Ensure content always has a value
                                    } else {
                                        $set('start_verse', null);
                                        $set('end_verse', null);
                                        $set('calculated_verses', null);
                                        $set('formatted_content', null);
                                        $set('content', ''); // Set empty string rather than null
                                    }
                                })
                                ->required(fn (Forms\Get $get) => $get('use_quran_content') && !$get('use_multi_surah_range'))
                                ->visible(fn (Forms\Get $get) => $get('use_quran_content') && !$get('use_multi_surah_range')),

                            Forms\Components\Placeholder::make('surah_info')
                                ->label('')
                                ->content(function (Forms\Get $get) {
                                    $surahNumber = $get('surah_number');
                                    if (!$surahNumber) {
                                        return 'اختر سورة لعرض المعلومات';
                                    }
                                    
                                    $surahName = QuranService::getSurahName($surahNumber);
                                    $verseCount = QuranService::getVerseCount($surahNumber);
                                    
                                    return "سورة {$surahName} - تحتوي على {$verseCount} آية";
                                })
                                ->visible(fn (Forms\Get $get) => $get('use_quran_content') && !$get('use_multi_surah_range') && $get('surah_number')),
                        ])->columnSpanFull(),

                        Forms\Components\Group::make([                            // آية البداية
                            Forms\Components\TextInput::make('start_verse')
                                ->label('من الآية')
                                ->numeric()
                                ->minValue(1)
                                ->live()
                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                    $surahNumber = $get('surah_number');
                                    $endVerse = $get('end_verse');
                                    
                                    if ($surahNumber && $state && $endVerse) {
                                        $calculated = QuranService::calculateVerseCount($surahNumber, $state, $endVerse);
                                        $set('calculated_verses', $calculated);
                                        
                                        if ($calculated > 0) {
                                            $formatted = QuranService::formatSurahContent($surahNumber, $state, $endVerse);
                                            $set('formatted_content', $formatted);
                                            $set('content', $formatted ?: 'سورة ' . QuranService::getSurahName($surahNumber) . ' من الآية ' . $state . ' إلى الآية ' . $endVerse);
                                        } else {
                                            // Provide a fallback value
                                            $set('content', 'سورة ' . QuranService::getSurahName($surahNumber) . ' من الآية ' . $state . ' إلى الآية ' . $endVerse);
                                        }
                                    }
                                })
                                ->required(fn (Forms\Get $get) => $get('use_quran_content') && !$get('use_multi_surah_range'))
                                ->visible(fn (Forms\Get $get) => $get('use_quran_content') && !$get('use_multi_surah_range')),                            // آية النهاية
                            Forms\Components\TextInput::make('end_verse')
                                ->label('إلى الآية')
                                ->numeric()
                                ->minValue(1)
                                ->live()
                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                    $surahNumber = $get('surah_number');
                                    $startVerse = $get('start_verse');
                                    
                                    if ($surahNumber && $startVerse && $state) {
                                        $calculated = QuranService::calculateVerseCount($surahNumber, $startVerse, $state);
                                        $set('calculated_verses', $calculated);
                                        
                                        if ($calculated > 0) {
                                            $formatted = QuranService::formatSurahContent($surahNumber, $startVerse, $state);
                                            $set('formatted_content', $formatted);
                                            $set('content', $formatted ?: 'سورة ' . QuranService::getSurahName($surahNumber) . ' من الآية ' . $startVerse . ' إلى الآية ' . $state);
                                        } else {
                                            // Provide a fallback value
                                            $set('content', 'سورة ' . QuranService::getSurahName($surahNumber) . ' من الآية ' . $startVerse . ' إلى الآية ' . $state);
                                        }
                                    }
                                })
                                ->required(fn (Forms\Get $get) => $get('use_quran_content') && !$get('use_multi_surah_range'))
                                ->visible(fn (Forms\Get $get) => $get('use_quran_content') && !$get('use_multi_surah_range')),                            // عدد الآيات المحسوب
                            Forms\Components\TextInput::make('calculated_verses')
                                ->label('عدد الآيات')
                                ->disabled()
                                ->dehydrated(false)
                                ->formatStateUsing(fn ($state) => $state ? $state . ' آية' : '0 آية')                                ->visible(fn (Forms\Get $get) => $get('use_quran_content') && !$get('use_multi_surah_range')),
                        ])->columns(3)
                          ->visible(fn (Forms\Get $get) => $get('use_quran_content') && !$get('use_multi_surah_range') && $get('surah_number')),

                        // Multi-Surah Section
                        Forms\Components\Group::make([
                            Forms\Components\Select::make('start_surah_number')
                                ->label('من السورة')
                                ->options(QuranService::getSurahOptions())
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                    $endSurah = $get('end_surah_number');
                                    $startVerse = $get('start_surah_verse') ?? 1;
                                    $endVerse = $get('end_surah_verse');
                                    
                                    if ($state && $endSurah) {
                                        $quranService = app(QuranService::class);
                                        $totalVerses = $quranService->calculateMultiSurahVerseCount(
                                            $state, $endSurah, $startVerse, $endVerse
                                        );
                                        $set('total_verses_calculated', $totalVerses);
                                        
                                        $formatted = $quranService->formatMultiSurahContent(
                                            $state, $endSurah, $startVerse, $endVerse
                                        );
                                        $set('multi_surah_formatted_content', $formatted);
                                        $set('content', $formatted ?: 'من سورة ' . QuranService::getSurahName($state) . ' إلى سورة ' . QuranService::getSurahName($endSurah));
                                    }
                                })
                                ->required(fn (Forms\Get $get) => $get('use_quran_content') && $get('use_multi_surah_range'))
                                ->visible(fn (Forms\Get $get) => $get('use_quran_content') && $get('use_multi_surah_range')),

                            Forms\Components\Select::make('end_surah_number')
                                ->label('إلى السورة')
                                ->options(QuranService::getSurahOptions())
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                    $startSurah = $get('start_surah_number');
                                    $startVerse = $get('start_surah_verse') ?? 1;
                                    $endVerse = $get('end_surah_verse');
                                    
                                    if ($startSurah && $state) {
                                        $quranService = app(QuranService::class);
                                        $totalVerses = $quranService->calculateMultiSurahVerseCount(
                                            $startSurah, $state, $startVerse, $endVerse
                                        );
                                        $set('total_verses_calculated', $totalVerses);
                                        
                                        $formatted = $quranService->formatMultiSurahContent(
                                            $startSurah, $state, $startVerse, $endVerse
                                        );
                                        $set('multi_surah_formatted_content', $formatted);
                                        $set('content', $formatted ?: 'من سورة ' . QuranService::getSurahName($startSurah) . ' إلى سورة ' . QuranService::getSurahName($state));
                                    }
                                })
                                ->required(fn (Forms\Get $get) => $get('use_quran_content') && $get('use_multi_surah_range'))
                                ->visible(fn (Forms\Get $get) => $get('use_quran_content') && $get('use_multi_surah_range')),
                        ])->columns(2)
                          ->visible(fn (Forms\Get $get) => $get('use_quran_content') && $get('use_multi_surah_range')),

                        // Multi-Surah Verse Boundaries
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('start_surah_verse')
                                ->label('من الآية (السورة الأولى)')
                                ->helperText('اتركه فارغاً للبدء من أول آية')
                                ->numeric()
                                ->minValue(1)
                                ->default(1)
                                ->live()
                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                    $startSurah = $get('start_surah_number');
                                    $endSurah = $get('end_surah_number');
                                    $endVerse = $get('end_surah_verse');
                                    
                                    if ($startSurah && $endSurah) {
                                        $quranService = app(QuranService::class);
                                        $totalVerses = $quranService->calculateMultiSurahVerseCount(
                                            $startSurah, $endSurah, $state ?? 1, $endVerse
                                        );
                                        $set('total_verses_calculated', $totalVerses);
                                        
                                        $formatted = $quranService->formatMultiSurahContent(
                                            $startSurah, $endSurah, $state ?? 1, $endVerse
                                        );
                                        $set('multi_surah_formatted_content', $formatted);
                                        $set('content', $formatted);
                                    }
                                })
                                ->visible(fn (Forms\Get $get) => $get('use_quran_content') && $get('use_multi_surah_range')),

                            Forms\Components\TextInput::make('end_surah_verse')
                                ->label('إلى الآية (السورة الأخيرة)')
                                ->helperText('اتركه فارغاً للانتهاء في آخر آية')
                                ->numeric()
                                ->minValue(1)
                                ->live()
                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                    $startSurah = $get('start_surah_number');
                                    $endSurah = $get('end_surah_number');
                                    $startVerse = $get('start_surah_verse') ?? 1;
                                    
                                    if ($startSurah && $endSurah) {
                                        $quranService = app(QuranService::class);
                                        $totalVerses = $quranService->calculateMultiSurahVerseCount(
                                            $startSurah, $endSurah, $startVerse, $state
                                        );
                                        $set('total_verses_calculated', $totalVerses);
                                        
                                        $formatted = $quranService->formatMultiSurahContent(
                                            $startSurah, $endSurah, $startVerse, $state
                                        );
                                        $set('multi_surah_formatted_content', $formatted);
                                        $set('content', $formatted);
                                    }
                                })
                                ->visible(fn (Forms\Get $get) => $get('use_quran_content') && $get('use_multi_surah_range')),

                            Forms\Components\TextInput::make('total_verses_calculated')
                                ->label('إجمالي الآيات')
                                ->disabled()
                                ->dehydrated(false)
                                ->formatStateUsing(fn ($state) => $state ? $state . ' آية' : '0 آية')
                                ->visible(fn (Forms\Get $get) => $get('use_quran_content') && $get('use_multi_surah_range')),
                        ])->columns(3)
                          ->visible(fn (Forms\Get $get) => $get('use_quran_content') && $get('use_multi_surah_range') && $get('start_surah_number') && $get('end_surah_number')),                        // عرض المحتوى المنسق
                        Forms\Components\Placeholder::make('formatted_preview')
                            ->label('المحتوى المنسق')
                            ->content(function (Forms\Get $get) {
                                $useMultiSurah = $get('use_multi_surah_range');
                                
                                if ($useMultiSurah) {
                                    $startSurah = $get('start_surah_number');
                                    $endSurah = $get('end_surah_number');
                                    $startVerse = $get('start_surah_verse') ?? 1;
                                    $endVerse = $get('end_surah_verse');
                                    
                                    if ($startSurah && $endSurah) {
                                        $quranService = app(QuranService::class);
                                        return $quranService->formatMultiSurahContent($startSurah, $endSurah, $startVerse, $endVerse);
                                    }
                                    
                                    return 'اختر السور لعرض المحتوى المنسق للنطاق المتعدد';
                                } else {
                                    $surahNumber = $get('surah_number');
                                    $startVerse = $get('start_verse');
                                    $endVerse = $get('end_verse');
                                    
                                    if ($surahNumber && $startVerse && $endVerse) {
                                        return QuranService::formatSurahContent($surahNumber, $startVerse, $endVerse);
                                    }
                                    
                                    return 'سيظهر المحتوى المنسق هنا بعد اختيار السورة والآيات';
                                }
                            })
                            ->columnSpanFull()
                            ->visible(fn (Forms\Get $get) => $get('use_quran_content')),                        // حقول مخفية لتخزين البيانات
                        Forms\Components\Hidden::make('content_type')
                            ->default('text'),
                        
                        Forms\Components\Hidden::make('formatted_content'),
                        
                        // Multi-surah hidden fields
                        Forms\Components\Hidden::make('range_type'),
                        Forms\Components\Hidden::make('multi_surah_formatted_content'),
                    ]),

                Forms\Components\Section::make('إعدادات إضافية')
                    ->schema([
                        Forms\Components\Textarea::make('instructions')
                            ->label('تعليمات الخطة')
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('فعّالة')
                            ->default(true)
                            ->onColor('success')
                            ->offColor('danger'),
                    ])->columns(2),
            ]);
    }/**
     * معالجة وإنشاء مجموعة من الخطط من نص مفصول بأسطر
     */
    protected function processPlans(int $curriculumId, ?int $levelId, string $type, string $plansText, int $expectedDays): void
    {
        $plans = array_filter(explode("\n", $plansText));
        $order = $this->getRelationship()->where('plan_type', $type)->max('order') + 1;
        
        foreach ($plans as $plan) {
            $plan = trim($plan);
            if (empty($plan)) continue;

            $this->getRelationship()->create([
                'curriculum_id' => $curriculumId,
                'curriculum_level_id' => $levelId,
                'name' => $plan,
                'content' => $plan,
                'plan_type' => $type,
                'expected_days' => $expectedDays,
                'order' => $order++,
                'is_active' => true,
            ]);
        }
    }
    
    public function table(Table $table): Table
    {
        $curriculumType = $this->ownerRecord->type;
          $columns = [
            Tables\Columns\TextColumn::make('plan_type')
                ->label('نوع الخطة')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'الدرس' => 'primary',
                    'المراجعة الصغرى' => 'warning',
                    'المراجعة الكبرى' => 'success',
                })
                ->sortable(),
            Tables\Columns\TextColumn::make('name')
                ->label('اسم الخطة')
                ->searchable()
                ->wrap(),            Tables\Columns\TextColumn::make('content')
                ->label('المحتوى')                ->description(function ($record) {
                    $contentType = $record->content_type ?? 'text';
                    if ($contentType === 'quran') {
                        $rangeType = $record->range_type ?? 'single_surah';
                        if ($rangeType === 'multi_surah' && $record->multi_surah_formatted_content) {
                            return $record->multi_surah_formatted_content;
                        } elseif ($rangeType === 'single_surah' && $record->formatted_content) {
                            return $record->formatted_content;
                        } elseif ($record->formatted_content) {
                            // Backward compatibility for existing records
                            return $record->formatted_content;
                        }
                    }
                    return $record->content ?? '';
                })
                ->wrap()
                ->limit(50),            Tables\Columns\BadgeColumn::make('content_type')
                ->label('نوع المحتوى')
                ->formatStateUsing(fn (?string $state): string => match ($state ?? 'text') {
                    'quran' => 'محتوى قرآني',
                    'text' => 'نص حر',
                    default => 'غير محدد',
                })
                ->color(fn (?string $state): string => match ($state ?? 'text') {
                    'quran' => 'success',
                    'text' => 'gray',
                    default => 'warning',
                }),
            Tables\Columns\BadgeColumn::make('range_type')
                ->label('نوع النطاق')
                ->formatStateUsing(fn (?string $state): string => match ($state) {
                    'multi_surah' => 'سور متعددة',
                    'single_surah' => 'سورة واحدة',
                    default => 'غير محدد',
                })
                ->color(fn (?string $state): string => match ($state) {
                    'multi_surah' => 'info',
                    'single_surah' => 'primary',
                    default => 'gray',
                })
                ->visible(fn ($record) => ($record->content_type ?? 'text') === 'quran')
                ->toggleable(isToggledHiddenByDefault: true),            Tables\Columns\TextColumn::make('calculated_verses')
                ->label('عدد الآيات')
                ->formatStateUsing(function ($record) {
                    $rangeType = $record->range_type ?? 'single_surah';
                    if ($rangeType === 'multi_surah') {
                        $totalVerses = $record->total_verses_calculated ?? 0;
                        return $totalVerses ? $totalVerses . ' آية' : '-';
                    } else {
                        $verses = $record->calculated_verses ?? 0;
                        return $verses ? $verses . ' آية' : '-';
                    }
                })
                ->visible(fn () => true)
                ->toggleable(isToggledHiddenByDefault: false),
        ];
        
        // إضافة عمود المستوى فقط لمنهج الطالب
        if ($curriculumType === 'منهج طالب') {
            $columns[] = Tables\Columns\TextColumn::make('level.name')
                ->label('المستوى')
                ->sortable();
        }
        
        $columns = array_merge($columns, [
            Tables\Columns\TextColumn::make('expected_days')
                ->label('المدة المتوقعة')
                ->formatStateUsing(fn ($state) => $state ? $state . ' يوم' : '-'),
            Tables\Columns\IconColumn::make('is_active')
                ->label('فعّالة')
                ->boolean()
                ->trueIcon('heroicon-o-check-badge')
                ->falseIcon('heroicon-o-x-circle')
                ->trueColor('success')
                ->falseColor('danger'),
        ]);

        return $table
            ->columns($columns)            ->filters([
                Tables\Filters\SelectFilter::make('plan_type')
                    ->label('نوع الخطة')
                    ->options([
                        'الدرس' => 'الدرس',
                        'المراجعة الصغرى' => 'المراجعة الصغرى',
                        'المراجعة الكبرى' => 'المراجعة الكبرى',
                    ]),
                // إضافة فلتر للمستويات فقط لمنهج الطالب
                ...($curriculumType === 'منهج طالب' ? [
                    Tables\Filters\SelectFilter::make('curriculum_level_id')
                        ->label('المستوى')
                        ->options(
                            CurriculumLevel::where('curriculum_id', $this->ownerRecord->id)
                                ->orderBy('level_order')
                                ->pluck('name', 'id')
                        )
                ] : []),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('فعّالة')
                    ->trueLabel('الخطط الفعّالة فقط')
                    ->falseLabel('الخطط غير الفعّالة فقط')
                    ->native(false)
            ])            ->headerActions([
                Tables\Actions\CreateAction::make(),
                
                Tables\Actions\Action::make('createFromTemplate')
                    ->label('إنشاء من قالب جاهز')
                    ->color('success')
                    ->icon('heroicon-o-sparkles')
                    ->form([
                        Forms\Components\Select::make('template_type')
                            ->label('نوع القالب')                            ->options([
                                'yearly_completion' => 'منهج ختم القرآن في سنة (12 شهر)',
                                'fast_memorization' => 'منهج الحفظ السريع (6 أشهر)',
                                'intensive_review' => 'منهج المراجعة المكثفة (4 أشهر)',
                            ])
                            ->helperText('
                                منهج ختم القرآن في سنة: منهج متدرج للطلاب العاديين - يغطي القرآن كاملاً خلال سنة
                                منهج الحفظ السريع: منهج مكثف للطلاب المتميزين - حفظ سريع ومنظم
                                منهج المراجعة المكثفة: منهج للحفاظ الذين أكملوا القرآن - مراجعة شاملة ومكثفة
                            ')
                            ->required()
                            ->live(),
                            
                        Forms\Components\Placeholder::make('template_details')
                            ->label('تفاصيل القالب')
                            ->content(function (Forms\Get $get) {
                                $templateType = $get('template_type');
                                if (!$templateType) {
                                    return 'اختر نوع القالب لعرض التفاصيل';
                                }
                                
                                $templates = CurriculumTemplateService::getAvailableTemplates();
                                $template = $templates[$templateType] ?? null;
                                
                                if (!$template) {
                                    return 'قالب غير متوفر';
                                }
                                
                                return "📚 **{$template['name']}**\n\n" .
                                       "**الوصف:** {$template['description']}\n\n" .
                                       "**المدة:** {$template['duration']}\n\n" .
                                       "**مناسب لـ:** {$template['suitable_for']}";
                            }),
                            
                        Forms\Components\TextInput::make('curriculum_name')
                            ->label('اسم المنهج')
                            ->helperText('اتركه فارغاً لاستخدام الاسم الافتراضي للقالب')
                            ->maxLength(255),
                            
                        Forms\Components\Textarea::make('curriculum_description')
                            ->label('وصف المنهج (اختياري)')
                            ->helperText('يمكنك تخصيص وصف المنهج أو تركه فارغاً')
                            ->rows(3),
                            
                        Forms\Components\Toggle::make('replace_existing')
                            ->label('استبدال الخطط الموجودة')
                            ->helperText('تحذير: سيؤدي هذا إلى حذف جميع الخطط الموجودة واستبدالها بخطط القالب')
                            ->default(false)
                            ->visible(fn () => $this->getRelationship()->count() > 0),
                    ])
                    ->action(function (array $data): void {
                        $curriculum = $this->ownerRecord;
                        
                        // حذف الخطط الموجودة إذا تم اختيار الاستبدال
                        if (($data['replace_existing'] ?? false) && $this->getRelationship()->count() > 0) {
                            $this->getRelationship()->delete();
                        }
                        
                        try {
                            // إنشاء منهج مؤقت من القالب للحصول على الخطط
                            $tempCurriculum = CurriculumTemplateService::createFromTemplate(
                                $data['template_type'],
                                $data['curriculum_name'] ?? null
                            );
                            
                            // نسخ الخطط إلى المنهج الحالي
                            foreach ($tempCurriculum->plans as $plan) {
                                $newPlan = $plan->replicate();
                                $newPlan->curriculum_id = $curriculum->id;
                                
                                // ربط المستوى إذا كان موجوداً
                                if ($plan->curriculum_level_id && $curriculum->type === 'منهج طالب') {
                                    $levelOrder = $plan->level->level_order ?? 1;
                                    $existingLevel = $curriculum->levels()
                                        ->where('level_order', $levelOrder)
                                        ->first();
                                    
                                    if ($existingLevel) {
                                        $newPlan->curriculum_level_id = $existingLevel->id;
                                    } else {
                                        $newPlan->curriculum_level_id = null;
                                    }
                                } else {
                                    $newPlan->curriculum_level_id = null;
                                }
                                
                                $newPlan->save();
                            }
                            
                            // تحديث وصف المنهج إذا تم تقديمه
                            if (!empty($data['curriculum_description'])) {
                                $curriculum->update([
                                    'description' => $data['curriculum_description']
                                ]);
                            }
                            
                            // حذف المنهج المؤقت
                            $tempCurriculum->delete();
                            
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('تم إنشاء الخطط من القالب بنجاح')
                                ->body('تم إضافة ' . $tempCurriculum->plans->count() . ' خطة من القالب المحدد')
                                ->send();
                                
                        } catch (\Exception $e) {                        \Filament\Notifications\Notification::make()
                            ->danger()
                            ->title('حدث خطأ أثناء إنشاء الخطط')
                            ->body('يرجى المحاولة مرة أخرى أو الاتصال بالدعم الفني')
                            ->send();
                    }
                    
                    // Refresh the table data
                    $this->dispatch('$refresh');
                    }),
                
                Tables\Actions\Action::make('importBulkPlans')
                    ->label('استيراد خطط متعددة')
                    ->color('warning')
                    ->icon('heroicon-o-table-cells')
                    ->form([
                        Forms\Components\TextInput::make('expected_days')
                            ->label('عدد الأيام المتوقعة لكل خطة')
                            ->helperText('عدد الأيام التي يحتاجها الطالب/المعلم لإنهاء الخطة الواحدة')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required(),
                        
                        Forms\Components\Select::make('level_id')
                            ->label('المستوى')
                            ->helperText('اختر المستوى الذي تريد إضافة هذه الخطط له')
                            ->options(function () {
                                return $this->ownerRecord->levels()
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->visible(fn () => $this->ownerRecord->type === 'منهج طالب')
                            ->required(fn () => $this->ownerRecord->type === 'منهج طالب'),
                        
                        Forms\Components\Textarea::make('lesson_plans')
                            ->label('خطط الدروس')
                            ->placeholder('ضع هنا قائمة الدروس (درس في كل سطر)')
                            ->helperText('يمكنك نسخ القائمة مباشرة من ملف Excel')
                            ->required()
                            ->rows(10),
                        
                        Forms\Components\Textarea::make('minor_review_plans')
                            ->label('خطط المراجعة الصغرى')
                            ->placeholder('ضع هنا قائمة خطط المراجعة الصغرى (خطة في كل سطر)')
                            ->helperText('يمكنك نسخ القائمة مباشرة من ملف Excel')
                            ->rows(10),
                        
                        Forms\Components\Textarea::make('major_review_plans')
                            ->label('خطط المراجعة الكبرى')
                            ->placeholder('ضع هنا قائمة خطط المراجعة الكبرى (خطة في كل سطر)')
                            ->helperText('يمكنك نسخ القائمة مباشرة من ملف Excel')
                            ->rows(10),
                    ])
                    ->action(function (array $data): void {
                        $curriculum = $this->ownerRecord;
                        $expectedDays = $data['expected_days'];
                        $levelId = $data['level_id'] ?? null;
                        
                        // معالجة خطط الدروس
                        $this->processPlans(
                            $curriculum->id,
                            $levelId,
                            'الدرس',
                            $data['lesson_plans'],
                            $expectedDays
                        );
                        
                        // معالجة خطط المراجعة الصغرى
                        if (!empty($data['minor_review_plans'])) {
                            $this->processPlans(
                                $curriculum->id,
                                $levelId,
                                'المراجعة الصغرى',
                                $data['minor_review_plans'],
                                $expectedDays
                            );
                        }
                        
                        // معالجة خطط المراجعة الكبرى
                        if (!empty($data['major_review_plans'])) {
                            $this->processPlans(
                                $curriculum->id,
                                $levelId,
                                'المراجعة الكبرى',
                                $data['major_review_plans'],
                                $expectedDays
                            );
                        }
                              \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('تم إضافة الخطط بنجاح')
                        ->send();
                        
                    // Refresh the table data
                    $this->dispatch('$refresh');
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
