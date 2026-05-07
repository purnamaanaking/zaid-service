<?php

namespace App\Http\Controllers\Api\Upload;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FileUploadController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240'],
            'type' => ['required', 'string', 'in:image,audio,document'],
        ]);

        $file = $request->file('file');
        $type = $request->input('type');

        $path = $file->store("uploads/{$type}", 'public');

        $url = url("storage/{$path}");

        return response()->json([
            'success' => true,
            'message' => 'File uploaded successfully',
            'data' => [
                'url' => $url,
                'path' => $path,
                'type' => $type,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'original_name' => $file->getClientOriginalName(),
            ],
        ]);
    }
}
