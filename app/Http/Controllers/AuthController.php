<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ProviderProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Notifications\CaseNotification;

class AuthController extends Controller
{
public function updateProfile(Request $request)
{
    $user = auth()->user();

    // التحقق من البيانات
    $validator = Validator::make($request->all(), [
        'name'          => 'sometimes|string|max:255',
        'email'         => 'sometimes|email|unique:users,email,' . $user->id,
        'phone'         => 'sometimes|string|unique:users,phone,' . $user->id,
        'profile_image' => 'sometimes|image|mimes:jpeg,png,jpg,webp|max:2048',
        'password'      => 'sometimes|nullable|min:8|confirmed',
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
    }

    // تحديث النصوص
    $user->fill($request->only(['name', 'email', 'phone']));

    // تحديث كلمة المرور إذا أُرسلت
    if ($request->filled('password')) {
        $user->password = Hash::make($request->password);
    }

    // معالجة رفع الصورة
    if ($request->hasFile('profile_image')) {
        // حذف الصورة القديمة من السيرفر إذا كانت موجودة وليست الصورة الافتراضية
        if ($user->profile_image && \Storage::disk('public')->exists($user->profile_image)) {
            \Storage::disk('public')->delete($user->profile_image);
        }

        // تخزين الصورة الجديدة في مجلد profiles داخل disk public
        $path = $request->file('profile_image')->store('profiles', 'public');
        $user->profile_image = $path;
    }

    $user->save();

    return response()->json([
        'status' => true,
        'message' => 'تم تحديث البيانات بنجاح',
        'user' => $user
    ]);
}
    // 1. تسجيل مستخدم جديد (عميل أو محامي أو مكتب)
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users',
            'phone' => 'required|string|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:client,lawyer,office', // تحديد الدور
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // إنشاء المستخدم
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role' => $request->role,
            'password' => Hash::make($request->password),
        ]);

        // إذا كان المستخدم محامي أو مكتب، ننشئ له بروفايل "فارغ" في انتظار رفع الأوراق
        if (in_array($user->role, ['lawyer', 'office'])) {
            ProviderProfile::create([
                'user_id' => $user->id,
                'status' => 'pending' // حالته معلقة حتى يرفع الأوراق ويقبلها الأدمن
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'تم التسجيل بنجاح',
            'access_token' => $token,
            'user' => $user
        ], 201);
    }

 // 2. تسجيل الدخول المعدل
public function login(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    // جلب المستخدم مع كل العلاقات المحسوبة والبروفايل
    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'بيانات الدخول غير صحيحة'], 401);
    }

    // إبطال التوكنات القديمة
    $user->tokens()->delete();
    $token = $user->createToken('auth_token')->plainTextToken;

    // تحديد حالة البروفايل
    $profileStatus = 'n/a';
    if (in_array($user->role, ['lawyer', 'office'])) {
        // تحميل بيانات البروفايل إذا كان محامي أو مكتب
        $user->load('providerProfile'); 
        $profileStatus = $user->providerProfile ? $user->providerProfile->status : 'pending';
    } 
    elseif (in_array($user->role, ['admin', 'employee'])) {
        $profileStatus = 'verified_admin';
    }

    return response()->json([
        'status' => true,
        'access_token' => $token,
        'token_type' => 'Bearer',
        'user' => $user, // هنا سيعود الكائن كاملاً بكل حقوله وعلاقاته (بما فيها provider_profile)
        'profile_status' => $profileStatus
    ]);
}
    
    // 3. تسجيل الخروج
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'تم تسجيل الخروج بنجاح']);
    }
    public function getNotifications()
    {
        $user = auth()->user();

        return response()->json([
            'unread_count' => $user->unreadNotifications->count(),
            'notifications' => $user->notifications // يعيد آخر الإشعارات
        ]);
    }

    public function markAsRead($id)
    {
        auth()->user()->notifications()->findOrFail($id)->markAsRead();
        return response()->json(['message' => 'تم القراءة']);
    }
}