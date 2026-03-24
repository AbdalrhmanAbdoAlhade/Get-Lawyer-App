<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // 1. جلب كل الإشعارات للمستخدم
    public function index()
    {
        $notifications = auth()->user()->notifications()->paginate(5);
        
        return response()->json([
            'status' => true,
            'notifications' => $notifications
        ]);
    }

    // 2. جعل إشعار معين "مقروء"
    public function markAsRead($id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json([
            'status' => true,
            'message' => 'Notification marked as read'
        ]);
    }

    // 3. جعل كل الإشعارات "مقروءة" (مفيدة جداً للموبايل)
    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
        
        return response()->json([
            'status' => true,
            'message' => 'All notifications marked as read'
        ]);
    }
}