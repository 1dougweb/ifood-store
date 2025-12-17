<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { dashboard } from '@/routes';
import ordersRoute from '@/routes/orders';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Package, User, MapPin, DollarSign, Clock } from 'lucide-vue-next';

interface Props {
    order: {
        id: number;
        ifood_order_id: string;
        short_reference: string;
        display_id: string;
        status: string;
        sub_status: string | null;
        customer_name: string;
        customer_phone: string;
        customer_delivery_address: string;
        total_amount: number;
        delivery_fee: number;
        discount: number;
        items_count: number;
        placed_at: string;
        confirmed_at: string | null;
        dispatched_at: string | null;
        delivered_at: string | null;
        cancelled_at: string | null;
        expected_delivery_at: string | null;
        restaurant: {
            id: number;
            name: string;
        };
        items: Array<{
            id: number;
            name: string;
            quantity: number;
            unit_price: number;
            total_price: number;
        }>;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Pedidos',
        href: ordersRoute.index().url,
    },
    {
        title: `Pedido #${props.order.short_reference || props.order.ifood_order_id}`,
    },
];

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value);
};

const formatDate = (date: string | null) => {
    if (!date) return '-';
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
    <Head :title="`Pedido #${order.short_reference || order.ifood_order_id}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
            <div class="flex items-center gap-4">
                <Link
                    :href="orders().url"
                    class="rounded-lg p-2 hover:bg-muted"
                >
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold">
                        Pedido #{{ order.short_reference || order.ifood_order_id }}
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        {{ order.restaurant.name }}
                    </p>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <!-- Order Details -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Package class="h-5 w-5" />
                            Detalhes do Pedido
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Status</p>
                            <Badge :class="getStatusColor(order.status)" class="mt-1">
                                {{ order.status }}
                            </Badge>
                            <p v-if="order.sub_status" class="mt-1 text-sm text-muted-foreground">
                                {{ order.sub_status }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">ID iFood</p>
                            <p class="mt-1 font-mono text-sm">{{ order.ifood_order_id }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Data do Pedido</p>
                            <p class="mt-1 text-sm">{{ formatDate(order.placed_at) }}</p>
                        </div>
                        <div v-if="order.confirmed_at">
                            <p class="text-sm font-medium text-muted-foreground">Confirmado em</p>
                            <p class="mt-1 text-sm">{{ formatDate(order.confirmed_at) }}</p>
                        </div>
                        <div v-if="order.dispatched_at">
                            <p class="text-sm font-medium text-muted-foreground">Enviado em</p>
                            <p class="mt-1 text-sm">{{ formatDate(order.dispatched_at) }}</p>
                        </div>
                        <div v-if="order.delivered_at">
                            <p class="text-sm font-medium text-muted-foreground">Entregue em</p>
                            <p class="mt-1 text-sm">{{ formatDate(order.delivered_at) }}</p>
                        </div>
                        <div v-if="order.expected_delivery_at">
                            <p class="text-sm font-medium text-muted-foreground">Previsão de Entrega</p>
                            <p class="mt-1 text-sm">{{ formatDate(order.expected_delivery_at) }}</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Customer Info -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <User class="h-5 w-5" />
                            Cliente
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Nome</p>
                            <p class="mt-1 text-sm">{{ order.customer_name }}</p>
                        </div>
                        <div v-if="order.customer_phone">
                            <p class="text-sm font-medium text-muted-foreground">Telefone</p>
                            <p class="mt-1 text-sm">{{ order.customer_phone }}</p>
                        </div>
                        <div v-if="order.customer_delivery_address">
                            <p class="text-sm font-medium text-muted-foreground flex items-center gap-2">
                                <MapPin class="h-4 w-4" />
                                Endereço de Entrega
                            </p>
                            <p class="mt-1 text-sm">{{ order.customer_delivery_address }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Order Items -->
            <Card>
                <CardHeader>
                    <CardTitle>Itens do Pedido</CardTitle>
                    <CardDescription>{{ order.items_count }} item(ns)</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div
                            v-for="item in order.items"
                            :key="item.id"
                            class="flex items-center justify-between rounded-lg border p-4"
                        >
                            <div class="flex-1">
                                <p class="font-medium">{{ item.name }}</p>
                                <p class="text-sm text-muted-foreground">
                                    {{ item.quantity }}x {{ formatCurrency(item.unit_price) }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold">{{ formatCurrency(item.total_price) }}</p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Order Summary -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <DollarSign class="h-5 w-5" />
                        Resumo Financeiro
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Subtotal</span>
                        <span>{{ formatCurrency(order.total_amount - order.delivery_fee + order.discount) }}</span>
                    </div>
                    <div v-if="order.delivery_fee > 0" class="flex justify-between">
                        <span class="text-muted-foreground">Taxa de Entrega</span>
                        <span>{{ formatCurrency(order.delivery_fee) }}</span>
                    </div>
                    <div v-if="order.discount > 0" class="flex justify-between text-green-600">
                        <span>Desconto</span>
                        <span>-{{ formatCurrency(order.discount) }}</span>
                    </div>
                    <div class="flex justify-between border-t pt-2 text-lg font-bold">
                        <span>Total</span>
                        <span>{{ formatCurrency(order.total_amount) }}</span>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

