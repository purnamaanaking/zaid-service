<?php

namespace App\Services\Documents;

use Illuminate\Support\Carbon;

class DocumentScheduleParser
{
    /** @return array<int, array<string, string>> */
    public function parse(string $text): array
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', $text) ?: [])));
        $header = null;
        $candidates = [];

        foreach ($lines as $line) {
            $cells = array_map('trim', explode('|', $line));
            if ($header === null && in_array('Nama Mahasiswa', $cells, true) && in_array('Tanggal', $cells, true) && in_array('Jam', $cells, true)) {
                $header = $cells;
                continue;
            }
            if ($header === null || count($cells) !== count($header)) continue;
            $row = array_combine($header, $cells);
            if (! $row || empty($row['Nama Mahasiswa']) || empty($row['Tanggal']) || empty($row['Jam'])) continue;
            if (! preg_match('/(\d{1,2})[.:](\d{2})\s*-\s*(\d{1,2})[.:](\d{2})/', $row['Jam'], $time)) continue;

            try {
                $date = Carbon::parse($row['Tanggal'], 'Asia/Jakarta')->format('Y-m-d');
            } catch (\Throwable) {
                continue;
            }

            $roles = collect(['Nama Pembimbing 1', 'Nama Pembimbing 2', 'Nama Penguji 1', 'Nama Penguji 2'])
                ->map(fn ($key) => $key.': '.($row[$key] ?? '-'))
                ->implode('; ');
            $candidates[] = [
                'title' => 'Sidang TA: '.$row['Nama Mahasiswa'],
                'description' => $roles,
                'scheduled_date' => $date,
                'scheduled_time' => sprintf('%02d:%02d:00', $time[1], $time[2]),
                'scheduled_end_time' => sprintf('%02d:%02d:00', $time[3], $time[4]),
                'location' => $row['Ruangan'] ?? '',
                'searchable' => implode(' | ', $row),
            ];
        }

        return $candidates;
    }
}
