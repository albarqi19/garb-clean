<?php

namespace App\Services;

use App\Models\Curriculum;
use App\Models\CurriculumLevel;
use App\Models\CurriculumPlan;
use Carbon\Carbon;

class CurriculumTemplateService
{
    /**
     * إنشاء منهج ختم القرآن في سنة
     */
    public static function createYearlyCompletionCurriculum(string $name = 'منهج ختم القرآن في سنة'): Curriculum
    {
        $curriculum = Curriculum::create([
            'name' => $name,
            'description' => 'منهج متدرج لختم القرآن الكريم خلال سنة دراسية واحدة مع مراجعة منتظمة',
            'type' => 'منهج طالب',
            'duration_months' => 12,
            'is_active' => true,
        ]);

        // إنشاء المستويات (الأشهر)
        $months = [
            'الشهر الأول', 'الشهر الثاني', 'الشهر الثالث', 'الشهر الرابع',
            'الشهر الخامس', 'الشهر السادس', 'الشهر السابع', 'الشهر الثامن',
            'الشهر التاسع', 'الشهر العاشر', 'الشهر الحادي عشر', 'الشهر الثاني عشر'
        ];

        foreach ($months as $index => $monthName) {
            CurriculumLevel::create([
                'curriculum_id' => $curriculum->id,
                'name' => $monthName,
                'description' => "خطة الحفظ والمراجعة للشهر " . ($index + 1),
                'level_order' => $index + 1,
                'expected_duration_days' => 30,
                'is_active' => true,
            ]);
        }

        // توزيع السور على الأشهر (تقريبياً 30 صفحة لكل شهر)
        $monthlyPlans = self::getYearlyCompletionPlans();
        
        foreach ($monthlyPlans as $monthIndex => $plans) {
            $level = $curriculum->levels->where('level_order', $monthIndex + 1)->first();
            
            foreach ($plans as $planData) {
                CurriculumPlan::create([
                    'curriculum_id' => $curriculum->id,
                    'curriculum_level_id' => $level->id,
                    'name' => $planData['name'],
                    'plan_type' => $planData['type'],
                    'content_type' => 'quran',
                    'surah_number' => $planData['surah_number'],
                    'start_verse' => $planData['start_verse'],
                    'end_verse' => $planData['end_verse'],
                    'calculated_verses' => QuranService::calculateVerseCount(
                        $planData['surah_number'],
                        $planData['start_verse'],
                        $planData['end_verse']
                    ),
                    'formatted_content' => QuranService::formatSurahContent(
                        $planData['surah_number'],
                        $planData['start_verse'],
                        $planData['end_verse']
                    ),
                    'content' => QuranService::formatSurahContent(
                        $planData['surah_number'],
                        $planData['start_verse'],
                        $planData['end_verse']
                    ),
                    'expected_days' => $planData['expected_days'],
                    'is_active' => true,
                ]);
            }
        }

        return $curriculum;
    }

    /**
     * إنشاء منهج الحفظ السريع (6 أشهر)
     */
    public static function createFastMemorizationCurriculum(string $name = 'منهج الحفظ السريع'): Curriculum
    {
        $curriculum = Curriculum::create([
            'name' => $name,
            'description' => 'منهج مكثف لحفظ القرآن الكريم في 6 أشهر للطلاب المتميزين',
            'type' => 'منهج طالب',
            'duration_months' => 6,
            'is_active' => true,
        ]);

        // إنشاء المستويات (الأشهر)
        $months = [
            'الشهر الأول - الأجزاء 1-5',
            'الشهر الثاني - الأجزاء 6-10', 
            'الشهر الثالث - الأجزاء 11-15',
            'الشهر الرابع - الأجزاء 16-20',
            'الشهر الخامس - الأجزاء 21-25',
            'الشهر السادس - الأجزاء 26-30'
        ];

        foreach ($months as $index => $monthName) {
            CurriculumLevel::create([
                'curriculum_id' => $curriculum->id,
                'name' => $monthName,
                'description' => "حفظ مكثف " . ($index + 1),
                'level_order' => $index + 1,
                'expected_duration_days' => 30,
                'is_active' => true,
            ]);
        }

        // خطط الحفظ السريع (5 أجزاء شهرياً)
        $fastPlans = self::getFastMemorizationPlans();
        
        foreach ($fastPlans as $monthIndex => $plans) {
            $level = $curriculum->levels->where('level_order', $monthIndex + 1)->first();
            
            foreach ($plans as $planData) {
                CurriculumPlan::create([
                    'curriculum_id' => $curriculum->id,
                    'curriculum_level_id' => $level->id,
                    'name' => $planData['name'],
                    'plan_type' => $planData['type'],
                    'content_type' => 'quran',
                    'surah_number' => $planData['surah_number'],
                    'start_verse' => $planData['start_verse'],
                    'end_verse' => $planData['end_verse'],
                    'calculated_verses' => QuranService::calculateVerseCount(
                        $planData['surah_number'],
                        $planData['start_verse'],
                        $planData['end_verse']
                    ),
                    'formatted_content' => QuranService::formatSurahContent(
                        $planData['surah_number'],
                        $planData['start_verse'],
                        $planData['end_verse']
                    ),
                    'content' => QuranService::formatSurahContent(
                        $planData['surah_number'],
                        $planData['start_verse'],
                        $planData['end_verse']
                    ),
                    'expected_days' => $planData['expected_days'],
                    'is_active' => true,
                ]);
            }
        }

        return $curriculum;
    }

