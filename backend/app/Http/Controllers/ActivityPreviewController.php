<?php

namespace App\Http\Controllers;

use App\Models\AiActivity;
use Illuminate\Http\Request;

class ActivityPreviewController extends Controller
{
    public function show(Request $request)
    {
        $activity = AiActivity::find((int) $request->query('aid'));
        return view('activity-preview', compact('activity'));
    }
}
