<?php

namespace App\Filament\Forms\Components;

use App\Services\QuranService;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Get;
use Filament\Forms\Set;

class QuranContentSelector extends Component
{
    protected string $view = 'filament.forms.components.quran-content-selector';

    public static function make(string $name = 'quran_content'): static
    {
        return app(static::class, ['name' => $name]);
    }

    public function getChildComponents(): array
    {
        return [
            Toggle::make('use_quran_content')
                ->label('استخدام محتوى قرآني منظم')
                ->helperText('فعل هذا الخيار لاستخدام محدد السور والآيات بدلاً من النص الحر')
                ->default(false)
                ->live()
                ->afterStateUpdated(function (Set $set, $state) {
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
                }),

            Grid::make(2)
                ->schema([
                    // المحتوى النصي التقليدي
                    Textarea::make('content')
                        ->label('محتوى الخطة')
                        ->helperText('حدد السور أو الآيات أو الأجزاء المطلوب دراستها في هذه الخطة')
                        ->rows(3)
                        ->columnSpanFull()
                        ->visible(fn (Get $get) => !$get('use_quran_content')),

                    // قائمة السور
                    Select::make('surah_number')
                        ->label('السورة')
                        ->options(QuranService::getSurahOptions())
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function (Set $set, $state) {
                            if ($state) {
                                // إعادة تعيين آيات البداية والنهاية
                                $set('start_verse', 1);
                                $verseCount = QuranService::getVerseCount($state);
                                $set('end_verse', $verseCount);
                                $set('calculated_verses', $verseCount);
                            } else {
                                $set('start_verse', null);
                                $set('end_verse', null);
                                $set('calculated_verses', null);
                            }
                        })
                        ->visible(fn (Get $get) => $get('use_quran_content')),

                    Placeholder::make('surah_info')
                        ->label('')
                        ->content(function (Get $get) {
                            $surahNumber = $get('surah_number');
                            if (!$surahNumber) {
                                return 'اختر سورة لعرض المعلومات';
                            }
                            
                            $surahName = QuranService::getSurahName($surahNumber);
                            $verseCount = QuranService::getVerseCount($surahNumber);
                            
                            return "سورة {$surahName} - تحتوي على {$verseCount} آية";
                        })
                        ->visible(fn (Get $get) => $get('use_quran_content') && $get('surah_number')),
                ])
                ->visible(fn (Get $get) => $get('use_quran_content')),

            Grid::make(3)
                ->schema([
                    // آية البداية
                    TextInput::make('start_verse')
                        ->label('من الآية')
                        ->numeric()
                        ->minValue(1)
                        ->live()
                        ->afterStateUpdated(function (Set $set, Get $get, $state) {
                            $surahNumber = $get('surah_number');
                            $endVerse = $get('end_verse');
                            
                            if ($surahNumber && $state && $endVerse) {
                                $calculated = QuranService::calculateVerseCount($surahNumber, $state, $endVerse);
                                $set('calculated_verses', $calculated);
                                
                                if ($calculated > 0) {
                                    $formatted = QuranService::formatSurahContent($surahNumber, $state, $endVerse);
                                    $set('formatted_content', $formatted);
                                    $set('content', $formatted);
                                }
                            }
                        })
                        ->rules(function (Get $get) {
                            return [
                                function ($attribute, $value, $fail) use ($get) {
                                    $surahNumber = $get('surah_number');
                                    $endVerse = $get('end_verse');
                                    
                                    if ($surahNumber && $value && $endVerse) {
                                        $totalVerses = QuranService::getVerseCount($surahNumber);
                                        
                                        if ($value < 1) {
                                            $fail('رقم آية البداية يجب أن يكون 1 أو أكثر');
                                        }
                                        
                                        if ($value > $totalVerses) {
                                            $fail("رقم آية البداية لا يمكن أن يكون أكبر من {$totalVerses}");
                                        }
                                        
                                        if ($value > $endVerse) {
                                            $fail('آية البداية لا يمكن أن تكون أكبر من آية النهاية');
                                        }
                                    }
                                }
                            ];
                        }),

                    // آية النهاية
                    TextInput::make('end_verse')
                        ->label('إلى الآية')
                        ->numeric()
                        ->minValue(1)
                        ->live()
                        ->afterStateUpdated(function (Set $set, Get $get, $state) {
                            $surahNumber = $get('surah_number');
                            $startVerse = $get('start_verse');
                            
                            if ($surahNumber && $startVerse && $state) {
                                $calculated = QuranService::calculateVerseCount($surahNumber, $startVerse, $state);
                                $set('calculated_verses', $calculated);
                                
                                if ($calculated > 0) {
                                    $formatted = QuranService::formatSurahContent($surahNumber, $startVerse, $state);
                                    $set('formatted_content', $formatted);
                                    $set('content', $formatted);
                                }
                            }
                        })
                        ->rules(function (Get $get) {
                            return [
                                function ($attribute, $value, $fail) use ($get) {
                                    $surahNumber = $get('surah_number');
                                    $startVerse = $get('start_verse');
                                    
                                    if ($surahNumber && $startVerse && $value) {
                                        $totalVerses = QuranService::getVerseCount($surahNumber);
                                        
                                        if ($value < 1) {
                                            $fail('رقم آية النهاية يجب أن يكون 1 أو أكثر');
                                        }
                                        
                                        if ($value > $totalVerses) {
                                            $fail("رقم آية النهاية لا يمكن أن يكون أكبر من {$totalVerses}");
                                        }
                                        
                                        if ($value < $startVerse) {
                                            $fail('آية النهاية لا يمكن أن تكون أصغر من آية البداية');
                                        }
                                    }
                                }
                            ];
                        }),

                    // عدد الآيات المحسوب
                    TextInput::make('calculated_verses')
                        ->label('عدد الآيات')
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(fn ($state) => $state ? $state . ' آية' : '0 آية'),
                ])
                ->visible(fn (Get $get) => $get('use_quran_content') && $get('surah_number')),

            // عرض المحتوى المنسق
            Placeholder::make('formatted_preview')
                ->label('المحتوى المنسق')
                ->content(function (Get $get) {
                    $surahNumber = $get('surah_number');
                    $startVerse = $get('start_verse');
                    $endVerse = $get('end_verse');
                    
                    if ($surahNumber && $startVerse && $endVerse) {
                        return QuranService::formatSurahContent($surahNumber, $startVerse, $endVerse);
                    }
                    
                    return 'سيظهر المحتوى المنسق هنا بعد اختيار السورة والآيات';
                })
                ->columnSpanFull()
                ->visible(fn (Get $get) => $get('use_quran_content')),

            // حقول مخفية لتخزين البيانات
            TextInput::make('content_type')
                ->default('text')
                ->hidden(),
            
            Textarea::make('formatted_content')
                ->hidden(),
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->columnSpanFull();
    }
}
