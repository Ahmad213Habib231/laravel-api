<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Mail\SendOtpMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ForgetPasswordController extends Controller
{
    
    public function sendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);
    
        $user = User::where('email', $request->email)->first();
    
        // إنشاء OTP عشوائي
        $otp = rand(100000, 999999);
    
        // تخزين OTP في قاعدة البيانات
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => $otp, 'created_at' => Carbon::now()]
        );
    

        // إرسال OTP عبر البريد الإلكتروني
        Mail::to($user->email)->send(new SendOtpMail($otp));
    
        return response()->json(['message' => 'OTP sent to your email.'], 200);
    }
    

    public function verifyOtpAndResetPassword(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email|exists:users,email',
        'otp' => 'required|digits:6',
        'password' => 'required|min:6|confirmed'
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // التحقق من OTP
    $otpRecord = DB::table('password_reset_tokens')
                   ->where('email', $request->email)
                   ->where('token', $request->otp)
                   ->first();

    if (!$otpRecord) {
        return response()->json(['message' => 'Invalid OTP.'], 400);
    }

    // تحديث كلمة المرور
    $user = User::where('email', $request->email)->first();
    $user->password = Hash::make($request->password);
    $user->save();

    // حذف OTP من قاعدة البيانات
    DB::table('password_reset_tokens')->where('email', $request->email)->delete();

    return response()->json(['message' => 'Password reset successfully.'], 200);
}

}









