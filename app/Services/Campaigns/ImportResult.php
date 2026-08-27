<?php

declare(strict_types=1);

namespace App\Services\Campaigns;

readonly class ImportResult
{
    public function __construct(
        public int $importId,
        public int $totalRows,
        public int $importedRows,
        public int $skippedRows,
        public int $invalidRows,
    ) {}
}
