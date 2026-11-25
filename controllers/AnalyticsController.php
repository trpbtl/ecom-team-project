<?php
/**
 * Controller: turns the raw figures from AnalyticsClass into what the Analytics page shows,
 * including the split of revenue between Packe's revenue streams.
 */
require_once __DIR__ . '/../classes/AnalyticsClass.php';
require_once __DIR__ . '/Couriers.php';

class AnalyticsController
{
    public const PERIODS = [7, 30, 90];

    private AnalyticsClass $model;

    public function __construct()
    {
        $this->model = new AnalyticsClass();
    }

    public static function period($days): int
    {
        return in_array((int) $days, self::PERIODS, true) ? (int) $days : 30;
    }

    /** Everything the page needs, in one array. */
    public function dashboard(int $days): array
    {
        $orders = $this->model->orderTotals();
        $delivery = $this->indexBy($this->model->deliveryOptions(), 'opt');
        $pro = $this->model->proTotals();
        $funnel = $this->model->designFunnel();

        // The smaller streams, all time (for the totals) and per day for the chosen period (for the chart)
        $carriers = $this->model->carriers();
        $allTime = $this->splitDelivery($this->model->deliveryIncomeByDay(0, Couriers::PARTNER_FEE));
        $streams = [
            'Packaging sales'       => round((float) $orders['product_sales'] - (float) $orders['discounts'], 2),
            'Delivery fees'         => round(array_sum(array_column($allTime, 'delivery')), 2),
            'Express rush premium'  => round(array_sum(array_column($allTime, 'express')), 2),
            'Partner booking fees'  => round(array_sum(array_column($allTime, 'partner')), 2),
            'Packe Pro memberships' => round((float) $pro['revenue'], 2),
        ];
        $total = array_sum($streams);
        $shares = array_map(fn($v) => $total > 0 ? round(100 * $v / $total, 1) : 0, $streams);

        // "Beyond packaging": one value per day for each smaller stream, so they show up on their own chart
        $perDay = $this->indexBy($this->splitDelivery($this->model->deliveryIncomeByDay($days, Couriers::PARTNER_FEE)), 'day');
        $proDay = $this->indexBy($this->model->membershipsByDay($days), 'day');
        $beyond = ['Delivery fees' => [], 'Express rush premium' => [], 'Partner booking fees' => [], 'Packe Pro memberships' => []];
        foreach ($this->fillDays([], $days) as $d) {
            $day = $d['day'];
            $beyond['Delivery fees'][]         = round($perDay[$day]['delivery'] ?? 0, 2);
            $beyond['Express rush premium'][]  = round($perDay[$day]['express'] ?? 0, 2);
            $beyond['Partner booking fees'][]  = round($perDay[$day]['partner'] ?? 0, 2);
            $beyond['Packe Pro memberships'][] = round((float) ($proDay[$day]['amount'] ?? 0), 2);
        }
        $beyondTotals = array_map(fn($vals) => round(array_sum($vals), 2), $beyond);

        return [
            'shares'      => $shares,
            'beyond'      => $beyond,
            'beyondTotals'=> $beyondTotals,
            'orders'      => $orders,
            'customers'   => $this->model->customerTotals(),
            'pro'         => $pro,
            'streams'     => $streams,
            'total_income'=> $total,
            'daily'       => $this->fillDays($this->model->revenueByDay($days), $days),
            'statuses'    => $this->model->ordersByStatus(),
            'categories'  => $this->model->revenueByCategory(),
            'products'    => $this->model->topProducts(),
            'delivery'    => $delivery,
            'deliveryStatuses' => $this->model->deliveryStatuses(),
            'carriers'    => $carriers,
            'riders'      => $this->model->riders(),
            'channels'    => $this->model->paymentChannels(),
            'funnel'      => $funnel,
            'conversion'  => (int) $funnel['saved'] > 0 ? round(100 * (int) $funnel['ordered'] / (int) $funnel['saved']) : 0,
            'cities'      => $this->model->topCities(),
        ];
    }

    /**
     * Split each day's delivery income into its streams. Express costs 1.8 × standard, so 0.8/1.8 of the express
     * fee (without partner booking fees) is the rush premium; each Uber, Yango or Bolt delivery adds one booking fee.
     */
    private function splitDelivery(array $rows): array
    {
        return array_map(function ($r) {
            $express = (float) $r['express_fees'] * (DELIVERY_EXPRESS_FACTOR - 1) / DELIVERY_EXPRESS_FACTOR;
            $partner = (int) $r['partner_count'] * Couriers::PARTNER_FEE;
            return ['day' => $r['day'], 'express' => $express, 'partner' => $partner,
                    'delivery' => (float) $r['fees'] - $express - $partner];
        }, $rows);
    }

    /** One entry per day, with zero for days without sales, so the chart has no gaps. */
    private function fillDays(array $rows, int $days): array
    {
        $byDay = $this->indexBy($rows, 'day');
        $out = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-$i days"));
            $out[] = ['day' => $day, 'revenue' => (float) ($byDay[$day]['revenue'] ?? 0), 'orders' => (int) ($byDay[$day]['orders'] ?? 0)];
        }
        return $out;
    }

    private function indexBy(array $rows, string $key): array
    {
        $out = [];
        foreach ($rows as $r) $out[$r[$key]] = $r;
        return $out;
    }
}
