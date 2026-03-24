<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProviderProfile;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    // عرض كل الطلبات مع إمكانية الفلترة حسب الحالة
public function index(Request $request)
{
    // الحصول على الحالة من الرابط (مثلاً: ?status=approved)
    $status = $request->query('status');

    // بناء الاستعلام الأساسي مع علاقة المستخدم
    $query = ProviderProfile::with('user');

    // إذا تم إرسال حالة معينة، قم بالفلترة بناءً عليها
    if ($status && in_array($status, ['pending', 'approved', 'rejected'])) {
        $query->where('status', $status);
    }

    // جلب البيانات (يمكنك إضافة paginate للحصول على أداء أفضل)
    $requests = $query->latest()->get();

    return response()->json($requests);
}
    // عرض كل الطلبات المعلقة للأدمن
    public function getPendingRequests()
    {
        $requests = ProviderProfile::with('user')->where('status', 'pending')->get();
        return response()->json($requests);
    }

    // اتخاذ قرار (قبول أو رفض)
    public function verify(Request $request, $id)
    {
        // السماح للأدمن والموظف فقط بالدخول
        if (!in_array(auth()->user()->role, ['admin', 'employee'])) {
            return response()->json(['message' => 'ليس لديك صلاحية مراجعة الأوراق'], 403);
        }
        $request->validate([
            'status' => 'required|in:approved,rejected,pending',
            'admin_notes' => 'required_if:status,rejected' // السبب إجباري في حالة الرفض
        ]);

        $profile = ProviderProfile::findOrFail($id);
        $profile->update([
            'status' => $request->status,
            'admin_notes' => $request->admin_notes
        ]);

        // هنا يمكنك إرسال إشعار (Notification) للمحامي بالنتيجة

        return response()->json(['message' => 'تم تحديث حالة الطلب بنجاح']);
    }
}