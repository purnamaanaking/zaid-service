<?php

namespace Tests\Unit\Integrations;

use App\Services\Integrations\CalendarSyncConflictResolver;
use Tests\TestCase;

class CalendarSyncConflictResolverTest extends TestCase
{
    public function test_it_flags_conflict_when_local_and_remote_changed_after_last_sync(): void
    {
        $resolver = app(CalendarSyncConflictResolver::class);

        $decision = $resolver->resolve(
            lastSyncedAt: now()->subMinutes(10),
            localChangedAt: now()->subMinutes(2),
            remoteChangedAt: now()->subMinute(),
        );

        $this->assertTrue($decision['has_conflict']);
        $this->assertSame('remote_wins', $decision['resolution']);
    }

    public function test_it_prefers_remote_when_only_remote_changed_after_last_sync(): void
    {
        $resolver = app(CalendarSyncConflictResolver::class);

        $decision = $resolver->resolve(
            lastSyncedAt: now()->subMinutes(10),
            localChangedAt: now()->subMinutes(20),
            remoteChangedAt: now()->subMinute(),
        );

        $this->assertFalse($decision['has_conflict']);
        $this->assertSame('remote_wins', $decision['resolution']);
    }

    public function test_it_prefers_local_when_local_changed_and_remote_did_not(): void
    {
        $resolver = app(CalendarSyncConflictResolver::class);

        $decision = $resolver->resolve(
            lastSyncedAt: now()->subMinutes(10),
            localChangedAt: now()->subMinute(),
            remoteChangedAt: now()->subMinutes(20),
        );

        $this->assertFalse($decision['has_conflict']);
        $this->assertSame('local_wins', $decision['resolution']);
    }
}
