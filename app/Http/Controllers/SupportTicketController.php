<?php
namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class SupportTicketController extends Controller
{
    // جلب أنواع المشاكل للـ dropdown
    public function getProblemTypes()
    {
        $types = collect(SupportTicket::$problemTypes)
            ->map(fn($label, $value) => ['value' => $value, 'label' => $label])
            ->values();

        return response()->json([
            'status' => true,
            'data'   => $types,
        ]);
    }

    // إرسال التذكرة
    public function store(Request $request)
    {
        $request->validate([
            'problem_type'  => 'required|in:' . implode(',', array_keys(SupportTicket::$problemTypes)),
            'title'         => 'required|string|max:255',
            'description'   => 'required|string',
            'attachments'   => 'nullable|array|max:5',
            'attachments.*' => 'file|mimes:png,jpg,jpeg|max:5120', // 5MB
        ], [
            'problem_type.required'  => 'نوع المشكلة مطلوب',
            'title.required'         => 'عنوان الرسالة مطلوب',
            'description.required'   => 'وصف الرسالة مطلوب',
            'attachments.*.mimes'    => 'المرفقات يجب أن تكون PNG أو JPG فقط',
            'attachments.*.max'      => 'حجم الملف يجب ألا يتجاوز 5MB',
        ]);

        DB::beginTransaction();
        try {
            // إنشاء التذكرة
            $ticket = SupportTicket::create([
                'user_id'      => auth()->id(),
                'problem_type' => $request->problem_type,
                'title'        => $request->title,
                'description'  => $request->description,
            ]);

            // رفع المرفقات لو موجودة
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('support_tickets/' . $ticket->id, 'public');

                    SupportTicketAttachment::create([
                        'support_ticket_id' => $ticket->id,
                        'file_path'         => $path,
                        'file_name'         => $file->getClientOriginalName(),
                        'file_size'         => $file->getSize(),
                        'mime_type'         => $file->getMimeType(),
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'تم إرسال طلب المساعدة بنجاح',
                'data'    => $ticket->load('attachments'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ أثناء الإرسال',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // تذاكر المستخدم الحالي
    public function myTickets()
    {
        $tickets = SupportTicket::where('user_id', auth()->id())
            ->with('attachments')
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data'   => $tickets,
        ]);
    }
    
    // كل التذاكر مع فلتر الحالة (للأدمن أو للمستخدم)
public function index(Request $request)
{
    // ❌ منع أي حد مش admin
    if (auth()->user()->role !== 'admin') {
        return response()->json([
            'status' => false,
            'message' => 'Unauthorized'
        ], 403);
    }

    $query = SupportTicket::with(['attachments', 'user'])
        ->latest();

    if ($request->filled('status')) {
        $request->validate([
            'status' => 'in:pending,in_progress,resolved,closed'
        ]);
        $query->where('status', $request->status);
    }

    if ($request->filled('problem_type')) {
        $query->where('problem_type', $request->problem_type);
    }

    $tickets = $query->paginate($request->get('per_page', 10));

    return response()->json([
        'status' => true,
        'data'   => $tickets,
    ]);
}

// تذكرة واحدة
public function show($id)
{
    // ❌ منع أي حد مش admin
    if (auth()->user()->role !== 'admin') {
        return response()->json([
            'status' => false,
            'message' => 'Unauthorized'
        ], 403);
    }

    $ticket = SupportTicket::with(['attachments', 'user'])
        ->findOrFail($id);

    return response()->json([
        'status' => true,
        'data'   => $ticket,
    ]);
}

}