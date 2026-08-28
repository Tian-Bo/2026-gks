<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UploadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate(['file' => ['required', 'file', 'max:10240']]);
        $file = $request->file('file');
        $path = Storage::disk('local')->putFile('ai-uploads', $file);
        return response()->json([
            'url' => rtrim($request->getSchemeAndHttpHost(), '/') . '/common/v1/uploads/' . $path,
            'type' => $file->getMimeType(),
        ]);
    }

    public function show(string $path): BinaryFileResponse
    {
        abort_unless(str_starts_with($path, 'ai-uploads/') && Storage::disk('local')->exists($path), 404);
        return response()->file(Storage::disk('local')->path($path));
    }
}