    /**
     * إنشاء منهج المراجعة المكثفة
     */
    public static function createIntensiveReviewCurriculum(string $name = 'منهج المراجعة المكثفة'): Curriculum
    {
        $curriculum = Curriculum::create([
            'name' => $name,
            'description' => 'منهج مراجعة شاملة للطلاب الذين أكملوا حفظ القرآن',
            'type' => 'منهج طالب', 
            'duration_months' => 4,
            'is_active' => true,
        ]);

        // إنشاء المستويات (أسابيع المراجعة)
        $weeks = [
            'الأسبوع 1-4 - مراجعة الأجزاء 1-10',
            'الأسبوع 5-8 - مراجعة الأجزاء 11-20',
            'الأسبوع 9-12 - مراجعة الأجزاء 21-30',
            'الأسبوع 13-16 - مراجعة شاملة'
        ];

        foreach ($weeks as $index => $weekName) {
            CurriculumLevel::create([
                'curriculum_id' => $curriculum->id,
                'name' => $weekName,
                'description' => "مرحلة المراجعة " . ($index + 1),
                'level_order' => $index + 1,
                'expected_duration_days' => 28,
                'is_active' => true,
            ]);
        }

        // خطط المراجعة المكثفة
        $reviewPlans = self::getIntensiveReviewPlans();
        
        foreach ($reviewPlans as $weekIndex => $plans) {
            $level = $curriculum->levels->where('level_order', $weekIndex + 1)->first();
            
            foreach ($plans as $planData) {
                CurriculumPlan::create([
                    'curriculum_id' => $curriculum->id,
                    'curriculum_level_id' => $level->id,
                    'name' => $planData['name'],
                    'plan_type' => $planData['type'],
                    'content_type' => 'text',
                    'content' => $planData['content'],
                    'expected_days' => $planData['expected_days'],
                    'is_active' => true,
                ]);
            }
        }

        return $curriculum;
    }

    /**
     * الحصول على خطط ختم القرآن في سنة
     */
    private static function getYearlyCompletionPlans(): array
    {
        return [
            // الشهر الأول - الفاتحة والبقرة
            0 => [
                [
                    'name' => 'حفظ سورة الفاتحة',
                    'type' => 'الدرس',
                    'surah_number' => 1,
                    'start_verse' => 1,
                    'end_verse' => 7,
                    'expected_days' => 3,
                ],
                [
                    'name' => 'حفظ بداية سورة البقرة (1-50)',
                    'type' => 'الدرس',
                    'surah_number' => 2,
                    'start_verse' => 1,
                    'end_verse' => 50,
                    'expected_days' => 10,
                ],
                [
                    'name' => 'حفظ من سورة البقرة (51-100)',
                    'type' => 'الدرس',
                    'surah_number' => 2,
                    'start_verse' => 51,
                    'end_verse' => 100,
                    'expected_days' => 10,
                ],
                [
                    'name' => 'مراجعة الفاتحة وبداية البقرة',
                    'type' => 'المراجعة الصغرى',
                    'surah_number' => 1,
                    'start_verse' => 1,
                    'end_verse' => 7,
                    'expected_days' => 7,
                ],
            ],
            // الشهر الثاني - استكمال البقرة
            1 => [
                [
                    'name' => 'حفظ من سورة البقرة (101-150)',
                    'type' => 'الدرس',
                    'surah_number' => 2,
                    'start_verse' => 101,
                    'end_verse' => 150,
                    'expected_days' => 8,
                ],
                [
                    'name' => 'حفظ من سورة البقرة (151-200)',
                    'type' => 'الدرس',
                    'surah_number' => 2,
                    'start_verse' => 151,
                    'end_verse' => 200,
                    'expected_days' => 8,
                ],
                [
                    'name' => 'حفظ من سورة البقرة (201-250)',
                    'type' => 'الدرس',
                    'surah_number' => 2,
                    'start_verse' => 201,
                    'end_verse' => 250,
                    'expected_days' => 8,
                ],
                [
                    'name' => 'مراجعة شاملة لسورة البقرة',
                    'type' => 'المراجعة الكبرى',
                    'surah_number' => 2,
                    'start_verse' => 1,
                    'end_verse' => 250,
                    'expected_days' => 6,
                ],
            ],
            // يمكن إضافة باقي الأشهر بنفس الطريقة
        ];
    }

