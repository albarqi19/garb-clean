<?php

namespace App\Services;

class QuranService
{
    /**
     * قائمة السور القرآنية مع أرقامها وعدد آياتها
     * 
     * @return array
     */
    public static function getSurahList(): array
    {
        return [
            1 => ['name' => 'الفاتحة', 'verses' => 7],
            2 => ['name' => 'البقرة', 'verses' => 286],
            3 => ['name' => 'آل عمران', 'verses' => 200],
            4 => ['name' => 'النساء', 'verses' => 176],
            5 => ['name' => 'المائدة', 'verses' => 120],
            6 => ['name' => 'الأنعام', 'verses' => 165],
            7 => ['name' => 'الأعراف', 'verses' => 206],
            8 => ['name' => 'الأنفال', 'verses' => 75],
            9 => ['name' => 'التوبة', 'verses' => 129],
            10 => ['name' => 'يونس', 'verses' => 109],
            11 => ['name' => 'هود', 'verses' => 123],
            12 => ['name' => 'يوسف', 'verses' => 111],
            13 => ['name' => 'الرعد', 'verses' => 43],
            14 => ['name' => 'إبراهيم', 'verses' => 52],
            15 => ['name' => 'الحجر', 'verses' => 99],
            16 => ['name' => 'النحل', 'verses' => 128],
            17 => ['name' => 'الإسراء', 'verses' => 111],
            18 => ['name' => 'الكهف', 'verses' => 110],
            19 => ['name' => 'مريم', 'verses' => 98],
            20 => ['name' => 'طه', 'verses' => 135],
            21 => ['name' => 'الأنبياء', 'verses' => 112],
            22 => ['name' => 'الحج', 'verses' => 78],
            23 => ['name' => 'المؤمنون', 'verses' => 118],
            24 => ['name' => 'النور', 'verses' => 64],
            25 => ['name' => 'الفرقان', 'verses' => 77],
            26 => ['name' => 'الشعراء', 'verses' => 227],
            27 => ['name' => 'النمل', 'verses' => 93],
            28 => ['name' => 'القصص', 'verses' => 88],
            29 => ['name' => 'العنكبوت', 'verses' => 69],
            30 => ['name' => 'الروم', 'verses' => 60],
            31 => ['name' => 'لقمان', 'verses' => 34],
            32 => ['name' => 'السجدة', 'verses' => 30],
            33 => ['name' => 'الأحزاب', 'verses' => 73],
            34 => ['name' => 'سبأ', 'verses' => 54],
            35 => ['name' => 'فاطر', 'verses' => 45],
            36 => ['name' => 'يس', 'verses' => 83],
            37 => ['name' => 'الصافات', 'verses' => 182],
            38 => ['name' => 'ص', 'verses' => 88],
            39 => ['name' => 'الزمر', 'verses' => 75],
            40 => ['name' => 'غافر', 'verses' => 85],
            41 => ['name' => 'فصلت', 'verses' => 54],
            42 => ['name' => 'الشورى', 'verses' => 53],
            43 => ['name' => 'الزخرف', 'verses' => 89],
            44 => ['name' => 'الدخان', 'verses' => 59],
            45 => ['name' => 'الجاثية', 'verses' => 37],
            46 => ['name' => 'الأحقاف', 'verses' => 35],
            47 => ['name' => 'محمد', 'verses' => 38],
            48 => ['name' => 'الفتح', 'verses' => 29],
            49 => ['name' => 'الحجرات', 'verses' => 18],
            50 => ['name' => 'ق', 'verses' => 45],
            51 => ['name' => 'الذاريات', 'verses' => 60],
            52 => ['name' => 'الطور', 'verses' => 49],
            53 => ['name' => 'النجم', 'verses' => 62],
            54 => ['name' => 'القمر', 'verses' => 55],
            55 => ['name' => 'الرحمن', 'verses' => 78],
            56 => ['name' => 'الواقعة', 'verses' => 96],
            57 => ['name' => 'الحديد', 'verses' => 29],
            58 => ['name' => 'المجادلة', 'verses' => 22],
            59 => ['name' => 'الحشر', 'verses' => 24],
            60 => ['name' => 'الممتحنة', 'verses' => 13],
            61 => ['name' => 'الصف', 'verses' => 14],
            62 => ['name' => 'الجمعة', 'verses' => 11],
            63 => ['name' => 'المنافقون', 'verses' => 11],
            64 => ['name' => 'التغابن', 'verses' => 18],
            65 => ['name' => 'الطلاق', 'verses' => 12],
            66 => ['name' => 'التحريم', 'verses' => 12],
            67 => ['name' => 'الملك', 'verses' => 30],
            68 => ['name' => 'القلم', 'verses' => 52],
            69 => ['name' => 'الحاقة', 'verses' => 52],
            70 => ['name' => 'المعارج', 'verses' => 44],
            71 => ['name' => 'نوح', 'verses' => 28],
            72 => ['name' => 'الجن', 'verses' => 28],
            73 => ['name' => 'المزمل', 'verses' => 20],
            74 => ['name' => 'المدثر', 'verses' => 56],
            75 => ['name' => 'القيامة', 'verses' => 40],
            76 => ['name' => 'الإنسان', 'verses' => 31],
            77 => ['name' => 'المرسلات', 'verses' => 50],
            78 => ['name' => 'النبأ', 'verses' => 40],
            79 => ['name' => 'النازعات', 'verses' => 46],
            80 => ['name' => 'عبس', 'verses' => 42],
            81 => ['name' => 'التكوير', 'verses' => 29],
            82 => ['name' => 'الانفطار', 'verses' => 19],
            83 => ['name' => 'المطففين', 'verses' => 36],
            84 => ['name' => 'الانشقاق', 'verses' => 25],
            85 => ['name' => 'البروج', 'verses' => 22],
            86 => ['name' => 'الطارق', 'verses' => 17],
            87 => ['name' => 'الأعلى', 'verses' => 19],
            88 => ['name' => 'الغاشية', 'verses' => 26],
            89 => ['name' => 'الفجر', 'verses' => 30],
            90 => ['name' => 'البلد', 'verses' => 20],
            91 => ['name' => 'الشمس', 'verses' => 15],
            92 => ['name' => 'الليل', 'verses' => 21],
            93 => ['name' => 'الضحى', 'verses' => 11],
            94 => ['name' => 'الشرح', 'verses' => 8],
            95 => ['name' => 'التين', 'verses' => 8],
            96 => ['name' => 'العلق', 'verses' => 19],
            97 => ['name' => 'القدر', 'verses' => 5],
            98 => ['name' => 'البينة', 'verses' => 8],
            99 => ['name' => 'الزلزلة', 'verses' => 8],
            100 => ['name' => 'العاديات', 'verses' => 11],
            101 => ['name' => 'القارعة', 'verses' => 11],
            102 => ['name' => 'التكاثر', 'verses' => 8],
            103 => ['name' => 'العصر', 'verses' => 3],
            104 => ['name' => 'الهمزة', 'verses' => 9],
            105 => ['name' => 'الفيل', 'verses' => 5],
            106 => ['name' => 'قريش', 'verses' => 4],
            107 => ['name' => 'الماعون', 'verses' => 7],
            108 => ['name' => 'الكوثر', 'verses' => 3],
            109 => ['name' => 'الكافرون', 'verses' => 6],
            110 => ['name' => 'النصر', 'verses' => 3],
            111 => ['name' => 'المسد', 'verses' => 5],
            112 => ['name' => 'الإخلاص', 'verses' => 4],
            113 => ['name' => 'الفلق', 'verses' => 5],
            114 => ['name' => 'الناس', 'verses' => 6]        ];
    }

