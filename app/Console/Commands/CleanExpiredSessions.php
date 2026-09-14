<?php

namespace App\Console\Commands;

use App\Models\UserDeviceSession;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Laravel\Sanctum\PersonalAccessToken;

class CleanExpiredSessions extends Command
{
    protected $signature = 'sessions:clean {--days=30 : Number of days after which sessions are considered expired}';

    protected $description = 'Clean expired device sessions and orphaned tokens';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $expiredDate = now()->subDays($days);

        $this->info("Cleaning sessions older than {$days} days...");

        $expiredSessionsQuery = UserDeviceSession::query()
            ->where('last_activity_at', '<', $expiredDate);
        $expiredSessions = (clone $expiredSessionsQuery)->count();
        $expiredSessionsQuery->delete();

        $orphanedTokensQuery = PersonalAccessToken::query()
            ->whereNotIn('id', function (Builder $query): void {
                $query->select('sanctum_token_id')
                    ->from('user_device_sessions')
                    ->whereNotNull('sanctum_token_id');
            })
            ->where('created_at', '<', $expiredDate);
        $orphanedTokens = (clone $orphanedTokensQuery)->count();
        $orphanedTokensQuery->delete();

        $orphanedSessionsQuery = UserDeviceSession::query()
            ->whereNotIn('sanctum_token_id', function (Builder $query): void {
                $query->select('id')->from('personal_access_tokens');
            });
        $orphanedSessions = (clone $orphanedSessionsQuery)->count();
        $orphanedSessionsQuery->delete();

        $this->info("Cleaned {$expiredSessions} expired sessions");
        $this->info("Cleaned {$orphanedTokens} orphaned tokens");
        $this->info("Cleaned {$orphanedSessions} orphaned sessions");
        $this->info('Session cleanup completed successfully!');

        return self::SUCCESS;
    }
}
