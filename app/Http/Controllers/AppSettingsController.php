<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\Request;

class AppSettingsController extends Controller
{
    public function show()
    {
        $settings = AppSetting::latest()->first();
        return response()->json([
            'status' => 'success',
            'data' => $settings,
        ]);
    }
} 