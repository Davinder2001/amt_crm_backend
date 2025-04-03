<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Sanctum\PersonalAccessToken;

class CleanupExpiredTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tokens:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired tokens from the database';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $deleted = PersonalAccessToken::where('expires_at', '<', now())->delete();
        $this->info("Deleted {$deleted} expired token(s).");
        return 0;
    }
}
