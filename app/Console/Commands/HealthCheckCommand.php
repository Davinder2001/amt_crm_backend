<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthCheckCommand extends Command
{
    protected $signature = 'health:check';
    protected $description = 'Check the health of the application and its dependencies';

    public function handle()
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'storage' => $this->checkStorage(),
        ];

        $allHealthy = true;
        foreach ($checks as $service => $healthy) {
            if (!$healthy) {
                $allHealthy = false;
                $this->error("❌ {$service} is not healthy");
            } else {
                $this->info("✅ {$service} is healthy");
            }
        }

        return $allHealthy ? 0 : 1;
    }

    private function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function checkRedis(): bool
    {
        try {
            Redis::ping();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function checkStorage(): bool
    {
        $storagePath = storage_path();
        $bootstrapCachePath = bootstrap_path('cache');

        return is_writable($storagePath) && is_writable($bootstrapCachePath);
    }
} 