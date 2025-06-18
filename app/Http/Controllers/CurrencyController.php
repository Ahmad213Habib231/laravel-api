<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator; 
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
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

        // حفظ الصورة داخل storage/app/public/uploads
        $img = $request->file('image');
        $ext = $img->getClientOriginalExtension();
        $imageName = time() . '.' . $ext;
        $img->storeAs('public/uploads', $imageName);

        // المسار الكامل للصورة في المتصفح
        $fullUrl = asset('storage/uploads/' . $imageName);

        // تخزين بيانات الصورة في DB
        $currency = new Currency;
        $currency->image = $imageName;
        $currency->image_path = 'uploads/' . $imageName;
        $currency->image_url = $fullUrl;
        $currency->Country = 'Egypt';
        $currency->save();

        $imagePath = storage_path('app/public/uploads/' . $imageName);

        // شغل كشف العملة
        $detections = $this->runDetection($imagePath);

        // لو المستخدم مسجل دخول سجل عملية الكشف
        if (Auth::check()) {
            $ext = pathinfo($imagePath, PATHINFO_EXTENSION);
            $newImageName = 'user_' . time() . '.' . $ext;
            $newImagePath = 'public/uploads/user_scans/' . $newImageName;
            $finalUrl = 'storage/uploads/user_scans/' . $newImageName;

            // إنشاء مجلد user_scans لو مش موجود
            if (!Storage::exists('public/uploads/user_scans')) {
                Storage::makeDirectory('public/uploads/user_scans');
            }

            // نسخ الصورة من مكانها إلى مجلد user_scans
            Storage::copy('public/uploads/' . $imageName, $newImagePath);

            // حفظ البيانات في جدول user_scans
            UserScan::create([
                'user_id' => Auth::id(),
                'currency_id' => $currency->id,
                'recognized_at' => now(),
                'accuracy' => $detections['accuracy'] ?? 0,
                'image_url' => $finalUrl,
                'result' => $detections['result'] ?? 'unknown',
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Image uploaded and detection completed',
            'data' => $detections
        ]);
    }

    private function runDetection($imagePath)
    {
        $modelPath = storage_path('app/model/best.pt');
        $scriptPath = base_path('scripts/detect.py');

        $command = "python3 " . escapeshellarg($scriptPath) . " " . escapeshellarg($imagePath) . " " . escapeshellarg($modelPath) . " 2>&1";
        $output = shell_exec($command);

        if ($output === null) {
            return [
                'status' => false,
                'message' => 'Detection process failed.',
                'error' => 'Error executing Python script or invalid output.',
                'output' => 'No output received from the Python script.'
            ];
        }

        $decoded = json_decode($output, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'status' => false,
                'message' => 'Invalid JSON output from detection script.',
                'error' => json_last_error_msg(),
                'raw_output' => $output
            ];
        }

        return $decoded;
    }
}
