<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;





class AuthController extends Controller {

    public function register(Request $request) {
        $validator = Validator::make($request->all(), [
           'first_name' => 'required|string',
           'last_name' => 'required|string',
           'email' => 'required|email|unique:users',
           'password' => 'required|min:6|confirmed',
        ]);
    
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
    
        // استخدم $request->only للحصول على البيانات بعد التحقق
        $data = $request->only('first_name', 'last_name', 'email', 'password');
    
        // إنشاء المستخدم
        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    
        // إنشاء التوكن
        $token = $user->createToken('auth_token')->plainTextToken;
    
        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $token 
        ], 201);
    }
    
    
    public function login(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $user = Auth::user();

        // ✅ إنشاء التوكن بعد تسجيل الدخول
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token, // ✅ إرجاع التوكن في الاستجابة
            'message' => 'Login successful'
        ]);
    }

    



    public function redirectToGoogle() {
        return Socialite::driver('google')->stateless()->redirect();
    }



    public function handleGoogleCallback() {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            Log::info('Google User: ', (array) $googleUser); // ✅ تسجيل بيانات Google
    
            $user = User::where('provider_id', $googleUser->getId())
                ->orWhere('email', $googleUser->getEmail())
                ->first();
    
            if (!$user) {
                $user = User::create([
                    'email' => $googleUser->getEmail(),
                    'first_name' => explode(' ', $googleUser->getName())[0],
                    'last_name' => explode(' ', $googleUser->getName())[1] ?? '',
                    'provider' => 'google',
                    'provider_id' => $googleUser->getId(),
                ]);
            }
    
            Auth::login($user);
            $token = $user->createToken('auth_token')->plainTextToken;
    
            return response()->json([
                'user' => $user,
                'token' => $token,
                'message' => 'Login with Google successful'
            ]);
        } catch (\Exception $e) {
            Log::error('Google Authentication Error: ' . $e->getMessage()); // ✅ تسجيل الخطأ
            return response()->json(['error' => 'Authentication failed', 'details' => $e->getMessage()], 500);
        }
    }
    


   // ✅ 1️⃣ توجيه المستخدم إلى Facebook
public function redirectToFacebook() {
    return Socialite::driver('facebook')->stateless()->redirect();
}

// ✅ 2️⃣ معالجة استجابة Facebook بعد تسجيل الدخول
public function handleFacebookCallback() {
    try {
        $facebookUser = Socialite::driver('facebook')->stateless()->user();

        // ✅ البحث عن المستخدم في قاعدة البيانات
        $user = User::where('facebook_id', $facebookUser->id)->orWhere('email', $facebookUser->email)->first();

        // ✅ إذا لم يكن المستخدم موجودًا، يتم إنشاؤه
        if (!$user) {
            $user = User::create([
                ['email' => $facebookUser->getEmail()],
                    [
                      'first_name' => explode(' ', $facebookUser->getName())[0],
                      'last_name' => explode(' ', $facebookUser->getName())[1] ?? '',
                      'provider' => 'google',
                      'provider_id' => $facebookUser->getId(),
                    ]  
            ]);
        }

        // ✅ تسجيل الدخول وإنشاء توكن
        Auth::login($user);
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'message' => 'Login with Facebook successful'
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Authentication failed'], 500);
    }
}







  

    public function logout(Request $request) {
        $user = Auth::user();
    
        if ($user) {
            // ✅ حذف جميع التوكنات الخاصة بالمستخدم
            $user->tokens()->delete();
    
            return response()->json(['message' => 'Logged out successfully'], 200);
        }
    
        return response()->json(['error' => 'Unauthorized'], 401);
    }



 


    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        // التحقق من البيانات المدخلة
        $request->validate([
            'name' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        // تحديث البيانات إذا تم إدخالها
        if ($request->has('name')) {
            $user->name = $request->name;
        }

        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json(['message' => 'تم تحديث الملف الشخصي بنجاح', 'user' => $user]);
    }

















    
}
