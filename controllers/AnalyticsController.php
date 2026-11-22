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

        // Partner deliveries (Uber, Yango, Bolt) each carry Packe's fixed booking fee
        $carriers = $this->model->carriers();
        $partnerCount = array_sum(array_map(fn($c) => $c['carrier'] === 'packe' ? 0 : (int) $c['n'], $carriers));
        $bookingFees = round($partnerCount * Couriers::PARTNER_FEE, 2);
        // Express costs 1.8 × standard, so 0.8/1.8 of every express fee is the rush premium
        $expressFees = (float) ($delivery['express']['fees'] ?? 0);
        $expressPremium = round($expressFees * (0.8 / 1.8), 2);
        $streams = [
            'Packaging sales'            => round((float) $orders['product_sales'] - (float) $orders['discounts'], 2),
            'Delivery fees'              => round((float) $orders['delivery_fees'] - $expressPremium - $bookingFees, 2),
            'Express rush premium'       => $expressPremium,
            'Partner booking fees'       => $bookingFees,
            'Packe Pro memberships'      => round((float) $pro['revenue'], 2),
        ];

        return [
            'orders'      => $orders,
            'customers'   => $this->model->customerTotals(),
            'pro'         => $pro,
            'streams'     => $streams,
            'total_income'=> array_sum($streams),
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
