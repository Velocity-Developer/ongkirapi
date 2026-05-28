<?php

namespace App\Console\Commands;

use App\Models\CostService;
use App\Models\Courier;
use App\Models\CourierService;
use Illuminate\Console\Command;

class GenerateCourierDataFromCostServicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'couriers:generate-from-cost-services
        {--chunk=500 : Number of records processed per batch}
        {--dry-run : Preview generated records without saving}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate couriers and courier services from existing cost services';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $chunkSize = max((int) $this->option('chunk'), 1);
        $isDryRun = (bool) $this->option('dry-run');

        $query = CostService::query()
            ->whereNotNull('code')
            ->whereNotNull('service')
            ->orderBy('id');

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('No cost services found.');

            return self::SUCCESS;
        }

        $createdCouriers = 0;
        $existingCouriers = 0;
        $createdCourierServices = 0;
        $existingCourierServices = 0;
        $seenCourierCodes = [];
        $seenCourierServiceKeys = [];

        $this->info(sprintf(
            '%s %d cost services...',
            $isDryRun ? 'Checking' : 'Processing',
            $total
        ));

        $query->chunkById($chunkSize, function ($costServices) use (
            &$createdCouriers,
            &$existingCouriers,
            &$createdCourierServices,
            &$existingCourierServices,
            &$seenCourierCodes,
            &$seenCourierServiceKeys,
            $isDryRun
        ) {
            foreach ($costServices as $costService) {
                if ($isDryRun) {
                    $courierCode = (string) $costService->code;
                    $courierServiceKey = $costService->code . ':' . $costService->service;

                    if (isset($seenCourierCodes[$courierCode]) || Courier::where('code', $costService->code)->exists()) {
                        $existingCouriers++;
                    } else {
                        $createdCouriers++;
                        $seenCourierCodes[$courierCode] = true;
                    }

                    if (isset($seenCourierServiceKeys[$courierServiceKey]) || CourierService::where([
                        'courier_code' => $costService->code,
                        'code' => $costService->service,
                    ])->exists()) {
                        $existingCourierServices++;
                    } else {
                        $createdCourierServices++;
                        $seenCourierServiceKeys[$courierServiceKey] = true;
                    }

                    continue;
                }

                $courier = Courier::firstOrCreate(
                    ['code' => $costService->code],
                    [
                        'name' => $costService->name,
                        'logo' => null,
                    ]
                );

                $courier->wasRecentlyCreated
                    ? $createdCouriers++
                    : $existingCouriers++;

                $courierService = CourierService::firstOrCreate(
                    [
                        'courier_code' => $costService->code,
                        'code' => $costService->service,
                    ],
                    [
                        'courier_id' => $courier->id,
                        'name' => $costService->service,
                        'description' => $costService->description,
                    ]
                );

                $courierService->wasRecentlyCreated
                    ? $createdCourierServices++
                    : $existingCourierServices++;
            }
        });

        $this->info(sprintf(
            '%s couriers: %d, existing couriers: %d.',
            $isDryRun ? 'Would create' : 'Created',
            $createdCouriers,
            $existingCouriers
        ));

        $this->info(sprintf(
            '%s courier services: %d, existing courier services: %d.',
            $isDryRun ? 'Would create' : 'Created',
            $createdCourierServices,
            $existingCourierServices
        ));

        return self::SUCCESS;
    }
}
