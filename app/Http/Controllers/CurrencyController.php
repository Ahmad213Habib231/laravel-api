<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator; 
use Illuminate\Support\Facades\Auth;
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

        // حفظ الصورة
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

        // شغل كشف detect
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
            'status' => true,
            'message' => 'Image uploaded and detection completed',
            'data' => $detections
        ]);
    }

    private function runDetection($imagePath)
    {
        $modelPath = storage_path('app/model/best.pt');
        $scriptPath = base_path('scripts/detect.py');

        $command = escapeshellcmd("python " . escapeshellarg($scriptPath) . " " . escapeshellarg($imagePath) . " " . escapeshellarg($modelPath));
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
