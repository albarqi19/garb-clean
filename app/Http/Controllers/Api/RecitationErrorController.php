<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RecitationError;
use App\Models\RecitationSession;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class RecitationErrorController extends Controller
{
    /**
     * عرض أخطاء جلسة تسميع محددة
     */
    public function index(Request $request): JsonResponse
    {
        $query = RecitationError::with(['recitationSession.student', 'recitationSession.teacher']);

        // فلترة حسب session_id
        if ($request->has('session_id')) {
            $query->where('session_id', $request->session_id);
        }

        $errors = $query->latest()->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $errors,
            'message' => 'تم جلب أخطاء التسميع بنجاح'
        ]);
    }

    /**
     * إضافة أخطاء لجلسة تسميع (يمكن إضافة عدة أخطاء دفعة واحدة)
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string|exists:recitation_sessions,session_id',
            'errors' => 'required|array|min:1',
            'errors.*.surah_number' => 'required|integer|min:1|max:114',
            'errors.*.verse_number' => 'required|integer|min:1',
            'errors.*.error_type' => 'required|in:نطق,تجويد,حفظ,ترتيل,وقف وابتداء,أخرى',
            'errors.*.severity_level' => 'required|in:خفيف,متوسط,شديد'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في البيانات المدخلة',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // التحقق من وجود الجلسة
            $session = RecitationSession::where('session_id', $request->session_id)->first();
            
            if (!$session) {
                return response()->json([
                    'success' => false,
                    'message' => 'جلسة التسميع غير موجودة'
                ], 404);
            }

            $createdErrors = [];
            
            // إضافة كل خطأ
            foreach ($request->errors as $errorData) {
                $errorData['session_id'] = $request->session_id;
                $errorData['recitation_session_id'] = $session->id;
                $errorData['is_repeated'] = $errorData['is_repeated'] ?? false;
                
                $error = RecitationError::create($errorData);
                $createdErrors[] = $error;
            }

            // تحديث حالة الأخطاء في الجلسة
            $session->update(['has_errors' => true]);

            return response()->json([
                'success' => true,
                'data' => $createdErrors,
                'message' => 'تم إضافة أخطاء التسميع بنجاح',
                'total_errors' => count($createdErrors)
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة أخطاء التسميع',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * إحصائيات الأخطاء الشائعة
     */
    public function getCommonErrors(): JsonResponse
    {
        try {
            // أنواع الأخطاء الأكثر شيوعاً
            $errorTypes = RecitationError::selectRaw('error_type, COUNT(*) as count')
                ->groupBy('error_type')
                ->orderBy('count', 'desc')
                ->get();

            // مستويات الخطورة
            $severityLevels = RecitationError::selectRaw('severity_level, COUNT(*) as count')
                ->groupBy('severity_level')
                ->orderBy('count', 'desc')
                ->get();

            // السور الأكثر خطأً
            $commonSurahs = RecitationError::selectRaw('surah_number, COUNT(*) as count')
                ->groupBy('surah_number')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($item) {
                    return [
                        'surah_number' => $item->surah_number,
                        'errors_count' => $item->count,
                        'surah_name' => $this->getSurahName($item->surah_number)
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'error_types' => $errorTypes,
                    'severity_levels' => $severityLevels,
                    'common_surahs' => $commonSurahs,
                    'total_errors' => RecitationError::count()
                ],
                'message' => 'تم جلب إحصائيات الأخطاء الشائعة بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب إحصائيات الأخطاء',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * إحصائيات أخطاء طالب محدد
     */
    public function getStudentErrors(int $studentId): JsonResponse
    {
        try {
            $student = \App\Models\Student::find($studentId);
            
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'الطالب غير موجود'
                ], 404);
            }

            // إنشاء query builder للأخطاء
            $baseQuery = RecitationError::whereHas('recitationSession', function ($query) use ($studentId) {
                $query->where('student_id', $studentId);
            });

            $totalErrors = $baseQuery->count();
            
            // أنواع الأخطاء للطالب
            $errorTypes = RecitationError::whereHas('recitationSession', function ($query) use ($studentId) {
                $query->where('student_id', $studentId);
            })->selectRaw('error_type, COUNT(*) as count')
                ->groupBy('error_type')
                ->orderBy('count', 'desc')
                ->get();

            // السور الأكثر خطأً للطالب
            $studentSurahs = RecitationError::whereHas('recitationSession', function ($query) use ($studentId) {
                $query->where('student_id', $studentId);
            })->selectRaw('surah_number, COUNT(*) as count')
                ->groupBy('surah_number')
                ->orderBy('count', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($item) {
                    return [
                        'surah_number' => $item->surah_number,
                        'errors_count' => $item->count,
                        'surah_name' => $this->getSurahName($item->surah_number)
                    ];
                });

            // الأخطاء المتكررة
            $repeatedErrors = RecitationError::whereHas('recitationSession', function ($query) use ($studentId) {
                $query->where('student_id', $studentId);
            })->where('is_repeated', true)->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'student' => [
                        'id' => $student->id,
                        'name' => $student->name,
                        'identity_number' => $student->identity_number
                    ],
                    'total_errors' => $totalErrors,
                    'repeated_errors' => $repeatedErrors,
                    'error_types' => $errorTypes,
                    'common_surahs' => $studentSurahs,
                    'improvement_percentage' => $totalErrors > 0 ? round((1 - ($repeatedErrors / $totalErrors)) * 100, 2) : 100
                ],
                'message' => 'تم جلب إحصائيات أخطاء الطالب بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب إحصائيات أخطاء الطالب',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * الحصول على اسم السورة بالعربية
     */
    private function getSurahName(int $surahNumber): string
    {
        $surahs = [
            1 => 'الفاتحة', 2 => 'البقرة', 3 => 'آل عمران', 4 => 'النساء', 5 => 'المائدة',
            6 => 'الأنعام', 7 => 'الأعراف', 8 => 'الأنفال', 9 => 'التوبة', 10 => 'يونس',
            11 => 'هود', 12 => 'يوسف', 13 => 'الرعد', 14 => 'إبراهيم', 15 => 'الحجر',
            16 => 'النحل', 17 => 'الإسراء', 18 => 'الكهف', 19 => 'مريم', 20 => 'طه',
            21 => 'الأنبياء', 22 => 'الحج', 23 => 'المؤمنون', 24 => 'النور', 25 => 'الفرقان',
            26 => 'الشعراء', 27 => 'النمل', 28 => 'القصص', 29 => 'العنكبوت', 30 => 'الروم',
            31 => 'لقمان', 32 => 'السجدة', 33 => 'الأحزاب', 34 => 'سبأ', 35 => 'فاطر',
            36 => 'يس', 37 => 'الصافات', 38 => 'ص', 39 => 'الزمر', 40 => 'غافر',
            41 => 'فصلت', 42 => 'الشورى', 43 => 'الزخرف', 44 => 'الدخان', 45 => 'الجاثية',
            46 => 'الأحقاف', 47 => 'محمد', 48 => 'الفتح', 49 => 'الحجرات', 50 => 'ق',
            51 => 'الذاريات', 52 => 'الطور', 53 => 'النجم', 54 => 'القمر', 55 => 'الرحمن',
            56 => 'الواقعة', 57 => 'الحديد', 58 => 'المجادلة', 59 => 'الحشر', 60 => 'الممتحنة',
            61 => 'الصف', 62 => 'الجمعة', 63 => 'المنافقون', 64 => 'التغابن', 65 => 'الطلاق',
            66 => 'التحريم', 67 => 'الملك', 68 => 'القلم', 69 => 'الحاقة', 70 => 'المعارج',
            71 => 'نوح', 72 => 'الجن', 73 => 'المزمل', 74 => 'المدثر', 75 => 'القيامة',
            76 => 'الإنسان', 77 => 'المرسلات', 78 => 'النبأ', 79 => 'النازعات', 80 => 'عبس',
            81 => 'التكوير', 82 => 'الانفطار', 83 => 'المطففين', 84 => 'الانشقاق', 85 => 'البروج',
            86 => 'الطارق', 87 => 'الأعلى', 88 => 'الغاشية', 89 => 'الفجر', 90 => 'البلد',
            91 => 'الشمس', 92 => 'الليل', 93 => 'الضحى', 94 => 'الشرح', 95 => 'التين',
            96 => 'العلق', 97 => 'القدر', 98 => 'البينة', 99 => 'الزلزلة', 100 => 'العاديات',
            101 => 'القارعة', 102 => 'التكاثر', 103 => 'العصر', 104 => 'الهمزة', 105 => 'الفيل',
            106 => 'قريش', 107 => 'الماعون', 108 => 'الكوثر', 109 => 'الكافرون', 110 => 'النصر',
            111 => 'المسد', 112 => 'الإخلاص', 113 => 'الفلق', 114 => 'الناس'
        ];

        return $surahs[$surahNumber] ?? 'غير محدد';
    }
}
