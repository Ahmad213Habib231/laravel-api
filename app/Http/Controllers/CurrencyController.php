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

        // حفظ الصورة في public/uploads
        $img = $request->file('image');
        $ext = $img->getClientOriginalExtension();
        $imageName = time() . '.' . $ext;
        $img->move(public_path('uploads'), $imageName);
        $fullUrl = asset('uploads/' . $imageName);

        // تخزين بيانات الصورة في DB
        $currency = new Currency;
        $currency->image = $imageName;
        $currency->image_path = 'uploads/' . $imageName;
        $currency->image_url = $fullUrl;
        $currency->save();

        $imagePath = public_path($currency->image_path);

        // استدعاء Flask API للكشف
        $detections = $this->runDetection($imagePath);

        // لو المستخدم مسجل دخول سجل عملية الكشف
        if (Auth::check()) {
            $ext = pathinfo($imagePath, PATHINFO_EXTENSION);
            $newImageName = 'user_' . time() . '.' . $ext;
            $newImagePath = 'uploads/user_scans/' . $newImageName;

            if (!file_exists(public_path('uploads/user_scans'))) {
                mkdir(public_path('uploads/user_scans'), 0755, true);
            }

            copy($imagePath, public_path($newImagePath));

            UserScan::create([
                'user_id' => Auth::id(),
                'currency_id' => $currency->id,
                'recognized_at' => now(),
                'accuracy' => $detections['accuracy'] ?? 0,
                'image_url' => $newImagePath,
                'result' => $detections['result'] ?? 'unknown',
            ]);
        }

        return response()->json([
            'status' => $detections['status'] ?? false,
            'message' => $detections['message'] ?? 'Detection completed',
            'data' => $detections
        ]);
    }

    private function runDetection($imagePath)
    {
        $flaskApiUrl = 'https://ec85-34-143-137-214.ngrok-free.app/detect';

        if (!file_exists($imagePath)) {
            return [
                'status' => false,
                'message' => 'File not found at path: ' . $imagePath,
                'error' => 'File not found',
            ];
        }

        try {
            $response = Http::attach(
                'image',
                fopen($imagePath, 'r'),
                basename($imagePath)
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
