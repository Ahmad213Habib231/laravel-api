<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserScan;
use Illuminate\Support\Facades\Auth;

class ScanHistoryController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            return response()->json([
                'status' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $userId = Auth::id();

        $scans = UserScan::where('user_id', $userId)
            ->orderBy('recognized_at', 'desc')
            ->get(['id', 'image_url', 'result']);

        return response()->json([
            'status' => true,
            'message' => 'Scan history fetched successfully',
            'data' => $scans,
        ]);
    }
}
