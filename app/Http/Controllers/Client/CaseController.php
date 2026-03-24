<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\LegalCase;
use App\Models\Offer;
use App\Models\User;
use App\Models\Review; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Notifications\CaseNotification;

class CaseController extends Controller
{
   // تعريف الأقسام هنا مباشرة
    private const CATEGORIES = [
        'Criminal',
        'Civil',
        'Commercial',
        'Family Law',
        'Administrative',
        'Labor',
        'Real Estate',
    'Financial'
    ];

    public function getCategories()
    {
        return response()->json([
            'status' => true,
            'data' => self::CATEGORIES
        ]);
    }
    /**
 * جلب إحصائيات العميل للوحة التحكم
 */
public function getClientStats()
{
    $clientId = auth()->id();

    // 1. القضايا التي تنتظر عروض (Pending)
    $pendingCases = LegalCase::where('client_id', $clientId)
        ->where('status', 'pending')
        ->count();

    // 2. قضايا قيد التنفيذ (Processing) - يعني تم اختيار محامي
    $ongoingCases = LegalCase::where('client_id', $clientId)
        ->where('status', 'processing')
        ->count();

    // 3. قضايا مكتملة (Completed)
    $completedCases = LegalCase::where('client_id', $clientId)
        ->where('status', 'completed')
        ->count();

    return response()->json([
        'status' => true,
        'data' => [
            'pending_cases'   => $pendingCases,   // قضايا بانتظار عروض
            'ongoing_cases'   => $ongoingCases,   // قضايا قيد التنفيذ
            'completed_cases' => $completedCases, // قضايا مكتملة
        ]
    ]);
}
    // 5. جلب كل قضايا العميل الحالي
public function index(Request $request)
{
    $query = LegalCase::where('client_id', auth()->id())
        ->withCount('offers'); // عشان العميل يعرف كل قضية جالها كام عرض

    // فلترة اختيارية حسب الحالة (pending, processing, completed)
    if ($request->has('status')) {
        $query->where('status', $request->status);
    }

    $cases = $query->latest()->get();

    return response()->json($cases);
}

// 6. عرض تفاصيل قضية واحدة مع بيانات المحامي المقبول
public function show($id)
{
    // 1. جلب القضية مع العلاقات المطلوبة
    $case = LegalCase::with(['acceptedProvider.providerProfile', 'client:id,name'])
        ->findOrFail($id);

    // 2. التحقق من الصلاحية (Access Control)
    $isClient = $case->client_id === auth()->id();
    $isAcceptedLawyer = $case->accepted_provider_id === auth()->id();

    if (!$isClient && !$isAcceptedLawyer) {
        return response()->json([
            'message' => 'غير مصرح لك برؤية تفاصيل هذه القضية'
        ], 403); // Error 403: Forbidden
    }

    return response()->json($case);
}

   // 1. العميل ينشر قضية جديدة
    public function store(Request $request)
    {
        // تصحيح: إسناد عملية التحقق لمتغير $validator
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|string', // جنائي، مالي، إلخ
            'initial_budget' => 'required|numeric|min:0',
            'attachments.*' => 'nullable|file|mimes:pdf,jpg,png,docx,zip|max:20000', // حد أقصى لكل ملف تقريباً 20MB
        ]);

        // الآن هذا الشرط سيعمل بشكل صحيح
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $filePaths = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                // تخزين الملفات في مجلد خاص بالقضايا داخل الـ public disk
                $filePaths[] = $file->store('cases/attachments', 'public');
            }
        }

        $case = LegalCase::create([
            'client_id' => auth()->id(),
            'title' => $request->title,
            'description' => $request->description,
            'category' => $request->category,
            'initial_budget' => $request->initial_budget,
            'status' => 'pending',
            'attachments' => $filePaths, // سيتم تحويلها لـ JSON تلقائياً بسبب cast array في الموديل
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم نشر القضية بنجاح', 
            'case' => $case
        ], 201);
    }

    // 2. العميل يقبل عرض محامي معين
