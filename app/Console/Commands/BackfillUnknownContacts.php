<?php

namespace App\Console\Commands;

use App\Jobs\BackfillContactNameJob;
use App\Models\Contact;
use Illuminate\Console\Command;

class BackfillUnknownContacts extends Command
{
    protected $signature = 'contacts:backfill-names
                            {--batch=100 : Contacts to dispatch per run}
                            {--platform= : Limit to a specific platform (instagram|facebook)}';

    protected $description = 'Queue BackfillContactNameJob for every contact with an unknown/empty name';

    public function handle(): int
    {
        $batch    = (int) $this->option('batch');
        $platform = $this->option('platform');

        $query = Contact::whereNull('name')
            ->orWhere('name', '')
            ->orWhereRaw('LOWER(name) = ?', ['unknown']);

        if ($platform) {
            $query->whereHas('contactPlatforms', fn ($q) => $q->where('platform', $platform));
        }

        $total     = 0;
        $dispatched = 0;

        $query->orderBy('id')->chunk($batch, function ($contacts) use (&$total, &$dispatched) {
            foreach ($contacts as $contact) {
                BackfillContactNameJob::dispatch($contact->id)->delay(now()->addSeconds($dispatched * 2));
                $dispatched++;
            }
            $total += $contacts->count();
        });

        $this->info("Dispatched {$dispatched} BackfillContactNameJob(s) for {$total} contact(s).");

        return self::SUCCESS;
    }
}
