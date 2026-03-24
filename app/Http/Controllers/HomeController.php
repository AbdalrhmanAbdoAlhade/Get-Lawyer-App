<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * جلب القضايا المتاحة مع الفلترة والسيرش
     */
    public function getAvailableCases(Request $request)
    {
        $query = LegalCase::with(['client' => function($q) {
            $q->select('id', 'name', 'profile_image');
        }])
        ->where('status', 'pending') // القضايا اللي لسه محدش قبلها
        ->withCount('offers');

        // 1. سيرش بالاسم أو الوصف
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        // 2. فلتر حسب القسم
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $cases = $query->latest()->paginate(10);

        return response()->json([
            'status' => true,
            'data' => $cases
        ]);
    }

    /**
     * جلب المحامين مرتبين حسب التقييم الأعلى
     */
    public function getTopProviders(Request $request)
{
    $providers = User::where('role', 'lawyer')
        ->where('is_active', true)
        ->with(['providerProfile'])
        ->withCount('reviewsReceived')
        ->get()
        ->makeHidden(['phone', 'email'])
        ->sortByDesc('average_rating')
        ->values();

    return response()->json([
        'status' => true,
        'data' => $providers
    ]);
}
}