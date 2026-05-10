<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\DataDeletionRequest;
use App\Services\Compliance\MetaUserDataDeleter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ProcessMetaDataDeletion implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public readonly int $deletionRequestId)
    {
    }

    public function handle(MetaUserDataDeleter $deleter): void
    {
        $request = DataDeletionRequest::find($this->deletionRequestId);
        if ($request === null) {
            Log::warning('ProcessMetaDataDeletion: request not found', ['id' => $this->deletionRequestId]);

            return;
        }

        try {
            $result = $deleter->deleteByPlatformUserIds([$request->platform_user_id], $request->source);

            $request->fill([
                'status' => DataDeletionRequest::STATUS_COMPLETED,
                'matched_records' => $result,
                'completed_at' => now(),
            ])->save();
        } catch (Throwable $e) {
            $request->fill([
                'status' => DataDeletionRequest::STATUS_FAILED,
                'error' => $e->getMessage(),
            ])->save();

            Log::error('ProcessMetaDataDeletion failed', [
                'request_id' => $request->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
