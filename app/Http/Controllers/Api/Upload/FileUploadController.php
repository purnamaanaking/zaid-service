<?php

namespace App\Http\Controllers\Api\Upload;

use App\Http\Controllers\Controller;
use App\Services\Documents\DocumentTextExtractor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FileUploadController extends Controller
{
    public function __construct(private readonly DocumentTextExtractor $documents) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file' => ['required', 'file', 'max:10240'],
                'type' => ['required', 'string', 'in:image,audio,document'],
            ]);

            $file = $request->file('file');
            $type = $request->input('type');

            $extractedText = $type === 'document' ? $this->documents->extract($file) : null;
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
                    'extracted_text' => $extractedText,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first() ?: $e->getMessage();

            return response()->json([
                'success' => false,
                'message' => $firstError,
                'error' => [
                    'message' => $firstError,
                ],
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Gagal memproses file.',
                'error' => [
                    'message' => $e->getMessage() ?: 'Gagal memproses file.',
                ],
            ], 422);
        }
    }
}