    /**
     * الحصول على جميع السور كمصفوفة
     * 
     * @return array
     */
    public function getAllSurahs(): array
    {
        return self::getSurahList();
    }

    /**
     * الحصول على خيارات السور للاستخدام في القوائم المنسدلة
     * 
     * @return array
     */
    public static function getSurahOptions(): array
    {
        $surahs = self::getSurahList();
        $options = [];
        
        foreach ($surahs as $number => $surah) {
            $options[$number] = sprintf('%d - %s (%d آية)', $number, $surah['name'], $surah['verses']);
        }
        
        return $options;
    }

    /**
     * الحصول على عدد الآيات في سورة معينة
     * 
     * @param int $surahNumber
     * @return int
     */
    public static function getVerseCount(int $surahNumber): int
    {
        $surahs = self::getSurahList();
        return $surahs[$surahNumber]['verses'] ?? 0;
    }

    /**
     * الحصول على اسم السورة بالرقم
     * 
     * @param int $surahNumber
     * @return string
     */
    public static function getSurahName(int $surahNumber): string
    {
        $surahs = self::getSurahList();
        return $surahs[$surahNumber]['name'] ?? '';
    }

    /**
     * حساب عدد الآيات بين آيتين في سورة واحدة
     * 
     * @param int $surahNumber
     * @param int $startVerse
     * @param int $endVerse
     * @return int
     */
    public static function calculateVerseCount(int $surahNumber, int $startVerse, int $endVerse): int
    {
        $totalVerses = self::getVerseCount($surahNumber);
        
        // التحقق من صحة أرقام الآيات
        if ($startVerse < 1 || $endVerse > $totalVerses || $startVerse > $endVerse) {
            return 0;
        }
        
        return $endVerse - $startVerse + 1;
    }

