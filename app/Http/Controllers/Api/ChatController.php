<?php
namespace App\Http\Controllers\Api;

use App\Models\Conversation;
use App\Models\Message;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    // قائمة المحادثات
    public function conversations()
    {
        $authId = auth()->id();

        $conversations = Conversation::with(['lastMessage', 'client', 'lawyer'])
            ->where('client_id', $authId)
            ->orWhere('lawyer_id', $authId)
            ->orderByDesc('last_message_at')
            ->get()
            ->map(fn($conv) => [
                'id'              => $conv->id,
                'other_user'      => tap($conv->otherParticipant($authId), fn($u) => [
                    'id'                => $u->id,
                    'name'              => $u->name,
                    'profile_image_url' => $u->profile_image_url,
                ]),
                'last_message'    => $conv->lastMessage?->body,
                'last_message_at' => $conv->last_message_at,
                'unread_count'    => $conv->unreadCount($authId),
            ]);

        return response()->json(['status' => true, 'data' => $conversations]);
    }

    // فتح أو إنشاء محادثة
    public function openConversation(Request $request)
    {
        $request->validate(['lawyer_id' => 'required|exists:users,id']);

        $conversation = Conversation::firstOrCreate(
            ['client_id' => auth()->id(), 'lawyer_id' => $request->lawyer_id],
            ['last_message_at' => now()]
        );

        return response()->json(['status' => true, 'data' => $conversation]);
    }

    // رسائل محادثة - Flutter هيعمل polling على الـ endpoint ده
    public function messages(Request $request, $conversationId)
    {
        $conversation = $this->findConversation($conversationId);

        // تعليم الرسائل الواردة كمقروءة
        $conversation->messages()
            ->where('sender_id', '!=', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messages = $conversation->messages()
            ->with('sender:id,name,profile_image')
            ->when($request->filled('after_id'), // الـ Flutter يبعت آخر id عنده
                fn($q) => $q->where('id', '>', $request->after_id)
            )
            ->latest()
            ->paginate($request->get('per_page', 20));

        return response()->json(['status' => true, 'data' => $messages]);
    }

    // إرسال رسالة
    public function sendMessage(Request $request, $conversationId)
    {
        $request->validate(['body' => 'required|string|max:2000']);

        $conversation = $this->findConversation($conversationId);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => auth()->id(),
            'body'            => $request->body,
        ]);

        $conversation->update(['last_message_at' => now()]);

        return response()->json([
            'status' => true,
            'data'   => $message->load('sender'),
        ], 201);
    }

    // تعليم كمقروء ✓✓
    public function markAsRead($conversationId)
    {
        $conversation = $this->findConversation($conversationId);

        $updated = $conversation->messages()
            ->where('sender_id', '!=', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'status'  => true,
            'message' => "تم تعليم {$updated} رسالة كمقروءة",
        ]);
    }

    // بحث في المحادثات
    public function search(Request $request)
    {
        $request->validate(['q' => 'required|string|min:2']);
        $authId = auth()->id();

        $conversations = Conversation::with(['client', 'lawyer', 'lastMessage'])
            ->where(fn($q) => $q->where('client_id', $authId)->orWhere('lawyer_id', $authId))
            ->where(fn($q) => $q
                ->whereHas('client', fn($q) => $q->where('name', 'like', "%{$request->q}%"))
                ->orWhereHas('lawyer', fn($q) => $q->where('name', 'like', "%{$request->q}%"))
            )
            ->get()
            ->map(fn($conv) => [
                'id'           => $conv->id,
                'other_user'   => $conv->otherParticipant($authId),
                'last_message' => $conv->lastMessage?->body,
            ]);

        return response()->json(['status' => true, 'data' => $conversations]);
    }

    // helper مشترك
    private function findConversation($id): Conversation
    {
        return Conversation::where('id', $id)
            ->where(fn($q) => $q
                ->where('client_id', auth()->id())
                ->orWhere('lawyer_id', auth()->id())
            )->firstOrFail();
    }
}