<?php

declare(strict_types=1);

namespace App\Services\Email;

use Generator;
use InvalidArgumentException;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use RuntimeException;

/**
 * Streaming parser for CSV and XLSX uploads. Auto-detects the header row
 * (first non-empty row) and yields subsequent rows as associative arrays
 * keyed by header name.
 */
class SpreadsheetParser
{
    public const MAX_ROWS = 50_000;

    public function __construct(
        private readonly string $path,
        private readonly string $extension,
    ) {
        if (! is_readable($path)) {
            throw new InvalidArgumentException("Cannot read file: {$path}");
        }

        if (! in_array(strtolower($extension), ['csv', 'xlsx'], true)) {
            throw new InvalidArgumentException("Unsupported file type: {$extension}");
        }
    }

    /**
     * Read the first N rows for preview (including header). Returns:
     *   ['headers' => [...], 'rows' => [['col' => val, ...], ...]]
     */
    public function preview(int $rows = 20): array
    {
        $headers = [];
        $previewRows = [];
        $count = 0;

        foreach ($this->stream() as $row) {
            if (empty($headers)) {
                $headers = array_keys($row);
            }
            $previewRows[] = $row;
            if (++$count >= $rows) {
                break;
            }
        }

        return [
            'headers' => $headers,
            'rows'    => $previewRows,
        ];
    }

    /**
     * Stream all data rows (after header) as associative arrays. Caller
     * controls memory by handling each row in the loop.
     *
     * @return Generator<int, array<string, string>>
     */
    public function stream(): Generator
    {
        $reader = $this->makeReader();
        $reader->open($this->path);

        try {
            $headers = null;
            $emitted = 0;

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $values = $this->normalizeRow($row->toArray());

                    if ($this->isEmptyRow($values)) {
                        continue;
                    }

                    if ($headers === null) {
                        $headers = $this->normalizeHeaders($values);
                        continue;
                    }

                    if ($emitted >= self::MAX_ROWS) {
                        throw new RuntimeException(
                            sprintf('Spreadsheet exceeds %d row limit.', self::MAX_ROWS)
                        );
                    }

                    yield $this->combine($headers, $values);
                    $emitted++;
                }
                // Only read the first sheet to keep behavior predictable.
                break;
            }
        } finally {
            $reader->close();
        }
    }

    private function makeReader(): CsvReader|XlsxReader
    {
        return match (strtolower($this->extension)) {
            'csv'  => new CsvReader(),
            'xlsx' => new XlsxReader(),
        };
    }

    /**
     * Normalize cell values to trimmed strings.
     *
     * @param  array<int, mixed>  $values
     * @return array<int, string>
     */
    private function normalizeRow(array $values): array
    {
        return array_map(
            static fn ($v) => is_scalar($v) ? trim((string) $v) : '',
            $values
        );
    }

    /**
     * Deduplicate header names and replace empties with col_N placeholders.
     *
     * @param  array<int, string>  $values
     * @return array<int, string>
     */
    private function normalizeHeaders(array $values): array
    {
        $seen = [];
        $out = [];
        foreach ($values as $idx => $v) {
            $name = $v !== '' ? $v : 'column_'.($idx + 1);
            $base = $name;
            $n = 1;
            while (isset($seen[$name])) {
                $n++;
                $name = $base.'_'.$n;
            }
            $seen[$name] = true;
            $out[] = $name;
        }
        return $out;
    }

    private function isEmptyRow(array $values): bool
    {
        foreach ($values as $v) {
            if ($v !== '') {
                return false;
            }
        }
        return true;
    }

    /**
     * Combine headers + values into associative array. Pads missing trailing cells.
     *
     * @param  array<int, string>  $headers
     * @param  array<int, string>  $values
     * @return array<string, string>
     */
    private function combine(array $headers, array $values): array
    {
        $out = [];
        foreach ($headers as $i => $h) {
            $out[$h] = $values[$i] ?? '';
        }
        return $out;
    }
}