    /**
     * تحويل معلومات السورة والآيات إلى نص قابل للقراءة
     * 
     * @param int $surahNumber
     * @param int $startVerse
     * @param int $endVerse
     * @return string
     */
    public static function formatSurahContent(int $surahNumber, int $startVerse, int $endVerse): string
    {
        $surahName = self::getSurahName($surahNumber);
        $verseCount = self::calculateVerseCount($surahNumber, $startVerse, $endVerse);
        
        if ($startVerse === $endVerse) {
            return sprintf('سورة %s - الآية %d', $surahName, $startVerse);
        }
        
        return sprintf('سورة %s - من الآية %d إلى الآية %d (%d آيات)', 
                      $surahName, $startVerse, $endVerse, $verseCount);
    }    /**
     * الحصول على معلومات الجزء والحزب والربع (يمكن توسيعها لاحقاً)
     * 
     * @param int $surahNumber
     * @param int $verseNumber
     * @return array
     */
    public static function getJuzInfo(int $surahNumber, int $verseNumber): array
    {
        // هذه دالة أساسية يمكن توسيعها لاحقاً لتتضمن معلومات الأجزاء والأحزاب
        return [
            'juz' => null, // رقم الجزء
            'hizb' => null, // رقم الحزب
            'quarter' => null // رقم الربع
        ];
    }

    /**
     * حساب عدد الآيات عبر نطاق من السور المتعددة
     * 
     * @param int $startSurahNumber السورة الأولى
     * @param int $startVerse الآية الأولى في السورة الأولى
     * @param int $endSurahNumber السورة الأخيرة
     * @param int|null $endVerse الآية الأخيرة في السورة الأخيرة (null يعني إلى نهاية السورة)
     * @return int إجمالي عدد الآيات
     */    public static function calculateMultiSurahVerseCount(
        int $startSurahNumber, 
        int $startVerse, 
        int $endSurahNumber, 
        ?int $endVerse = null    ): int {
        // التحقق من صحة المدخلات
        if ($startSurahNumber > $endSurahNumber) {
            return 0;
        }
        
        // إذا كان endVerse فارغاً، استخدم نهاية السورة الأخيرة
        if ($endVerse === null) {
            $endVerse = self::getVerseCount($endSurahNumber);
        }
        
        if ($startSurahNumber === $endSurahNumber) {
            // نفس السورة - استخدم الدالة العادية
            return self::calculateVerseCount($startSurahNumber, $startVerse, $endVerse);
        }
        
        $totalVerses = 0;
        $surahs = self::getSurahList();
        
        // حساب الآيات في السورة الأولى (من الآية المحددة إلى نهاية السورة)
        $firstSurahTotalVerses = $surahs[$startSurahNumber]['verses'] ?? 0;
        if ($startVerse <= $firstSurahTotalVerses) {
            $totalVerses += $firstSurahTotalVerses - $startVerse + 1;
        }
        
        // حساب الآيات في السور الوسطى (كاملة)
        for ($surahNum = $startSurahNumber + 1; $surahNum < $endSurahNumber; $surahNum++) {
            $totalVerses += $surahs[$surahNum]['verses'] ?? 0;
        }
        
        // حساب الآيات في السورة الأخيرة (من بداية السورة إلى الآية المحددة)
        $lastSurahTotalVerses = $surahs[$endSurahNumber]['verses'] ?? 0;
        if ($endVerse <= $lastSurahTotalVerses) {
            $totalVerses += $endVerse;
        }
        
        return $totalVerses;
    }    /**
     * تنسيق النطاق المتعدد للسور إلى نص قابل للقراءة
     * 
     * @param int $startSurahNumber
     * @param int $startVerse
     * @param int $endSurahNumber
     * @param int|null $endVerse
     * @return string
     */
    public static function formatMultiSurahContent(
        int $startSurahNumber, 
        int $startVerse, 
        int $endSurahNumber, 
        ?int $endVerse = null
    ): string {
        // إذا كان endVerse فارغاً، استخدم نهاية السورة الأخيرة
        if ($endVerse === null) {
            $endVerse = self::getVerseCount($endSurahNumber);
        }
        
        $startSurahName = self::getSurahName($startSurahNumber);
        $endSurahName = self::getSurahName($endSurahNumber);
        
        if ($startSurahNumber === $endSurahNumber) {
            // نفس السورة
            return self::formatSurahContent($startSurahNumber, $startVerse, $endVerse);
        }
        
        $totalVerses = self::calculateMultiSurahVerseCount(
            $startSurahNumber, $startVerse, $endSurahNumber, $endVerse
        );
        
        // تحديد التفاصيل حسب النطاق
        $startSurahDetails = '';
        $endSurahDetails = '';
        
        // تفاصيل السورة الأولى
        $startSurahTotalVerses = self::getVerseCount($startSurahNumber);
        if ($startVerse === 1 && $startVerse === $startSurahTotalVerses) {
            $startSurahDetails = "سورة {$startSurahName} كاملة";
        } elseif ($startVerse === 1) {
            $startSurahDetails = "من بداية سورة {$startSurahName}";
        } else {
            $startSurahDetails = "من سورة {$startSurahName} الآية {$startVerse}";
        }
        
        // تفاصيل السورة الأخيرة
        $endSurahTotalVerses = self::getVerseCount($endSurahNumber);
        if ($endVerse === $endSurahTotalVerses) {
            $endSurahDetails = "إلى نهاية سورة {$endSurahName}";
        } else {
            $endSurahDetails = "إلى سورة {$endSurahName} الآية {$endVerse}";
        }
        
        return sprintf('%s %s (%d آية)', 
                      $startSurahDetails, 
                      $endSurahDetails, 
                      $totalVerses);
    }    /**
     * التحقق من صحة النطاق المتعدد للسور
     * 
     * @param int $startSurahNumber
     * @param int $startVerse
     * @param int $endSurahNumber
     * @param int|null $endVerse
     * @return bool
     */
    public static function validateMultiSurahRange(
        int $startSurahNumber, 
        int $startVerse, 
        int $endSurahNumber, 
        ?int $endVerse = null
    ): bool {
        $surahs = self::getSurahList();
        
        // إذا كان endVerse فارغاً، استخدم نهاية السورة الأخيرة
        if ($endVerse === null) {
            $endVerse = self::getVerseCount($endSurahNumber);
        }
        
        // التحقق من وجود السور
        if (!isset($surahs[$startSurahNumber]) || !isset($surahs[$endSurahNumber])) {
            return false;
        }
        
        // التحقق من ترتيب السور
        if ($startSurahNumber > $endSurahNumber) {
            return false;
        }
        
        // التحقق من صحة أرقام الآيات في السورة الأولى
        $startSurahTotalVerses = $surahs[$startSurahNumber]['verses'];
        if ($startVerse < 1 || $startVerse > $startSurahTotalVerses) {
            return false;
        }
        
        // التحقق من صحة أرقام الآيات في السورة الأخيرة
        $endSurahTotalVerses = $surahs[$endSurahNumber]['verses'];
        if ($endVerse < 1 || $endVerse > $endSurahTotalVerses) {
            return false;
        }
        
        return true;
    }

