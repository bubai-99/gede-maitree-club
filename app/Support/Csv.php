<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV downloads that open correctly in Excel (UTF-8 with a byte order mark, so Bengali
 * names show properly).
 */
final class Csv
{
    /**
     * @param  list<string>  $header
     * @param  iterable<array<int, string|int|null>>  $rows
     */
    public static function download(string $filename, array $header, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows): void {
            $out = fopen('php://output', 'w');

            if ($out === false) {
                return;
            }

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $header, escape: '');

            foreach ($rows as $row) {
                fputcsv($out, array_map(fn ($cell): string => self::safe((string) $cell), $row), escape: '');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Stop spreadsheet formulas from text typed by people (CSV injection).
     */
    private static function safe(string $cell): string
    {
        return preg_match('/^[=+\-@\t\r]/', $cell) === 1 && ! is_numeric($cell) ? "'".$cell : $cell;
    }
}
