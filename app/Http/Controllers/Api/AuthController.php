<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\User;
use App\Events\TeacherLoginEvent;
use App\Events\SupervisorLoginEvent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * تسجيل دخول المعلم
     */
    public function teacherLogin(Request $request): JsonResponse
    {
        $request->validate([
            'identity_number' => 'required|string',
            'password' => 'required|string',
        ]);        $teacher = Teacher::where('identity_number', $request->identity_number)->first();

        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'رقم الهوية غير صحيح'
            ], 401);
        }

        // التحقق من كلمة المرور
        if (!$teacher->checkPassword($request->password)) {
            return response()->json([
                'success' => false,
                'message' => 'كلمة المرور غير صحيحة'
            ], 401);
        }

        // التحقق من حالة المستخدم
        if (!$teacher->is_active_user) {
            return response()->json([
                'success' => false,
                'message' => 'الحساب غير نشط'
            ], 401);
        }        // تحديث آخر تسجيل دخول
        $teacher->updateLastLogin();

        // إطلاق event إشعار تسجيل الدخول
        event(new TeacherLoginEvent($teacher));

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الدخول بنجاح',
            'data' => [
                'user_type' => 'teacher',
                'user_id' => $teacher->id,
                'name' => $teacher->name,
                'identity_number' => $teacher->identity_number,
                'must_change_password' => $teacher->must_change_password,
                'last_login_at' => $teacher->last_login_at,
            ]
        ]);
    }    /**
     * تسجيل دخول الطالب
     */
    public function studentLogin(Request $request): JsonResponse
    {
        $request->validate([
            'identity_number' => 'required|string',
            'password' => 'required|string',
        ]);        $student = Student::where('identity_number', $request->identity_number)->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'رقم الهوية غير صحيح'
            ], 401);
        }

        // التحقق من كلمة المرور
        if (!$student->checkPassword($request->password)) {
            return response()->json([
                'success' => false,
                'message' => 'كلمة المرور غير صحيحة'
            ], 401);
        }

        // التحقق من حالة المستخدم
        if (!$student->is_active_user || !$student->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'الحساب غير نشط'
            ], 401);
        }

        // تحديث آخر تسجيل دخول
        $student->updateLastLogin();        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الدخول بنجاح',
            'data' => [
                'user_type' => 'student',
                'user_id' => $student->id,
                'name' => $student->name,
                'identity_number' => $student->identity_number,
                'must_change_password' => $student->must_change_password,
                'last_login_at' => $student->last_login_at,
            ]
        ]);
    }

    /**
     * تسجيل دخول المشرف
     */
    public function supervisorLogin(Request $request): JsonResponse
    {
        $request->validate([
            'identity_number' => 'required|string',
            'password' => 'required|string',
        ]);

        $supervisor = User::role('supervisor')
            ->where('identity_number', $request->identity_number)
            ->first();

        if (!$supervisor) {
            return response()->json([
                'success' => false,
                'message' => 'رقم الهوية غير صحيح'
            ], 401);
        }

        // التحقق من كلمة المرور
        if (!Hash::check($request->password, $supervisor->password)) {
            return response()->json([
                'success' => false,
                'message' => 'كلمة المرور غير صحيحة'
            ], 401);
        }        // التحقق من حالة المستخدم
        if (!$supervisor->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'الحساب غير نشط'
            ], 401);
        }

        // إطلاق event إشعار تسجيل الدخول للمشرف
        event(new SupervisorLoginEvent($supervisor));

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الدخول بنجاح',
            'data' => [
                'user_type' => 'supervisor',
                'user_id' => $supervisor->id,
                'name' => $supervisor->name,
                'email' => $supervisor->email,
                'identity_number' => $supervisor->identity_number,
                'phone' => $supervisor->phone,
                'is_active' => $supervisor->is_active,
                'circles_count' => $supervisor->circleSupervisors()->active()->count(),
            ]
        ]);
    }

    /**
     * تغيير كلمة مرور المعلم
     */
    public function teacherChangePassword(Request $request): JsonResponse
    {
        $request->validate([
            'identity_number' => 'required|string',
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $teacher = Teacher::where('identity_number', $request->identity_number)->first();

        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'رقم الهوية غير صحيح'
            ], 404);
        }

        if (!$teacher->checkPassword($request->current_password)) {
            return response()->json([
                'success' => false,
                'message' => 'كلمة المرور الحالية غير صحيحة'
            ], 401);
        }

        $teacher->changePassword($request->new_password);

        return response()->json([
            'success' => true,
            'message' => 'تم تغيير كلمة المرور بنجاح'
        ]);
    }    /**
     * تغيير كلمة مرور الطالب
     */
    public function studentChangePassword(Request $request): JsonResponse
    {
        $request->validate([
            'identity_number' => 'required|string',
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $student = Student::where('identity_number', $request->identity_number)->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'رقم الهوية غير صحيح'
            ], 404);
        }

        if (!$student->checkPassword($request->current_password)) {
            return response()->json([
                'success' => false,
                'message' => 'كلمة المرور الحالية غير صحيحة'
            ], 401);
        }

        $student->changePassword($request->new_password);

        return response()->json([
            'success' => true,
            'message' => 'تم تغيير كلمة المرور بنجاح'
        ]);
    }    /**
     * الحصول على معلومات المستخدم
     */
    public function getUserInfo(Request $request): JsonResponse
    {
        $request->validate([
            'user_type' => 'required|in:teacher,student',
            'identity_number' => 'required|string',
        ]);

        if ($request->user_type === 'teacher') {
            $user = Teacher::where('identity_number', $request->identity_number)->first();
        } else {
            $user = Student::where('identity_number', $request->identity_number)->first();
        }

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'المستخدم غير موجود'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user_type' => $request->user_type,
                'user_id' => $user->id,
                'name' => $user->name,
                'identity_number' => $user->identity_number,
                'must_change_password' => $user->must_change_password,
                'is_active_user' => $user->is_active_user,
                'last_login_at' => $user->last_login_at,
                'password_changed_at' => $user->password_changed_at,
            ]
        ]);
    }

    /**
     * تسجيل دخول عام (للمشرفين والمدراء)
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'البريد الإلكتروني غير صحيح'
            ], 401);
        }

        // التحقق من كلمة المرور
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'كلمة المرور غير صحيحة'
            ], 401);
        }

        // التحقق من حالة المستخدم
        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'الحساب غير نشط'
            ], 401);
        }

        // إنشاء token
        $token = $user->createToken('API Token')->plainTextToken;

        // تحديث آخر تسجيل دخول
        $user->update(['last_login_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الدخول بنجاح',
            'token' => $token,
            'data' => [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
                'last_login_at' => $user->last_login_at,
            ]
        ]);
    }
}
