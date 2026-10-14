<?php

namespace App\Services\Plans;

use Illuminate\Http\UploadedFile;

class PlanCsvParser
{
    public function readRows(UploadedFile $file): array
    {
        if ($file->getSize() !== false && $file->getSize() > 2 * 1024 * 1024) {
            return [];
        }

        $path = $file->getRealPath();

        if ($path === false) {
            return [];
        }

        $raw = file_get_contents($path);

        if ($raw === false || $raw === '') {
            return [];
        }

        $content = $this->normalizeEncoding($raw);
        $content = ltrim($content, "\xEF\xBB\xBF");
        $lines = preg_split('/\r\n|\n|\r/', $content);

        if (! is_array($lines) || count($lines) < 2) {
            return [];
        }

        $delimiter = $this->detectDelimiter($lines[0] ?? '');
        $headers = array_map(
            fn (string $header) => $this->normalizeHeader($header),
            str_getcsv((string) $lines[0], $delimiter),
        );

        $rows = [];

        foreach ($lines as $index => $line) {
            if ($index === 0 || trim($line) === '') {
                continue;
            }

            if (count($rows) >= 1000) {
                break;
            }

            $columns = str_getcsv($line, $delimiter);
            $row = ['_line' => $index + 1];

            foreach ($headers as $headerIndex => $header) {
                if ($header === '') {
                    continue;
                }

                $row[$header] = isset($columns[$headerIndex]) ? trim((string) $columns[$headerIndex]) : '';
            }

            $rows[] = $row;
        }

        return $rows;
    }

    public function parseDate(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            return null;
        }

        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{2})$/', $trimmed, $matches) === 1) {
            $year = 2000 + (int) $matches[3];

            return sprintf('%04d-%02d-%02d', $year, (int) $matches[2], (int) $matches[1]);
        }

        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $trimmed, $matches) === 1) {
            return sprintf('%04d-%02d-%02d', (int) $matches[3], (int) $matches[2], (int) $matches[1]);
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $trimmed) === 1) {
            return $trimmed;
        }

        return null;
    }

    public function parseKickoffTime(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim(str_ireplace('Uhr', '', $value));

        if ($trimmed === '') {
            return null;
        }

        $normalized = str_replace('.', ':', $trimmed);

        if (preg_match('/^\d{1,2}$/', $normalized) === 1) {
            return sprintf('%02d:00', (int) $normalized);
        }

        if (preg_match('/^(\d{1,2}):(\d{2})$/', $normalized, $matches) !== 1) {
            return null;
        }

        $hour = (int) $matches[1];
        $minute = (int) $matches[2];

        if ($hour > 23 || $minute > 59) {
            return null;
        }

        return sprintf('%02d:%02d', $hour, $minute);
    }

    public function parseTime(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            return null;
        }

        if (preg_match('/^(\d{1,2}):(\d{2})$/', $trimmed, $matches) !== 1) {
            return null;
        }

        $hour = (int) $matches[1];
        $minute = (int) $matches[2];

        if ($hour > 23 || $minute > 59) {
            return null;
        }

        return sprintf('%02d:%02d', $hour, $minute);
    }

    public function parseWeekday(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = mb_strtolower(trim($value));

        if ($normalized === '') {
            return null;
        }

        return match ($normalized) {
            'montag', 'mo' => 'Montag',
            'dienstag', 'di' => 'Dienstag',
            'mittwoch', 'mi' => 'Mittwoch',
            'donnerstag', 'do' => 'Donnerstag',
            'freitag', 'fr' => 'Freitag',
            'samstag', 'sa' => 'Samstag',
            'sonntag', 'so' => 'Sonntag',
            default => null,
        };
    }

    public function normalizeComparableName(?string $value): string
    {
        $value = mb_strtolower((string) $value);
        $value = str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $value);
        $value = preg_replace('/\s+/', ' ', $value) ?? '';

        return trim($value);
    }

    private function normalizeEncoding(string $raw): string
    {
        if (mb_check_encoding($raw, 'UTF-8')) {
            return $raw;
        }

        return mb_convert_encoding($raw, 'UTF-8', 'Windows-1252');
    }

    private function detectDelimiter(string $line): string
    {
        return substr_count($line, ';') >= substr_count($line, ',') ? ';' : ',';
    }

    private function normalizeHeader(string $header): string
    {
        $normalized = mb_strtolower(trim($header));
        $normalized = str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $normalized);
        $normalized = preg_replace('/\s+/', '_', $normalized) ?? '';

        return $normalized;
    }
}
