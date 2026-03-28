<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\LegalCase;
use App\Models\Offer;
use Illuminate\Http\Request;
use App\Notifications\CaseNotification;

class OfferController extends Controller
{
    /**
 * جلب إحصائيات المحامي للوحة التحكم (Dashboard)
 */
public function getStats()
{
    $user = auth()->user();

    // 1. بانتظار العروض: قضايا متاحة في النظام المحامي لسه ملمسهاش
    $pendingOpportunities = LegalCase::where('status', 'pending')
        ->whereDoesntHave('offers', function($q) use ($user) {
            $q->where('provider_id', $user->id);
        })->count();

    // 2. عروض مقدمة: عروض المحامي اللي لسه العميل مخدش فيها قرار
    $myActiveOffersCount = Offer::where('provider_id', $user->id)
        ->whereHas('legalCase', function($q) {
            $q->where('status', 'pending');
        })->count();

    // 3. قضايا قيد التنفيذ: اللي العميل وافق عليها وشغالين فيها دلوقتي
    $ongoingCasesCount = LegalCase::where('accepted_provider_id', $user->id)
        ->where('status', 'processing')
        ->count();

    // 4. قضايا مكتملة: الشغل اللي خلص واتقفل (الإنجازات)
    $completedCasesCount = LegalCase::where('accepted_provider_id', $user->id)
        ->where('status', 'completed')
        ->count();

    return response()->json([
        'status' => true,
        'data' => [
            'pending_opportunities' => $pendingOpportunities, // بانتظار العروض
            'active_offers'         => $myActiveOffersCount,  // عروض متاحة (مقدمة)
            'ongoing_cases'         => $ongoingCasesCount,   // تم التعاقد (قيد التنفيذ)
            'completed_cases'       => $completedCasesCount, // قضايا مكتملة ✅
        ]
    ]);
}
   // 1. عرض القضايا المتاحة للمحامين (Pending فقط) مع بيانات العميل
  public function index(Request $request)
{
    // نستخدم with لجلب علاقة العميل (client)
    // لاحظ استخدام profile_image بدلاً من avatar بناءً على موديل User الخاص بك
    $query = LegalCase::with(['client' => function($q) {
        $q->select('id', 'name', 'profile_image');
    }])
    ->withCount('offers') // مفيد جداً للمحامي لمعرفة عدد العروض الحالية
    ->where('status', 'pending');

    // فلترة حسب القسم إذا لزم الأمر
    if ($request->filled('category')) {
        $query->where('category', $request->category);
    }

    $cases = $query->latest()->get();

    return response()->json([
        'status' => true,
        'data' => $cases
    ]);
}

    // 2. تقديم عرض سعر على قضية
   public function store(Request $request, $caseId)
{
    $user = auth()->user();

    // 1. التأكد من التوثيق
    if (!$user->providerProfile || $user->providerProfile->status !== 'approved') {
        return response()->json([
            'message' => 'عذراً، يجب توثيق حسابك من قبل الإدارة أولاً لتتمكن من تقديم عروض.'
        ], 403);
    }

    // 2. التحقق من المدخلات باستخدام Validator::make لضمان الحصول على كائن $validator
    $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
        'offered_price' => 'required|numeric|min:1',
        'proposal_text' => 'required|string|min:20',
        'attachments.*' => 'nullable|file|mimes:pdf,jpg,png,docx|max:20000',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'errors' => $validator->errors()
        ], 422);
    }

    // 3. التحقق من وجود القضية وصلاحيتها
    $case = LegalCase::findOrFail($caseId);

    if ($case->status !== 'pending') {
        return response()->json(['message' => 'هذه القضية لم تعد تستقبل عروضاً'], 422);
    }

    // منع تكرار العرض
    if (Offer::where('case_id', $caseId)->where('provider_id', $user->id)->exists()) {
        return response()->json(['message' => 'لقد قدمت عرضاً بالفعل على هذه القضية'], 422);
    }

    // 4. معالجة رفع الملفات
    $filePaths = [];
    if ($request->hasFile('attachments')) {
        foreach ($request->file('attachments') as $file) {
            $filePaths[] = $file->store('offers/attachments', 'public');
        }
    }

    // 5. إنشاء العرض
    $offer = Offer::create([
        'case_id'       => $caseId,
        'provider_id'   => $user->id,
        'offered_price' => $request->offered_price,
        'proposal_text' => $request->proposal_text,
        'attachments'   => $filePaths,
    ]);

    // 6. إرسال الإشعار للعميل
    $client = $case->client;
    if ($client) {
        $client->notify(new \App\Notifications\CaseNotification([
            'title'   => 'عرض جديد',
            'message' => 'قام محامي بتقديم عرض سعر جديد على قضيتك: ' . $case->title,
            'case_id' => $case->id,
            'type'    => 'offer_received'
        ]));
    }

    return response()->json([
        'status'  => true,
        'message' => 'تم تقديم عرضك بنجاح', 
        'offer'   => $offer
    ], 201);
}

/**
     * 3. جلب القضايا التي قدم المحامي عروضاً عليها
     */
    public function myOffers(Request $request)
    {
        $user = auth()->user();

        $cases = LegalCase::whereHas('offers', function($q) use ($user) {
            $q->where('provider_id', $user->id);
        })
        ->with([
            'client' => function($q) {
                $q->select('id', 'name', 'profile_image');
            },
            'offers' => function($q) use ($user) {
                // نجلب عرض المحامي الحالي فقط لعرض تفاصيله (السعر، النص، الخ..)
                $q->where('provider_id', $user->id);
            }
        ])
        ->withCount('offers') // جلب عدد العروض الكلي على القضية
        ->latest()
        ->get();

        return response()->json([
            'status' => true,
            'data' => $cases
        ]);
    }

    /**
     * 4. جلب القضايا التي تم قبول عرض المحامي فيها
     */
  public function myAcceptedOffers(Request $request)
{
    $user = auth()->user();

    $cases = LegalCase::where('accepted_provider_id', $user->id)
        ->whereIn('status', ['processing'])
        ->with([
            'client' => function($q) {
                $q->select('id', 'name', 'profile_image');
            },
            'offers' => function($q) use ($user) {
                // نجيب عرض المحامي فقط
                $q->where('provider_id', $user->id);
            }
        ])
        ->withCount('offers')
        ->latest()
        ->get();

    return response()->json([
        'status' => true,
        'data' => $cases
    ]);
}
/**
 * 5. عرض تفاصيل قضية بعينها
 */
public function show($id)
{
    $user = auth()->user();

    // جلب القضية مع بيانات العميل وتاريخ العروض
    $case = LegalCase::with(['client' => function($q) {
        $q->select('id', 'name', 'profile_image');
    }])
    ->withCount('offers')
    ->find($id);

    // التأكد من وجود القضية
    if (!$case) {
        return response()->json([
            'status' => false,
            'message' => 'عذراً، القضية غير موجودة'
        ], 404);
    }

    // (اختياري) لو عايز المحامي يشوف العرض بتاعه هو فقط لو كان قدم عرض سابقاً على دي القضية
    $myOffer = Offer::where('case_id', $id)
                    ->where('provider_id', $user->id)
                    ->first();

    return response()->json([
        'status' => true,
        'data' => [
            'case' => $case,
            'my_offer' => $myOffer // هيرجع null لو لسه مقدمش عرض
        ]
    ]);
} 

}