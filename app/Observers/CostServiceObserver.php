<?php

namespace App\Observers;

use App\Models\CostService;
use App\Models\Courier;
use App\Models\CourierService;

class CostServiceObserver
{
    public function created(CostService $costService): void
    {
        $courier = Courier::firstOrCreate(
            ['code' => $costService->code],
            [
                'name' => $costService->name,
                'logo' => null,
            ]
        );

        CourierService::firstOrCreate(
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
    }
}
