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
            if (preg_match('/\b(planner|halaman|page|edisi|copyright)\b|•/i', $line)) {
                continue;
            }

            $cells = str_contains($line, '|')
                ? array_values(array_filter(array_map('trim', explode('|', $line)), fn ($c) => $c !== ''))
                : array_values(array_filter(array_map('trim', preg_split('/\s{2,}|\t/', $line) ?: []), fn ($c) => $c !== ''));

            if ($header === null) {
                $lower = array_map('strtolower', $cells);
                $hasDate = in_array('tanggal', $lower, true) || in_array('date', $lower, true);
                if ($hasDate) {
                    $header = $cells;
                    continue;
                }
            }

            if ($header === null || count($cells) !== count($header)) {
                if ($header !== null && ! empty($candidates) && count($cells) < count($header)) {
                    $continuation = implode(' ', array_filter($cells, fn ($c) => ! preg_match('/^\(?(senin|selasa|rabu|kamis|jumat|sabtu|minggu|mon|tue|wed|thu|fri|sat|sun)\)?$/i', $c) && ! str_contains($c, '•')));
                    if (! empty($continuation)) {
                        $lastIdx = count($candidates) - 1;
                        $candidates[$lastIdx]['title'] .= ' '.$continuation;
                        $candidates[$lastIdx]['searchable'] .= ' '.$continuation;
                    }
                }
                continue;
            }

            $row = array_combine($header, $cells);
            if (! $row) {
                continue;
            }

            // Find date column
            $dateVal = null;
            foreach ($row as $k => $v) {
                $lk = strtolower($k);
                if (str_contains($lk, 'tanggal') || str_contains($lk, 'date')) {
                    $dateVal = $v;
                    break;
                }
            }

            if (empty($dateVal)) {
                continue;
            }

            $cleanDate = trim(preg_replace('/\s*\(.*?\)/', '', $dateVal));
            $parsedDate = null;
            if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $cleanDate, $m)) {
                $parsedDate = sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
            } else {
                try {
                    $parsedDate = Carbon::parse($cleanDate, 'Asia/Jakarta')->format('Y-m-d');
                } catch (\Throwable) {
                    continue;
                }
            }

            // Find time column
            $timeVal = null;
            foreach ($row as $k => $v) {
                $lk = strtolower($k);
                if (str_contains($lk, 'waktu') || str_contains($lk, 'jam') || str_contains($lk, 'time')) {
                    $timeVal = $v;
                    break;
                }
            }

            $startTime = '09:00:00';
            $endTime = '10:00:00';
            if ($timeVal && preg_match('/(\d{1,2})[.:](\d{2})\s*-\s*(\d{1,2})[.:](\d{2})/', $timeVal, $t)) {
                $startTime = sprintf('%02d:%02d:00', $t[1], $t[2]);
                $endTime = sprintf('%02d:%02d:00', $t[3], $t[4]);
            } elseif ($timeVal && preg_match('/(\d{1,2})[.:](\d{2})/', $timeVal, $t)) {
                $startTime = sprintf('%02d:%02d:00', $t[1], $t[2]);
                $endTime = Carbon::parse($startTime)->addHour()->format('H:i:s');
            }

            // Determine title and description
            $title = '';
            $desc = '';
            if (! empty($row['Nama Mahasiswa'])) {
                $title = 'Sidang TA: '.$row['Nama Mahasiswa'];
                $roles = collect(['Nama Pembimbing 1', 'Nama Pembimbing 2', 'Nama Penguji 1', 'Nama Penguji 2'])
                    ->filter(fn ($k) => ! empty($row[$k]))
                    ->map(fn ($k) => $k.': '.$row[$k])
                    ->implode('; ');
                $desc = $roles;
            } else {
                foreach ($row as $k => $v) {
                    $lk = strtolower($k);
                    if (str_contains($lk, 'deskripsi') || str_contains($lk, 'aktivitas') || str_contains($lk, 'kegiatan') || str_contains($lk, 'acara') || str_contains($lk, 'agenda') || str_contains($lk, 'title')) {
                        if (trim($v) !== '') {
                            $title = trim($v);
                            break;
                        }
                    }
                }
                if ($title === '') {
                    foreach ($row as $k => $v) {
                        $lk = strtolower($k);
                        if (! in_array($lk, ['no', 'nomor', 'status', 'kategori', 'category'], true) && ! str_contains($lk, 'tanggal') && ! str_contains($lk, 'waktu') && trim($v) !== '') {
                            $title = trim($v);
                            break;
                        }
                    }
                }
                $category = '';
                foreach ($row as $k => $v) {
                    if (str_contains(strtolower($k), 'kategori') || str_contains(strtolower($k), 'category')) {
                        $category = trim($v);
                        break;
                    }
                }
                $status = $row['STATUS'] ?? $row['Status'] ?? $row['status'] ?? '';
                $desc = trim(($category ? "[{$category}] " : '').($status ? "Status: {$status}" : ''));
            }

            if ($title === '') {
                continue;
            }

            $location = '';
            foreach ($row as $k => $v) {
                $lk = strtolower($k);
                if (str_contains($lk, 'ruangan') || str_contains($lk, 'lokasi') || str_contains($lk, 'tempat') || str_contains($lk, 'location')) {
                    $location = trim($v);
                    break;
                }
            }

            $candidates[] = [
                'title' => $title,
                'description' => $desc,
                'scheduled_date' => $parsedDate,
                'scheduled_time' => $startTime,
                'scheduled_end_time' => $endTime,
                'location' => $location,
                'category' => $category ?? '',
                'searchable' => implode(' | ', $row),
            ];
        }

        return $candidates;
    }
}

