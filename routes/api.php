<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Provider\ProfileController;
use App\Http\Controllers\Admin\VerificationController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Client\CaseController;
use App\Http\Controllers\Provider\OfferController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\Api\ChatController;

/*
|--------------------------------------------------------------------------
| HomeController Routes 
|--------------------------------------------------------------------------
*/
Route::get('lawyers/{id}', [HomeController::class, 'getLawyerDetails']);
// روت القضايا (بيدعم سيرش وفلتر كـ Query Params)
Route::get('/home/cases', [HomeController::class, 'getAvailableCases']);
// روت المحامين الأعلى تقييماً
Route::get('/home/top-providers', [HomeController::class, 'getTopProviders']);
Route::get('/categories', [App\Http\Controllers\Client\CaseController::class, 'getCategories']);

/*
|--------------------------------------------------------------------------
| notifications Routes 
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
});
/*
|--------------------------------------------------------------------------
| Public Routes (المسارات العامة)
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/provider/{id}/reviews', [CaseController::class, 'getProviderReviews']);
Route::get('reviews/all', [CaseController::class, 'getAllReviews']);

/*
|--------------------------------------------------------------------------
| Protected Routes (المسارات المحمية - تحتاج توكن)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
Route::post('/profile/update', [AuthController::class, 'updateProfile']);
    // --- ملف المستخدم والإشعارات ---
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/notifications', [AuthController::class, 'getNotifications']);
    Route::post('/notifications/{id}/read', [AuthController::class, 'markAsRead']);
      Route::get('support/problem-types', [SupportTicketController::class, 'getProblemTypes']);
    Route::post('support/tickets',      [SupportTicketController::class, 'store']);
    Route::get('support/my-tickets',    [SupportTicketController::class, 'myTickets']);

    /*
    |-- مسارات الأدمن والموظفين (الإدارة) --
    */
    Route::prefix('admin')->group(function () {
            Route::get('support/tickets',            [SupportTicketController::class, 'index']);           // كل التذاكر + فلتر (للأدمن)
    Route::get('support/tickets/{id}',       [SupportTicketController::class, 'show']);            // تذكرة واحدة
        Route::get('/users', [DashboardController::class, 'getAllUsers']);
        // لوحة التحكم (إحصائيات)
        Route::get('/dashboard', [DashboardController::class, 'index']);
Route::get('/verifications', [VerificationController::class, 'index']);
        // توثيق المحامين والمكاتب
        Route::get('/pending-lawyers', [VerificationController::class, 'getPendingRequests']);
        Route::post('/verify-lawyer/{id}', [VerificationController::class, 'verify']);

        // إدارة موظفي المنصة (للأدمن فقط)
       Route::get('/staff', [StaffController::class, 'index']);          // عرض كل الموظفين
    Route::post('/add-staff', [StaffController::class, 'addStaff']);   // إضافة موظف
    Route::post('/staff/{id}', [StaffController::class, 'update']);     // تعديل موظف
    });

    /*
    |-- مسارات العميل (Clients) --
    */
    Route::prefix('client')->group(function () {
        Route::get('/cases', [CaseController::class, 'index']);      // عرض كل القضايا
        Route::get('/stats', [CaseController::class, 'getClientStats']);
    Route::post('/cases/{id}/review', [CaseController::class, 'addReview']);
    Route::get('/cases/{id}', [CaseController::class, 'show']);
        Route::post('/cases', [CaseController::class, 'store']); // نشر قضية
        Route::post('/offers/{id}/accept', [CaseController::class, 'acceptOffer']); // قبول عرض
        Route::patch('/cases/{id}/status', [CaseController::class, 'updateStatus']); // إغلاق أو تعليق
    // جلب عروض قضية معينة
Route::get('/cases/{caseId}/offers', [CaseController::class, 'getOffers']);
        
    });

    /*
    |-- مسارات المحامي والمكتب (Providers) --
    */
    Route::prefix('provider')->group(function () {
        // 1. جلب القضايا التي قدمت عليها عروضاً
    Route::get('/my-offers', [OfferController::class, 'myOffers']);
    // جلب تفاصيل قضية محددة بالـ ID
    Route::get('/cases/{id}', [OfferController::class, 'show']);
    // 2. جلب القضايا التي تم قبول عرضك فيها
    Route::get('/my-accepted-offers', [OfferController::class, 'myAcceptedOffers']);
        // رفع أوراق التوثيق
        Route::post('/upload-docs', [ProfileController::class, 'uploadDocuments']);

        // تصفح القضايا المتاحة
        Route::get('/cases', [OfferController::class, 'index']);
        Route::get('/stats', [OfferController::class, 'getStats']);
        // تقديم عرض سعر
        Route::post('/cases/{caseId}/offers', [OfferController::class, 'store']);
    });
    
    Route::middleware('auth:sanctum')->group(function () {
    Route::get ('chat/conversations',               [ChatController::class, 'conversations']);
    Route::post('chat/conversations/open',          [ChatController::class, 'openConversation']);
    Route::get ('chat/conversations/search',        [ChatController::class, 'search']);
    Route::get ('chat/conversations/{id}/messages', [ChatController::class, 'messages']);
    Route::post('chat/conversations/{id}/messages', [ChatController::class, 'sendMessage']);
    Route::post('chat/conversations/{id}/read',     [ChatController::class, 'markAsRead']);
});

});
