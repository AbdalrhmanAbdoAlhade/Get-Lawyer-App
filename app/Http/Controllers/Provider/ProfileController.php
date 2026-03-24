<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function uploadDocuments(Request $request)
    {
        $user = auth()->user();

        // التأكد أن المستخدم محامي أو مكتب فقط
        if (!in_array($user->role, ['lawyer', 'office'])) {
            return response()->json(['message' => 'هذا الإجراء للمحامين والمكاتب فقط'], 403);
        }

        $validator = Validator::make($request->all(), [
            'id_photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'license_photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'personal_photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'iban' => 'required|string|min:15|max:35',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $profile = $user->providerProfile;

        // دالة مساعدة لرفع الملفات وحذف القديم إن وجد
        $profile->id_photo = $this->uploadFile($request->file('id_photo'), 'ids', $profile->id_photo);
        $profile->license_photo = $this->uploadFile($request->file('license_photo'), 'licenses', $profile->license_photo);
        $profile->personal_photo = $this->uploadFile($request->file('personal_photo'), 'personal', $profile->personal_photo);

        $profile->iban = $request->iban;
        $profile->status = 'pending'; // إعادة الحالة للانتظار عند تحديث البيانات
        $profile->save();

        return response()->json([
            'message' => 'تم رفع الأوراق بنجاح، وهي قيد المراجعة الآن',
            'profile' => $profile
        ]);
    }

    private function uploadFile($file, $folder, $oldPath = null)
    {
        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }
        return $file->store('uploads/' . $folder, 'public');
    }
}