public function acceptOffer($offerId)
{
    // جلب العرض مع بيانات المحامي (Provider)
    $offer = Offer::with(['legalCase', 'provider.providerProfile'])->findOrFail($offerId);
    $case = $offer->legalCase;

    if ($case->client_id !== auth()->id() || $case->status !== 'pending') {
        return response()->json(['message' => 'لا يمكنك قبول هذا العرض'], 403);
    }

    DB::transaction(function () use ($case, $offer) {
        // تحديث حالة القضية وتعيين المحامي المقبول
        $case->update([
            'status' => 'processing',
            'accepted_provider_id' => $offer->provider_id
        ]);

        // إرسال الإشعار للمحامي
        $offer->provider->notify(new CaseNotification([
            'title' => 'تم قبول عرضك',
            'message' => 'مبروك! اختارك العميل للبدء في قضية: ' . $case->title,
            'case_id' => $case->id,
            'type' => 'case_accepted'
        ]));
    });

    return response()->json([
        'message' => 'تم قبول العرض بنجاح',
        'accepted_lawyer' => [
            'name'  => $offer->provider->name,
            'phone' => $offer->provider->phone,
            'email' => $offer->provider->email,
            'bio'   => $offer->provider->providerProfile->bio ?? 'لا يوجد وصف',
        ]
    ]);
}

 /**
     * 3. العميل يغير حالة القضية (إغلاق أو إعادة فتح)
     */
    public function updateStatus(Request $request, $caseId)
    {
        $request->validate([
            'status' => 'required|in:completed,unresolved'
        ]);

        $case = LegalCase::findOrFail($caseId);

        if ($case->client_id !== auth()->id()) {
            return response()->json(['message' => 'غير مصرح لك'], 403);
        }

        if ($request->status == 'completed') {
            $case->update(['status' => 'completed']);
            return response()->json(['message' => 'تم إغلاق القضية بنجاح، يمكنك الآن تقييم المحامي']);
        }

        if ($request->status == 'unresolved') {
            $case->update([
                'status' => 'pending',
                'accepted_provider_id' => null
            ]);
            return response()->json(['message' => 'تمت إعادة القضية للحالة المعلقة']);
        }
    }

    /**
     * 7. تقييم المحامي بعد انتهاء القضية
     */
    public function addReview(Request $request, $caseId)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500'
        ]);

        $case = LegalCase::findOrFail($caseId);

        // التأكد أن العميل هو صاحب القضية، وأن القضية مكتملة، ولها محامي مقبول
        if ($case->client_id !== auth()->id() || $case->status !== 'completed' || !$case->accepted_provider_id) {
            return response()->json(['message' => 'لا يمكنك تقييم هذه القضية حالياً'], 403);
        }

        // التأكد من عدم وجود تقييم مسبق لنفس القضية
        $exists = Review::where('case_id', $caseId)->exists();
        if ($exists) {
            return response()->json(['message' => 'لقد قمت بتقييم هذه القضية بالفعل'], 400);
        }

        $review = Review::create([
            'case_id' => $case->id,
            'client_id' => auth()->id(),
            'provider_id' => $case->accepted_provider_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json([
            'message' => 'شكرًا لك! تم إضافة تقييمك بنجاح',
            'review' => $review
        ]);
    }
    
    
    // 7. جلب تقييمات محامي معين وحساب المتوسط (هذه الدالة التي طلبتها)
    public function getProviderReviews($providerId)
    {
        $provider = User::where('id', $providerId)
                        ->whereIn('role', ['lawyer', 'office'])
                        ->firstOrFail();

        // سيتم جلب الـ average_rating و reviews_count تلقائياً بفضل الـ $appends في موديل User
        $reviews = Review::with('client:id,name')
                         ->where('provider_id', $providerId)
                         ->latest()
                         ->get();

        return response()->json([
            'provider_name' => $provider->name,
            'average_rating' => $provider->average_rating,
            'total_reviews' => $provider->reviews_count,
            'reviews' => $reviews
        ]);
    }
    
     // 3. العميل يرى العروض المقدمة على قضية معينة مع تقييمات المحامين
public function getOffers($caseId)
    {
        $case = LegalCase::where('id', $caseId)
                         ->where('client_id', auth()->id())
                         ->firstOrFail();

        // بفضل $with = ['providerProfile'] في موديل User، سيتم جلب البروفايل تلقائياً
        $offers = Offer::with([
            'provider' => function($query) {
                $query->select('id', 'name', 'phone', 'email', 'profile_image');
            }
        ])
        ->where('case_id', $caseId)
        ->latest()
        ->get();

        return response()->json([
            'case_title' => $case->title,
            'attachments' => $case->attachment_urls, // المرفقات التي رفعها العميل
            'offers' => $offers
        ]);
    }
    
        /**
     * 7. جلب كل التقييمات الموجودة في النظام (الدالة الجديدة)
     */
    public function getAllReviews()
    {
        $reviews = Review::with(['client:id,name', 'provider:id,name'])
                         ->latest()
                         ->paginate(20);

        return response()->json($reviews);
    }

}