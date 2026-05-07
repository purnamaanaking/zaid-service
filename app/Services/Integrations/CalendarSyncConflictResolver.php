<?php

namespace App\Services\Integrations;

use Carbon\CarbonInterface;

class CalendarSyncConflictResolver
{
    /**
     * @return array{has_conflict: bool, resolution: string}
     */
    public function resolve(?CarbonInterface $lastSyncedAt, ?CarbonInterface $localChangedAt, ?CarbonInterface $remoteChangedAt): array
    {
        $localChangedAfterSync = $lastSyncedAt !== null && $localChangedAt !== null && $localChangedAt->gt($lastSyncedAt);
        $remoteChangedAfterSync = $lastSyncedAt !== null && $remoteChangedAt !== null && $remoteChangedAt->gt($lastSyncedAt);

        if ($localChangedAfterSync && $remoteChangedAfterSync) {
            return [
                'has_conflict' => true,
                'resolution' => 'remote_wins',
            ];
        }

        if ($remoteChangedAfterSync) {
            return [
                'has_conflict' => false,
                'resolution' => 'remote_wins',
            ];
        }

        if ($localChangedAfterSync) {
            return [
                'has_conflict' => false,
                'resolution' => 'local_wins',
            ];
        }

        return [
            'has_conflict' => false,
            'resolution' => 'remote_wins',
        ];
    }
}