    /**
     * الحصول على قائمة السور ضمن نطاق معين
     * 
     * @param int $startSurahNumber
     * @param int $endSurahNumber
     * @return array
     */
    public static function getSurahsInRange(int $startSurahNumber, int $endSurahNumber): array
    {
        $surahs = self::getSurahList();
        $result = [];
        
        for ($i = $startSurahNumber; $i <= $endSurahNumber; $i++) {
            if (isset($surahs[$i])) {
                $result[$i] = $surahs[$i];
            }
        }
        
        return $result;
    }    /**
     * إنشاء ملخص تفصيلي للنطاق المتعدد
     * 
     * @param int $startSurahNumber
     * @param int $startVerse
     * @param int $endSurahNumber
     * @param int|null $endVerse
     * @return array
     */
    public static function getMultiSurahRangeSummary(
        int $startSurahNumber, 
        int $startVerse, 
        int $endSurahNumber, 
        ?int $endVerse = null
    ): array {
        // إذا كان endVerse فارغاً، استخدم نهاية السورة الأخيرة
        if ($endVerse === null) {
            $endVerse = self::getVerseCount($endSurahNumber);
        }
        
        $surahsInRange = self::getSurahsInRange($startSurahNumber, $endSurahNumber);
        $totalVerses = self::calculateMultiSurahVerseCount(
            $startSurahNumber, $startVerse, $endSurahNumber, $endVerse
        );
        $formattedContent = self::formatMultiSurahContent(
            $startSurahNumber, $startVerse, $endSurahNumber, $endVerse
        );
        
        return [
            'start_surah' => [
                'number' => $startSurahNumber,
                'name' => self::getSurahName($startSurahNumber),
                'start_verse' => $startVerse
            ],
            'end_surah' => [
                'number' => $endSurahNumber,
                'name' => self::getSurahName($endSurahNumber),
                'end_verse' => $endVerse
            ],
            'surahs_count' => count($surahsInRange),
            'total_verses' => $totalVerses,
            'formatted_content' => $formattedContent,
            'surahs_in_range' => $surahsInRange,
            'is_valid' => self::validateMultiSurahRange(
                $startSurahNumber, $startVerse, $endSurahNumber, $endVerse
            )
        ];
    }
}
