<?php

namespace App\Console\Commands;

use App\Models\ShippingLog;
use Illuminate\Console\Command;

class ShipplingLogFillDomainCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shipping-logs:fill-domain
        {--chunk=500 : Number of records processed per batch}
        {--dry-run : Preview changes without updating records}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fill missing shipping log domains from user agent data';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $chunkSize = max((int) $this->option('chunk'), 1);
        $isDryRun = (bool) $this->option('dry-run');

        $query = ShippingLog::query()
            ->whereNull('domain')
            ->whereNotNull('user_agent')
            ->orderBy('id');

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('No shipping logs found with empty domain and available user agent.');

            return self::SUCCESS;
        }

        $updated = 0;
        $skipped = 0;

        $this->info(sprintf(
            '%s %d shipping logs with empty domain...',
            $isDryRun ? 'Checking' : 'Updating',
            $total
        ));

        $query->chunkById($chunkSize, function ($shippingLogs) use (&$updated, &$skipped, $isDryRun) {
            foreach ($shippingLogs as $shippingLog) {
                $domain = ShippingLog::extractDomainFromUserAgent($shippingLog->user_agent);

                if (!$domain) {
                    $skipped++;
                    continue;
                }

                if (!$isDryRun) {
                    $shippingLog->forceFill(['domain' => $domain])->save();
                }

                $updated++;
            }
        });

        $this->info(sprintf(
            '%s: %d, skipped: %d.',
            $isDryRun ? 'Would update' : 'Updated',
            $updated,
            $skipped
        ));

        return self::SUCCESS;
    }
}
