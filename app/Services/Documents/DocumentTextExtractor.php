<?php

namespace App\Services\Documents;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\Process\Process;

class DocumentTextExtractor
{
    public function extract(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'pdf') {
            $process = new Process(['pdftotext', '-layout', $file->getRealPath(), '-']);
            $process->setTimeout(15);
            $process->run();
            $text = $process->isSuccessful() ? $process->getOutput() : '';
            if (trim($text) === '') throw ValidationException::withMessages(['file' => 'PDF ini berupa scan atau tidak memiliki teks. Kirim gambar halaman jadwal atau PDF yang sudah OCR.']);
        } elseif ($extension === 'csv') {
            $text = file_get_contents($file->getRealPath()) ?: '';
        } elseif (in_array($extension, ['xls', 'xlsx'], true)) {
            $sheet = IOFactory::load($file->getRealPath())->getActiveSheet();
            $text = collect($sheet->toArray(null, true, true, false))
                ->take(2000)
                ->map(fn (array $row) => implode(' | ', array_slice(array_map(fn ($cell) => trim((string) $cell), $row), 0, 30)))
                ->filter()
                ->implode("\n");
        } else {
            throw ValidationException::withMessages(['file' => 'Format dokumen belum didukung. Gunakan PDF, CSV, XLS, atau XLSX.']);
        }

        return mb_substr(trim(preg_replace('/\s+/', ' ', $text) ?? ''), 0, 40000);
    }
}
