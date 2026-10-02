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
            try {
                $process = new Process(['pdftotext', '-layout', $file->getRealPath(), '-']);
                $process->setTimeout(20);
                $process->run();
                $text = $process->isSuccessful() ? $process->getOutput() : '';
            } catch (\Throwable $e) {
                $text = '';
            }

            if (trim($text) === '') {
                throw ValidationException::withMessages([
                    'file' => 'PDF ini berupa scan atau tidak memiliki teks. Kirim gambar halaman jadwal atau PDF yang sudah OCR.',
                ]);
            }
        } elseif ($extension === 'csv') {
            $text = file_get_contents($file->getRealPath()) ?: '';
            if (trim($text) === '') {
                throw ValidationException::withMessages([
                    'file' => 'File CSV kosong atau tidak memiliki data teks.',
                ]);
            }
        } elseif (in_array($extension, ['xls', 'xlsx'], true)) {
            try {
                $spreadsheet = IOFactory::load($file->getRealPath());
                $sheets = $spreadsheet->getAllSheets();
                $allText = [];
                $multipleSheets = count($sheets) > 1;

                foreach ($sheets as $sheet) {
                    $sheetTitle = $sheet->getTitle();
                    $rows = collect($sheet->toArray(null, true, true, false))
                        ->take(1000)
                        ->map(fn (array $row) => implode(' | ', array_slice(array_map(fn ($cell) => trim((string) $cell), $row), 0, 30)))
                        ->filter(fn ($line) => trim(str_replace('|', '', $line)) !== '')
                        ->implode("\n");

                    if (! empty(trim($rows))) {
                        $allText[] = $multipleSheets ? "[Sheet: {$sheetTitle}]\n".$rows : $rows;
                    }
                }
                $text = implode("\n\n", $allText);
            } catch (\Throwable $e) {
                throw ValidationException::withMessages([
                    'file' => 'Gagal membaca dokumen Excel: '.$e->getMessage(),
                ]);
            }

            if (trim($text) === '') {
                throw ValidationException::withMessages([
                    'file' => 'File Excel kosong atau tidak memiliki data teks.',
                ]);
            }
        } else {
            throw ValidationException::withMessages(['file' => 'Format dokumen belum didukung. Gunakan PDF, CSV, XLS, atau XLSX.']);
        }

        $text = preg_replace('/[ \t]+$/m', '', $text) ?? $text;
        $text = preg_replace('/(\r?\n){3,}/', "\n\n", $text) ?? $text;

        return mb_substr(trim($text), 0, 40000);
    }
}
