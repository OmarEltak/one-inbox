<?php

declare(strict_types=1);

namespace App\Services\Campaigns;

use App\Models\Contact;
use App\Models\ContactImport;
use Illuminate\Support\Facades\DB;

class PhoneContactImporter
{
    public function __construct(private PhoneNormalizer $normalizer) {}

    public function import(
        int $teamId,
        string $channel,
        string $filename,
        string $defaultCountry,
        string $phoneColumn,
        ?string $nameColumn,
        ?string $optedInAtColumn,
        array $customColumns,
        iterable $rows,
    ): ImportResult {
        $tag = 'imported:' . pathinfo($filename, PATHINFO_FILENAME);

        $import = ContactImport::create([
            'team_id'       => $teamId,
            'user_id'       => auth()->id(),
            'channel'       => $channel,
            'filename'      => $filename,
            'original_name' => $filename,
            'total_rows'    => 0,
            'imported_rows' => 0,
            'skipped_rows'  => 0,
            'invalid_rows'  => 0,
            'tag'           => $tag,
            'status'        => 'running',
        ]);

        $seenPhones = [];
        $total = $imported = $skipped = $invalid = 0;

        DB::transaction(function () use (
            $teamId, $defaultCountry, $phoneColumn, $nameColumn,
            $optedInAtColumn, $customColumns, $rows, $tag,
            &$total, &$imported, &$skipped, &$invalid, &$seenPhones
        ) {
            foreach ($rows as $row) {
                $total++;
                $raw = trim((string) ($row[$phoneColumn] ?? ''));
                try {
                    $normalized = $this->normalizer->normalize($raw, $defaultCountry);
                } catch (InvalidPhoneException) {
                    $invalid++;
                    continue;
                }

                if (isset($seenPhones[$normalized->e164])) {
                    $skipped++;
                    continue;
                }
                $seenPhones[$normalized->e164] = true;

                $meta = ['phone_country' => $normalized->countryIso2];
                foreach ($customColumns as $col) {
                    if (isset($row[$col])) {
                        $meta[$col] = $row[$col];
                    }
                }
                if ($optedInAtColumn && ! empty($row[$optedInAtColumn])) {
                    $meta['opted_in_at'] = $row[$optedInAtColumn];
                }

                Contact::updateOrCreate(
                    ['team_id' => $teamId, 'phone' => $normalized->e164],
                    [
                        'name'     => $nameColumn ? ($row[$nameColumn] ?? null) : null,
                        'metadata' => $meta,
                        'tags'     => [$tag],
                    ],
                );
                $imported++;
            }
        });

        $import->update([
            'total_rows'    => $total,
            'imported_rows' => $imported,
            'skipped_rows'  => $skipped,
            'invalid_rows'  => $invalid,
            'status'        => 'completed',
        ]);

        return new ImportResult(
            importId: $import->id,
            totalRows: $total,
            importedRows: $imported,
            skippedRows: $skipped,
            invalidRows: $invalid,
        );
    }
}
