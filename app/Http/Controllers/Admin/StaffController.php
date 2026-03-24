<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    // الأدمن بيضيف موظف خدمة عملاء للمنصة
    public function addStaff(Request $request)
    {
        // التأكد أن اللي بينفذ الأمر هو الأدمن الأساسي فقط
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'غير مسموح لغير الأدمن بإضافة موظفين'], 403);
        }

        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'phone' => 'nullable|string',
        ]);

        $employee = User::create([
            'name' => $request->name,
            'email' => $request->email,
             'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => 'employee', // هنا بنحدد إنه موظف منصة
            'is_active' => true,
        ]);

        return response()->json(['message' => 'تم إضافة موظف للمنصة بنجاح', 'staff' => $employee]);
    }
    
    // 1. جلب كل موظفي المنصة
public function index()
{
    // التأكد أن الأدمن فقط هو من يرى الموظفين
    if (auth()->user()->role !== 'admin') {
        return response()->json(['message' => 'غير مصرح لك'], 403);
    }

    $staff = User::where('role', 'employee')->latest()->get();

    return response()->json($staff);
}

// 2. تعديل بيانات موظف موجود
public function update(Request $request, $id)
{
    if (auth()->user()->role !== 'admin') {
        return response()->json(['message' => 'غير مصرح لك بتعديل بيانات الموظفين'], 403);
    }

    $employee = User::where('id', $id)->where('role', 'employee')->firstOrFail();

    $request->validate([
        'name' => 'sometimes|string',
        'email' => 'sometimes|email|unique:users,email,' . $id,
        'phone' => 'nullable|string',
        'password' => 'nullable|min:8',
        'is_active' => 'sometimes|boolean'
    ]);

    $data = $request->only(['name', 'email', 'phone', 'is_active']);
    
    // تشفير كلمة المرور في حال تم إرسالها فقط
    if ($request->filled('password')) {
        $data['password'] = Hash::make($request->password);
    }

    $employee->update($data);

    return response()->json([
        'message' => 'تم تحديث بيانات الموظف بنجاح',
        'staff' => $employee
    ]);
}
}