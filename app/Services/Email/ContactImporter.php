<?php

declare(strict_types=1);

namespace App\Services\Email;

use App\Models\Contact;
use App\Models\ContactImport;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Import rows from a parsed spreadsheet into the contacts table.
 *
 * Column map shape:
 *   [
 *     'email' => 'Email Address',   // required source column header
 *     'name'  => 'Full Name',       // optional
 *     'custom' => ['Company', 'City'], // any extra columns to keep in metadata
 *   ]
 */
class ContactImporter
{
    public function __construct(
        private readonly SpreadsheetParser $parser,
    ) {}

    /**
     * @param  array{email: string, name?: string|null, custom?: array<int,string>}  $map
     */
    public function import(
        int $teamId,
        int $userId,
        string $filename,
        string $originalName,
        array $map,
    ): ContactImport {
        if (empty($map['email'])) {
            throw new \InvalidArgumentException('Column map must specify an "email" source column.');
        }

        $tag = $this->buildTag($originalName);

        $import = ContactImport::create([
            'team_id'       => $teamId,
            'user_id'       => $userId,
            'filename'      => $filename,
            'original_name' => $originalName,
            'tag'           => $tag,
            'status'        => ContactImport::STATUS_PROCESSING,
        ]);

        $total = 0;
        $imported = 0;
        $invalid = 0;
        $skipped = 0;
        $seenEmails = [];

        try {
            foreach ($this->parser->stream() as $row) {
                $total++;

                $rawEmail = $row[$map['email']] ?? '';
                if (! EmailValidator::isValid($rawEmail)) {
                    $invalid++;
                    continue;
                }

                $email = EmailValidator::normalize($rawEmail);

                if (isset($seenEmails[$email])) {
                    $skipped++;
                    continue;
                }
                $seenEmails[$email] = true;

                $name = ! empty($map['name']) ? trim((string) ($row[$map['name']] ?? '')) : null;

                $customFields = [];
                foreach ($map['custom'] ?? [] as $col) {
                    $val = trim((string) ($row[$col] ?? ''));
                    if ($val !== '') {
                        $customFields[$col] = $val;
                    }
                }

                $this->upsertContact($teamId, $email, $name, $tag, $customFields);
                $imported++;
            }

            $import->update([
                'status'        => ContactImport::STATUS_COMPLETED,
                'total_rows'    => $total,
                'imported_rows' => $imported,
                'invalid_rows'  => $invalid,
                'skipped_rows'  => $skipped,
            ]);
        } catch (Throwable $e) {
            $import->update([
                'status'        => ContactImport::STATUS_FAILED,
                'total_rows'    => $total,
                'imported_rows' => $imported,
                'invalid_rows'  => $invalid,
                'skipped_rows'  => $skipped,
                'last_error'    => substr($e->getMessage(), 0, 1000),
            ]);
            throw $e;
        }

        return $import->fresh();
    }

    private function upsertContact(
        int $teamId,
        string $email,
        ?string $name,
        string $tag,
        array $customFields,
    ): Contact {
        return DB::transaction(function () use ($teamId, $email, $name, $tag, $customFields) {
            $contact = Contact::where('team_id', $teamId)
                ->where('email', $email)
                ->lockForUpdate()
                ->first();

            if ($contact === null) {
                return Contact::create([
                    'team_id'        => $teamId,
                    'email'          => $email,
                    'name'           => $name,
                    'tags'           => [$tag],
                    'metadata'       => $customFields ?: null,
                    'first_seen_at'  => now(),
                ]);
            }

            $tags = array_values(array_unique(array_merge($contact->tags ?? [], [$tag])));
            $metadata = array_merge($contact->metadata ?? [], $customFields);

            $update = ['tags' => $tags, 'metadata' => $metadata ?: null];
            if ($name !== null && $name !== '' && empty($contact->name)) {
                $update['name'] = $name;
            }

            $contact->update($update);
            return $contact;
        });
    }

    private function buildTag(string $originalName): string
    {
        $base = pathinfo($originalName, PATHINFO_FILENAME);
        $base = preg_replace('/[^A-Za-z0-9_-]+/', '-', $base) ?? 'import';
        $base = trim($base, '-_') ?: 'import';
        return 'imported:'.substr($base, 0, 60);
    }
}