    /**
     * الحصول على خطط الحفظ السريع
     */
    private static function getFastMemorizationPlans(): array
    {
        return [
            // الشهر الأول - 5 أجزاء
            0 => [
                [
                    'name' => 'حفظ الجزء الأول',
                    'type' => 'الدرس',
                    'surah_number' => 1,
                    'start_verse' => 1,
                    'end_verse' => 7,
                    'expected_days' => 6,
                ],
                [
                    'name' => 'حفظ الجزء الثاني',
                    'type' => 'الدرس',
                    'surah_number' => 2,
                    'start_verse' => 142,
                    'end_verse' => 252,
                    'expected_days' => 6,
                ],
                // يمكن إضافة باقي الأجزاء
            ],
        ];
    }

    /**
     * الحصول على خطط المراجعة المكثفة
     */
    private static function getIntensiveReviewPlans(): array
    {
        return [
            // الأسبوع 1-4
            0 => [
                [
                    'name' => 'مراجعة يومية للجزء الأول والثاني',
                    'type' => 'المراجعة الكبرى',
                    'content' => 'مراجعة سورة الفاتحة وبداية سورة البقرة',
                    'expected_days' => 7,
                ],
                [
                    'name' => 'مراجعة الأجزاء 3-5',
                    'type' => 'المراجعة الكبرى', 
                    'content' => 'مراجعة من سورة البقرة إلى بداية سورة آل عمران',
                    'expected_days' => 7,
                ],
            ],
        ];
    }

    /**
     * الحصول على قائمة القوالب المتاحة
     */
    public static function getAvailableTemplates(): array
    {
        return [
            'yearly_completion' => [
                'name' => 'منهج ختم القرآن في سنة',
                'description' => 'منهج متدرج لختم القرآن الكريم خلال سنة دراسية واحدة',
                'duration' => '12 شهر',
                'suitable_for' => 'الطلاب العاديين',
            ],
            'fast_memorization' => [
                'name' => 'منهج الحفظ السريع',
                'description' => 'منهج مكثف لحفظ القرآن الكريم في 6 أشهر',
                'duration' => '6 أشهر',
                'suitable_for' => 'الطلاب المتميزين',
            ],
            'intensive_review' => [
                'name' => 'منهج المراجعة المكثفة',
                'description' => 'منهج مراجعة شاملة للطلاب الذين أكملوا الحفظ',
                'duration' => '4 أشهر',
                'suitable_for' => 'الحفاظ الذين أكملوا القرآن',
            ],
        ];
    }    /**
     * إنشاء منهج من قالب
     */
    public static function createFromTemplate(string $templateType, string $customName = null): Curriculum
    {
        return match ($templateType) {
            'yearly_completion' => self::createYearlyCompletionCurriculum($customName ?? 'منهج ختم القرآن في سنة'),
            'fast_memorization' => self::createFastMemorizationCurriculum($customName ?? 'منهج الحفظ السريع'),
            'intensive_review' => self::createIntensiveReviewCurriculum($customName ?? 'منهج المراجعة المكثفة'),
            default => throw new \InvalidArgumentException("Template type '{$templateType}' not found"),
        };
    }

    /**
     * إنشاء خطط منهج من قالب محدد للمنهج الموجود
     */
    public function createPlansFromTemplate($templateType, $curriculumId, $options = []): bool
    {
        $startDate = $options['start_date'] ?? null;
        $selectedParts = $options['selected_parts'] ?? [];

        switch ($templateType) {
            case 'yearly_completion':
                return $this->createYearlyCompletionPlans($curriculumId, $startDate);
                
            case 'fast_memorization':
                return $this->createFastMemorizationPlans($curriculumId, $startDate, $selectedParts);
                
            case 'intensive_review':
                return $this->createIntensiveReviewPlans($curriculumId, $startDate, $selectedParts);
                
            default:
                return false;
        }
    }

