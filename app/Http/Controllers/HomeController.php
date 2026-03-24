<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function getLawyerDetails($id)
{
    // جلب المحامي مع البروفايل والتقييمات
    $lawyer = User::with([
        'providerProfile',          // بيانات البروفايل
        'reviewsReceived.client'    // بيانات العميل لكل تقييم
    ])
    ->where('role', 'lawyer')
    ->find($id);

    if (!$lawyer) {
        return response()->json([
            'status' => false,
            'message' => 'المحامي غير موجود'
        ], 404);
    }

    // إعداد البيانات النهائية مع الحقول المحسوبة
    $data = [
        'id' => $lawyer->id,
        'name' => $lawyer->name,
        'profile_image_url' => $lawyer->profile_image_url,
        'average_rating' => $lawyer->average_rating,
        'reviews_count' => $lawyer->reviews_count,
        'is_active' => $lawyer->is_active,
        'provider_profile' => $lawyer->providerProfile,
        'reviews' => $lawyer->reviewsReceived->map(function($review) {
            return [
                'id' => $review->id,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'client' => [
                    'id' => $review->client->id,
                    'name' => $review->client->name,
                    'profile_image_url' => $review->client->profile_image_url
                ]
            ];
        })
    ];

    return response()->json([
        'status' => true,
        'data' => $data
    ]);
}

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
    $perPage = $request->input('per_page', 5);

    // جلب المحامين مع البروفايل وعدد التقييمات
    $providers = User::where('role', 'lawyer')
        ->where('is_active', true)
        ->with(['providerProfile'])
        ->withCount('reviewsReceived')
        ->paginate($perPage);

    // ترتيب حسب average_rating بعد جلب الـ collection
    $providers->getCollection()->transform(function ($provider) {
        // اخفاء البيانات الحساسة
        return $provider->makeHidden(['phone', 'email']);
    });

    // إذا تحب تقدر ترتب حسب average_rating بعد الـ transform
    $sorted = $providers->getCollection()->sortByDesc('average_rating')->values();
    $providers->setCollection($sorted);

    return response()->json([
        'status' => true,
        'data' => $providers
    ]);
}

}
