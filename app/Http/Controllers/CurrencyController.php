<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator; 
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\Currency;
use App\Models\UserScan;

class CurrencyController extends Controller
{
    public function uploadAndDetect(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|mimes:jpg,jpeg,png',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Fix errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $img = $request->file('image');
        $imageName = time() . '.' . $img->getClientOriginalExtension();

        // استدعاء Flask API مباشرة باستخدام ملف الصورة من الطلب
        $detections = $this->runDetectionFromFile($img, $imageName);

        // تعديل الاسم داخل النتيجة بحذف EGP أو egp (أو أي حالة أحرف أخرى)
        if (isset($detections['result']) && is_array($detections['result'])) {
            foreach ($detections['result'] as &$item) {
                if (isset($item['name'])) {
                    $item['name'] = str_ireplace('egp', '', $item['name']);
                    $item['name'] = trim($item['name']);
                }
            }
        }

        // تخزين البيانات في قاعدة البيانات بدون رفع صورة محلياً
        $currency = new Currency;
        $currency->image = $imageName;
        $currency->image_path = null; // لا يوجد مسار محلي
        $currency->image_url = null;  // لا يوجد رابط محلي
        $currency->save();

        // إذا المستخدم مسجل دخول، سجل عملية المسح بدون نسخ صورة
        if (Auth::check()) {
            UserScan::create([
                'user_id' => Auth::id(),
                'currency_id' => $currency->id,
                'recognized_at' => now(),
                'accuracy' => $detections['accuracy'] ?? 0,
                'image_url' => null,
                'result' => $detections['result'] ?? 'unknown',
            ]);
        }

        return response()->json([
            'status' => $detections['status'] ?? false,
            'message' => $detections['message'] ?? 'Detection completed',
            'data' => $detections
        ]);
    }

    private function runDetectionFromFile($file, $filename)
    {
        $flaskApiUrl = 'https://c7ab-35-245-170-42.ngrok-free.app/detect';

        try {
            $response = Http::attach(
                'image',
                fopen($file->getRealPath(), 'r'),
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
