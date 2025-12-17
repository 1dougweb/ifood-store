<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard, orders } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Package, TrendingUp, Clock, DollarSign } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    summaryMetrics: {
        total_orders: number;
        today_orders: number;
        pending_orders: number;
        delivered_orders: number;
        cancelled_orders: number;
        total_revenue: number;
        today_revenue: number;
        average_order_value: number;
    };
    recentOrders: Array<{
        id: number;
        ifood_order_id: string;
        short_reference: string;
        status: string;
        total_amount: number;
        customer_name: string;
        placed_at: string;
        restaurant: {
            name: string;
        };
    }>;
    pendingOrdersCount: number;
    chartMetrics: Array<{
        period_date: string;
        total_orders: number;
        total_revenue: number;
    }>;
    restaurants: Array<{
        id: number;
        name: string;
    }>;
    primaryRestaurant: {
        id: number;
        name: string;
    } | null;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value);
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getStatusColor = (status: string) => {
    const colors: Record<string, string> = {
        PLACED: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        CONFIRMED: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        DISPATCHED: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
        DELIVERED: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        CANCELLED: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    };
    return colors[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
};
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
            <!-- Metrics Cards -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total de Pedidos</CardTitle>
                        <Package class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ summaryMetrics.total_orders }}</div>
                        <p class="text-xs text-muted-foreground">
                            {{ summaryMetrics.today_orders }} hoje
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Receita Total</CardTitle>
                        <DollarSign class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ formatCurrency(summaryMetrics.total_revenue) }}
                        </div>
                        <p class="text-xs text-muted-foreground">
                            {{ formatCurrency(summaryMetrics.today_revenue) }} hoje
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Ticket Médio</CardTitle>
                        <TrendingUp class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ formatCurrency(summaryMetrics.average_order_value) }}
                        </div>
                        <p class="text-xs text-muted-foreground">Por pedido</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Pedidos Pendentes</CardTitle>
                        <Clock class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ pendingOrdersCount }}</div>
                        <p class="text-xs text-muted-foreground">Aguardando processamento</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Recent Orders -->
            <Card>
                <CardHeader>
                    <CardTitle>Pedidos Recentes</CardTitle>
                    <CardDescription>Últimos pedidos recebidos</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div
                            v-for="order in recentOrders"
                            :key="order.id"
                            class="flex items-center justify-between rounded-lg border p-4"
                        >
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold">
                                        #{{ order.short_reference || order.ifood_order_id }}
                                    </span>
                                    <span
                                        class="rounded-full px-2 py-1 text-xs font-medium"
                                        :class="getStatusColor(order.status)"
                                    >
                                        {{ order.status }}
                                    </span>
                                </div>
                                <p class="text-sm text-muted-foreground">
                                    {{ order.customer_name }} • {{ order.restaurant.name }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ formatDate(order.placed_at) }}
                                </p>
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-bold">
                                    {{ formatCurrency(order.total_amount) }}
                                </div>
                            </div>
                        </div>
                        <div v-if="recentOrders.length === 0" class="py-8 text-center text-muted-foreground">
                            Nenhum pedido recente
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