    /**
     * إنشاء خطط ختم القرآن في سنة واحدة
     */
    public function createYearlyCompletionPlans($curriculumId, $startDate = null): bool
    {
        $startDate = $startDate ? Carbon::parse($startDate) : Carbon::now();
        
        $curriculum = Curriculum::find($curriculumId);
        if (!$curriculum) {
            return false;
        }

        $plans = [];
        $currentDate = $startDate->copy();
        $planNumber = 1;

        $quranService = new QuranService();
        $surahs = $quranService->getAllSurahs();
        
        // توزيع السور على 365 يوم (حوالي 17 صفحة يومياً)
        $totalDays = 365;
        $workDaysPerWeek = 6; // راحة يوم الجمعة
        $weeksInYear = 52;
        $totalWorkDays = $weeksInYear * $workDaysPerWeek;
        
        $currentSurahIndex = 0;
        $currentVerseInSurah = 1;

        for ($day = 1; $day <= $totalWorkDays; $day++) {
            if ($currentSurahIndex >= count($surahs)) {
                break; // انتهت السور
            }

            $surah = $surahs[$currentSurahIndex];
            
            // حساب عدد الآيات لهذا اليوم (حوالي 20 آية يومياً)
            $versesPerDay = 20;
            $startVerse = $currentVerseInSurah;
            $endVerse = min($currentVerseInSurah + $versesPerDay - 1, $surah['verses']);

            $plans[] = [
                'curriculum_id' => $curriculumId,
                'plan_number' => $planNumber++,
                'title' => "اليوم {$day}: سورة {$surah['name']} - الآيات {$startVerse}-{$endVerse}",
                'description' => "حفظ ومراجعة سورة {$surah['name']} من الآية {$startVerse} إلى الآية {$endVerse}",
                'surah_number' => $surah['number'],
                'start_verse' => $startVerse,
                'end_verse' => $endVerse,
                'calculated_verses' => $endVerse - $startVerse + 1,
                'content_type' => 'memorization',
                'formatted_content' => $quranService->formatContent($surah['number'], $startVerse, $endVerse),
                'duration_minutes' => 45,
                'status' => 'pending',
                'scheduled_date' => $currentDate->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // تحديث الموقع في السورة
            if ($endVerse >= $surah['verses']) {
                $currentSurahIndex++;
                $currentVerseInSurah = 1;
            } else {
                $currentVerseInSurah = $endVerse + 1;
            }

            // تخطي يوم الجمعة (كل 6 أيام)
            if ($day % 6 == 0) {
                $currentDate->addDay(); // يوم راحة
                
                // إضافة خطة مراجعة أسبوعية
                $weekNumber = ceil($day / 6);
                $plans[] = [
                    'curriculum_id' => $curriculumId,
                    'plan_number' => $planNumber++,
                    'title' => "مراجعة الأسبوع {$weekNumber}",
                    'description' => "مراجعة شاملة لما تم حفظه في الأسبوع {$weekNumber}",
                    'surah_number' => null,
                    'start_verse' => null,
                    'end_verse' => null,
                    'calculated_verses' => 0,
                    'content_type' => 'weekly_review',
                    'formatted_content' => "مراجعة أسبوعية",
                    'duration_minutes' => 60,
                    'status' => 'pending',
                    'scheduled_date' => $currentDate->toDateString(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $currentDate->addDay();
        }

        return $this->insertPlans($plans);
    }

    /**
     * إنشاء خطط الحفظ السريع
     */
    public function createFastMemorizationPlans($curriculumId, $startDate = null, $selectedParts = []): bool
    {
        $startDate = $startDate ? Carbon::parse($startDate) : Carbon::now();
        
        if (empty($selectedParts)) {
            $selectedParts = [30]; // الجزء الثلاثون افتراضياً
        }

        $plans = [];
        $currentDate = $startDate->copy();
        $planNumber = 1;

        $quranService = new QuranService();

        foreach ($selectedParts as $partNumber) {
            // حساب السور في الجزء (مبسط - يحتاج تطوير دقيق)
            $partSurahs = $this->getPartSurahs($partNumber);
            
            foreach ($partSurahs as $surah) {
                // تقسيم السورة على أسبوع (6 أيام حفظ + يوم مراجعة)
                $versesPerDay = ceil($surah['verses'] / 6);
                
                for ($day = 1; $day <= 6; $day++) {
                    $startVerse = (($day - 1) * $versesPerDay) + 1;
                    $endVerse = min($day * $versesPerDay, $surah['verses']);

                    if ($startVerse <= $surah['verses']) {
                        $plans[] = [
                            'curriculum_id' => $curriculumId,
                            'plan_number' => $planNumber++,
                            'title' => "الجزء {$partNumber} - سورة {$surah['name']} - الآيات {$startVerse}-{$endVerse}",
                            'description' => "حفظ مكثف لسورة {$surah['name']} من الآية {$startVerse} إلى الآية {$endVerse}",
                            'surah_number' => $surah['number'],
                            'start_verse' => $startVerse,
                            'end_verse' => $endVerse,
                            'calculated_verses' => $endVerse - $startVerse + 1,
                            'content_type' => 'intensive_memorization',
                            'formatted_content' => $quranService->formatContent($surah['number'], $startVerse, $endVerse),
                            'duration_minutes' => 60,
                            'status' => 'pending',
                            'scheduled_date' => $currentDate->toDateString(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    $currentDate->addDay();
                }
                
                // يوم مراجعة السورة كاملة
                $plans[] = [
                    'curriculum_id' => $curriculumId,
                    'plan_number' => $planNumber++,
                    'title' => "مراجعة سورة {$surah['name']} كاملة",
                    'description' => "مراجعة شاملة لسورة {$surah['name']} كاملة",
                    'surah_number' => $surah['number'],
                    'start_verse' => 1,
                    'end_verse' => $surah['verses'],
                    'calculated_verses' => $surah['verses'],
                    'content_type' => 'review',
                    'formatted_content' => $quranService->formatContent($surah['number'], 1, $surah['verses']),
                    'duration_minutes' => 30,
                    'status' => 'pending',
                    'scheduled_date' => $currentDate->toDateString(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                
                $currentDate->addDay();
            }
        }

        return $this->insertPlans($plans);
    }

    /**
     * إنشاء خطط المراجعة المكثفة
     */
    public function createIntensiveReviewPlans($curriculumId, $startDate = null, $selectedParts = []): bool
    {
        $startDate = $startDate ? Carbon::parse($startDate) : Carbon::now();
        
        if (empty($selectedParts)) {
            $selectedParts = range(1, 30); // جميع الأجزاء افتراضياً
        }

        $plans = [];
        $currentDate = $startDate->copy();
        $planNumber = 1;

        $quranService = new QuranService();
        
        // مراجعة جزء كامل كل يوم
        foreach ($selectedParts as $partNumber) {
            $plans[] = [
                'curriculum_id' => $curriculumId,
                'plan_number' => $planNumber++,
                'title' => "مراجعة الجزء {$partNumber} كاملاً",
                'description' => "مراجعة مكثفة للجزء {$partNumber} من القرآن الكريم بشكل كامل",
                'surah_number' => null,
                'start_verse' => null,
                'end_verse' => null,
                'calculated_verses' => 0,
                'content_type' => 'intensive_review',
                'formatted_content' => "الجزء {$partNumber} كاملاً",
                'duration_minutes' => 90,
                'status' => 'pending',
                'scheduled_date' => $currentDate->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $currentDate->addDay();
        }

        return $this->insertPlans($plans);
    }

    /**
     * الحصول على السور في جزء معين (مبسط)
     */
    private function getPartSurahs($partNumber): array
    {
        $quranService = new QuranService();
        $allSurahs = $quranService->getAllSurahs();
        
        // توزيع مبسط للأجزاء (يحتاج دقة أكثر)
        $surahsPerPart = ceil(count($allSurahs) / 30);
        $startIndex = ($partNumber - 1) * $surahsPerPart;
        $endIndex = min($startIndex + $surahsPerPart, count($allSurahs));
        
        return array_slice($allSurahs, $startIndex, $endIndex - $startIndex);
    }

    /**
     * إدراج الخطط في قاعدة البيانات
     */
    private function insertPlans(array $plans): bool
    {
        try {
            // تقسيم الخطط إلى مجموعات لتجنب مشاكل الذاكرة
            $chunks = array_chunk($plans, 100);
            
            foreach ($chunks as $chunk) {
                CurriculumPlan::insert($chunk);
            }
            
            return true;
        } catch (\Exception $e) {
            \Log::error('خطأ في إدراج خطط المنهج: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * حذف جميع خطط المنهج الحالية
     */
    public function clearCurriculumPlans($curriculumId): bool
    {
        try {
            CurriculumPlan::where('curriculum_id', $curriculumId)->delete();
            return true;
        } catch (\Exception $e) {
            \Log::error('خطأ في حذف خطط المنهج: ' . $e->getMessage());
            return false;
        }
    }
}
