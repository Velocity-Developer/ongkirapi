<?php

namespace App\Http\Controllers;

use App\Models\Courier;
use App\Models\CourierService;
use App\Models\KodePos;
use App\Models\ShippingLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AnalyticController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $couriers = Courier::query()
            ->withCount('courier_services')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return response()->json([
            'status' => [
                'code' => 200,
                'description' => 'OK',
            ],
            'data' => [
                'shipping_logs_today' => ShippingLog::whereDate('created_at', today())->count(),
                'couriers' => [
                    'total' => $couriers->count(),
                    'services_total' => CourierService::count(),
                    'items' => $couriers,
                ],
                'kodepos_inactive' => KodePos::where('status', 'inactive')->count(),
            ],
        ]);
    }

    public function chart_shippinglog(Request $request)
    {
        $validated = $request->validate([
            'period' => [
                'nullable',
                Rule::in([
                    'daily_week',
                    'monthly_year',
                    'daily_month',
                    'harian_1_minggu',
                    'bulanan_1_tahun',
                    'harian_1_bulan',
                ]),
            ],
        ]);

        $period = $this->normalizeShippingLogChartPeriod($validated['period'] ?? 'daily_week');

        [$startDate, $endDate, $format, $groupExpression] = match ($period) {
            'monthly_year' => [
                now()->startOfMonth()->subMonths(11),
                now()->endOfMonth(),
                'Y-m',
                "DATE_FORMAT(created_at, '%Y-%m')",
            ],
            'daily_month' => [
                now()->startOfDay()->subDays(29),
                now()->endOfDay(),
                'Y-m-d',
                'DATE(created_at)',
            ],
            default => [
                now()->startOfDay()->subDays(6),
                now()->endOfDay(),
                'Y-m-d',
                'DATE(created_at)',
            ],
        };

        $counts = ShippingLog::query()
            ->selectRaw($groupExpression . ' as period, COUNT(*) as total')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw($groupExpression))
            ->pluck('total', 'period');

        $items = $this->buildShippingLogChartItems($period, $startDate, $endDate, $format, $counts);

        return response()->json([
            'status' => [
                'code' => 200,
                'description' => 'OK',
            ],
            'data' => [
                'period' => $period,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'items' => $items,
            ],
        ]);
    }

    private function normalizeShippingLogChartPeriod(string $period): string
    {
        return match ($period) {
            'harian_1_minggu' => 'daily_week',
            'bulanan_1_tahun' => 'monthly_year',
            'harian_1_bulan' => 'daily_month',
            default => $period,
        };
    }

    private function buildShippingLogChartItems(
        string $period,
        Carbon $startDate,
        Carbon $endDate,
        string $format,
        $counts
    ) {
        $items = [];
        $cursor = $startDate->copy();

        while ($cursor <= $endDate) {
            $key = $cursor->format($format);

            $items[] = [
                'label' => $key,
                'total' => (int) ($counts[$key] ?? 0),
            ];

            $period === 'monthly_year'
                ? $cursor->addMonth()
                : $cursor->addDay();
        }

        return $items;
    }
}
