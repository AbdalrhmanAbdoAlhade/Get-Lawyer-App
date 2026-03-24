<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\LegalCase;
use App\Models\ProviderProfile;
use App\Models\Transaction;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function getAllUsers(Request $request)
{
    // 1. التأكد من الصلاحية (أدمن أو موظف فقط)
    if (!in_array(auth()->user()->role, ['admin', 'employee'])) {
        return response()->json(['message' => 'غير مصرح لك بالدخول'], 403);
    }

    // 2. بناء الاستعلام
    $query = User::query();

    // فلترة حسب الرول (admin, client, lawyer, office, employee)
    if ($request->has('role') && $request->role != '') {
        $query->where('role', $request->role);
    }

    // فلترة حسب الحالة (نشط أو غير نشط)
    // نستخدم has_active لأن القيمة قد تكون 0 (false)
    if ($request->has('is_active') && $request->is_active !== null) {
        $query->where('is_active', (bool)$request->is_active);
    }

    // فلترة إضافية للبحث بالاسم أو الإيميل أو الهاتف (اختياري لكن مفيد جداً)
    if ($request->has('search') && $request->search != '') {
        $searchTerm = $request->search;
        $query->where(function($q) use ($searchTerm) {
            $q->where('name', 'LIKE', "%{$searchTerm}%")
              ->orWhere('email', 'LIKE', "%{$searchTerm}%")
              ->orWhere('phone', 'LIKE', "%{$searchTerm}%");
        });
    }

    // 3. التنفيذ مع التقسيم (Pagination)
    // تم إضافة with('providerProfile') لجلب بيانات التوثيق إذا كان المستخدم محامي
    $users = $query->with('providerProfile')
                  ->latest()
                  ->paginate(10);

    return response()->json([
        'status' => 'success',
        'filters_applied' => $request->only(['role', 'is_active', 'search']),
        'data' => $users
    ]);
}

    public function index()
    {
        // التأكد من الصلاحية (أدمن أو موظف فقط)
        if (!in_array(auth()->user()->role, ['admin', 'employee'])) {
            return response()->json(['message' => 'غير مصرح لك بالدخول'], 403);
        }

        // إحصائيات سريعة (Stats Widgets)
        $stats = [
            'total_clients' => User::where('role', 'client')->count(),
            'total_lawyers' => User::where('role', 'lawyer')->count(),
            'pending_verifications' => ProviderProfile::where('status', 'pending')->count(),
            'active_cases' => LegalCase::where('status', 'processing')->count(),
            'completed_cases' => LegalCase::where('status', 'completed')->count(),
            'total_revenue' => Transaction::sum('amount'), // إجمالي الأموال المتداولة
        ];

        // آخر القضايا التي تم نشرها
        $recent_cases = LegalCase::with('client')
            ->latest()
            ->take(5)
            ->get();

        // آخر طلبات التوثيق للمحامين والمكاتب
        $latest_verification_requests = ProviderProfile::with('user')
            ->where('status', 'pending')
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'stats' => $stats,
            'recent_cases' => $recent_cases,
            'latest_verification_requests' => $latest_verification_requests,
        ]);
    }
}