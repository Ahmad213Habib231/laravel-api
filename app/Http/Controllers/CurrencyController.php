<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator; 
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use App\Models\Currency;
use App\Models\UserScan;

class CurrencyController extends Controller
{
    public function uploadAndDetect(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|mimes:jpg,jpeg,png|max:4096',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Fix errors',
                'errors' => $validator->errors()
            ], 422);
        }

        // رفع الصورة إلى Cloudinary
        $uploadedFile = $request->file('image');
        $cloudinaryUpload = Cloudinary::upload($uploadedFile->getRealPath(), [
            'folder' => 'currency_uploads'
        ]);

        $secureUrl = $cloudinaryUpload->getSecurePath();

        // تخزين بيانات الصورة في قاعدة البيانات
        $currency = new Currency;
        $currency->image = basename($secureUrl);
        $currency->image_path = $secureUrl;
        $currency->image_url = $secureUrl;
        $currency->save();

        // تحميل الصورة مؤقتًا من Cloudinary كـ stream لإرسالها إلى Flask
        $imageStream = fopen($secureUrl, 'r');

        $detections = $this->runDetectionFromStream($imageStream, basename($secureUrl));

        // إذا المستخدم مسجل دخول، يتم تسجيل عملية المسح
        if (Auth::check()) {
            UserScan::create([
                'user_id' => Auth::id(),
                'currency_id' => $currency->id,
                'recognized_at' => now(),
                'accuracy' => $detections['accuracy'] ?? 0,
                'image_url' => $secureUrl,
                'result' => $detections['result'] ?? 'unknown',
            ]);
        }

        return response()->json([
            'status' => $detections['status'] ?? false,
            'message' => $detections['message'] ?? 'Detection completed',
            'data' => $detections
        ]);
    }

    private function runDetectionFromStream($stream, $filename)
    {
        $flaskApiUrl = 'https://5b27-34-143-137-214.ngrok-free.app/detect';

        try {
            $response = Http::attach(
                'image',
                $stream,
                $filename
            )->post($flaskApiUrl);

            if ($response->successful()) {
                return $response->json();
            } else {
                return [
                    'status' => false,
                    'message' => 'Flask API error',
                    'error' => $response->body(),
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Exception when calling Flask API',
                'error' => $e->getMessage(),
            ];
        }
    }
}
