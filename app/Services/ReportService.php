<?php

namespace App\Services;

use App\Models\Restaurant;
use App\Models\Order;
use App\Models\RestaurantMetric;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class ReportService
{
    /**
     * Generate weekly report for a restaurant
     */
    public function generateWeeklyReport(Restaurant $restaurant, Carbon $startDate = null, Carbon $endDate = null): array
    {
        $startDate = $startDate ?? Carbon::now()->subWeek()->startOfWeek();
        $endDate = $endDate ?? Carbon::now()->subWeek()->endOfWeek();

        $orders = $restaurant->orders()
            ->whereBetween('placed_at', [$startDate, $endDate])
            ->get();

        $totalOrders = $orders->count();
        $totalRevenue = $orders->sum('total_amount');
        $deliveredOrders = $orders->where('status', 'DELIVERED')->count();
        $cancelledOrders = $orders->where('status', 'CANCELLED')->count();
        $delayedOrders = $orders->where('status', 'DELAYED')->count();

        $averageOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        // Calculate average delivery time
        $deliveredOrdersWithTime = $orders->where('status', 'DELIVERED')
            ->whereNotNull('delivered_at')
            ->whereNotNull('placed_at');
        
        $avgDeliveryTime = 0;
        if ($deliveredOrdersWithTime->count() > 0) {
            $totalMinutes = $deliveredOrdersWithTime->sum(function ($order) {
                return $order->placed_at->diffInMinutes($order->delivered_at);
            });
            $avgDeliveryTime = $totalMinutes / $deliveredOrdersWithTime->count();
        }

        return [
            'restaurant' => $restaurant->name,
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'metrics' => [
                'total_orders' => $totalOrders,
                'total_revenue' => $totalRevenue,
                'average_order_value' => round($averageOrderValue, 2),
                'delivered_orders' => $deliveredOrders,
                'cancelled_orders' => $cancelledOrders,
                'delayed_orders' => $delayedOrders,
                'average_delivery_time_minutes' => round($avgDeliveryTime, 2),
            ],
            'recommendations' => $this->generateRecommendations($totalOrders, $delayedOrders, $cancelledOrders, $avgDeliveryTime),
        ];
    }

    /**
     * Generate monthly report for a restaurant
     */
    public function generateMonthlyReport(Restaurant $restaurant, Carbon $month = null): array
    {
        $month = $month ?? Carbon::now()->subMonth();
        $startDate = $month->copy()->startOfMonth();
        $endDate = $month->copy()->endOfMonth();

        $orders = $restaurant->orders()
            ->whereBetween('placed_at', [$startDate, $endDate])
            ->get();

        $totalOrders = $orders->count();
        $totalRevenue = $orders->sum('total_amount');
        $deliveredOrders = $orders->where('status', 'DELIVERED')->count();
        $cancelledOrders = $orders->where('status', 'CANCELLED')->count();
        $delayedOrders = $orders->where('status', 'DELAYED')->count();

        $averageOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        // Daily breakdown
        $dailyBreakdown = [];
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $dayOrders = $orders->filter(function ($order) use ($currentDate) {
                return $order->placed_at->isSameDay($currentDate);
            });

            $dailyBreakdown[] = [
                'date' => $currentDate->format('Y-m-d'),
                'orders' => $dayOrders->count(),
                'revenue' => $dayOrders->sum('total_amount'),
            ];

            $currentDate->addDay();
        }

        return [
            'restaurant' => $restaurant->name,
            'period' => [
                'month' => $month->format('Y-m'),
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'metrics' => [
                'total_orders' => $totalOrders,
                'total_revenue' => $totalRevenue,
                'average_order_value' => round($averageOrderValue, 2),
                'delivered_orders' => $deliveredOrders,
                'cancelled_orders' => $cancelledOrders,
                'delayed_orders' => $delayedOrders,
            ],
            'daily_breakdown' => $dailyBreakdown,
            'recommendations' => $this->generateRecommendations($totalOrders, $delayedOrders, $cancelledOrders, 0),
        ];
    }

    /**
     * Generate recommendations based on metrics
     */
    protected function generateRecommendations(int $totalOrders, int $delayedOrders, int $cancelledOrders, float $avgDeliveryTime): array
    {
        $recommendations = [];

        if ($totalOrders === 0) {
            $recommendations[] = [
                'type' => 'warning',
                'message' => 'Nenhum pedido registrado no período. Verifique a integração com o iFood.',
            ];
            return $recommendations;
        }

        $delayedPercentage = ($delayedOrders / $totalOrders) * 100;
        if ($delayedPercentage > 10) {
            $recommendations[] = [
                'type' => 'error',
                'message' => "Taxa de atraso alta ({$delayedPercentage}%). Revise o tempo de preparo e comunicação com entregadores.",
            ];
        }

        $cancelledPercentage = ($cancelledOrders / $totalOrders) * 100;
        if ($cancelledPercentage > 5) {
            $recommendations[] = [
                'type' => 'warning',
                'message' => "Taxa de cancelamento alta ({$cancelledPercentage}%). Analise os motivos dos cancelamentos.",
            ];
        }

        if ($avgDeliveryTime > 60) {
            $recommendations[] = [
                'type' => 'info',
                'message' => "Tempo médio de entrega alto ({$avgDeliveryTime} minutos). Considere otimizar o processo.",
            ];
        }

        if (empty($recommendations)) {
            $recommendations[] = [
                'type' => 'success',
                'message' => 'Métricas dentro do esperado. Continue assim!',
            ];
        }

        return $recommendations;
    }

    /**
     * Send report via email
     */
    public function sendReportByEmail(Restaurant $restaurant, array $report, string $type = 'weekly'): void
    {
        $user = $restaurant->user;
        $locale = $user->language ?? 'pt';

        // Set locale for email
        app()->setLocale($locale);

        $subject = $type === 'weekly' 
            ? __('Relatório Semanal - :restaurant', ['restaurant' => $restaurant->name])
            : __('Relatório Mensal - :restaurant', ['restaurant' => $restaurant->name]);

        // For now, we'll use a simple text email
        // In production, create a proper Mailable class
        Mail::raw($this->formatReportAsText($report, $type, $locale), function ($message) use ($user, $subject) {
            $message->to($user->email)
                ->subject($subject);
        });
    }

    /**
     * Format report as text for email
     */
    protected function formatReportAsText(array $report, string $type, string $locale): string
    {
        $periodLabel = $type === 'weekly' ? 'Semana' : 'Mês';
        $period = $report['period']['start'] . ' a ' . $report['period']['end'];

        $text = "Relatório {$periodLabel} - {$report['restaurant']}\n";
        $text .= "Período: {$period}\n\n";
        $text .= "Métricas:\n";
        $text .= "- Total de Pedidos: {$report['metrics']['total_orders']}\n";
        $text .= "- Receita Total: R$ " . number_format($report['metrics']['total_revenue'], 2, ',', '.') . "\n";
        $text .= "- Pedidos Entregues: {$report['metrics']['delivered_orders']}\n";
        $text .= "- Pedidos Cancelados: {$report['metrics']['cancelled_orders']}\n";
        $text .= "- Pedidos Atrasados: {$report['metrics']['delayed_orders']}\n\n";

        if (!empty($report['recommendations'])) {
            $text .= "Recomendações:\n";
            foreach ($report['recommendations'] as $rec) {
                $text .= "- {$rec['message']}\n";
            }
        }

        return $text;
    }
}

