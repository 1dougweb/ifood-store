<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { dashboard } from '@/routes';
import ordersRoute from '@/routes/orders';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Filter, Package } from 'lucide-vue-next';

interface Props {
    orders: {
        data: Array<{
            id: number;
            ifood_order_id: string;
            short_reference: string;
            status: string;
            total_amount: number;
            customer_name: string;
            placed_at: string;
            restaurant: {
                id: number;
                name: string;
            };
        }>;
        links?: any;
        meta?: {
            total?: number;
            [key: string]: any;
        };
    };
    restaurants: Array<{
        id: number;
        name: string;
    }>;
    filters: {
        restaurant_id?: number;
        status?: string;
        date_from?: string;
        date_to?: string;
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
];

const form = useForm({
    restaurant_id: props.filters.restaurant_id ? String(props.filters.restaurant_id) : undefined,
    status: props.filters.status || undefined,
    date_from: props.filters.date_from || undefined,
    date_to: props.filters.date_to || undefined,
});

const applyFilters = () => {
    const params: Record<string, string | number> = {};
    
    if (form.restaurant_id) {
        params.restaurant_id = Number(form.restaurant_id);
    }
    if (form.status) {
        params.status = form.status;
    }
    if (form.date_from) {
        params.date_from = form.date_from;
    }
    if (form.date_to) {
        params.date_to = form.date_to;
    }
    
    router.get(ordersRoute.index().url, params, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    form.restaurant_id = undefined;
    form.status = undefined;
    form.date_from = undefined;
    form.date_to = undefined;
    router.get(ordersRoute.index().url);
};

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
    <Head title="Pedidos" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
            <!-- Filters -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Filter class="h-5 w-5" />
                        Filtros
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="applyFilters" class="grid gap-4 md:grid-cols-4">
                        <div class="space-y-2">
                            <Label for="restaurant_id">Restaurante</Label>
                            <Select v-model="form.restaurant_id">
                                <SelectTrigger id="restaurant_id">
                                    <SelectValue placeholder="Todos" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="restaurant in restaurants"
                                        :key="restaurant.id"
                                        :value="String(restaurant.id)"
                                    >
                                        {{ restaurant.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-2">
                            <Label for="status">Status</Label>
                            <Select v-model="form.status">
                                <SelectTrigger id="status">
                                    <SelectValue placeholder="Todos" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="PLACED">Recebido</SelectItem>
                                    <SelectItem value="CONFIRMED">Confirmado</SelectItem>
                                    <SelectItem value="DISPATCHED">Em Entrega</SelectItem>
                                    <SelectItem value="DELIVERED">Entregue</SelectItem>
                                    <SelectItem value="CANCELLED">Cancelado</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-2">
                            <Label for="date_from">Data Inicial</Label>
                            <Input id="date_from" v-model="form.date_from" type="date" />
                        </div>
                        <div class="space-y-2">
                            <Label for="date_to">Data Final</Label>
                            <Input id="date_to" v-model="form.date_to" type="date" />
                        </div>
                        <div class="flex items-end gap-2">
                            <Button type="submit" class="w-full">Filtrar</Button>
                            <Button type="button" variant="outline" @click="clearFilters">
                                Limpar
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <!-- Orders List -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Package class="h-5 w-5" />
                        Pedidos
                    </CardTitle>
                    <CardDescription>
                        Total: {{ orders.meta?.total ?? orders.data?.length ?? 0 }} pedidos
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div
                            v-for="order in orders.data || []"
                            :key="order.id"
                            class="flex items-center justify-between rounded-lg border p-4 hover:bg-muted/50"
                        >
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <Link
                                        :href="ordersRoute.show({ order: order.id }).url"
                                        class="font-semibold hover:underline"
                                    >
                                        #{{ order.short_reference || order.ifood_order_id }}
                                    </Link>
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
                        <div v-if="!orders.data || orders.data.length === 0" class="py-8 text-center text-muted-foreground">
                            Nenhum pedido encontrado
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="orders.links && orders.links.length > 3" class="mt-6 flex justify-center gap-2">
                        <Link
                            v-for="link in orders.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            :class="[
                                'rounded px-3 py-2 text-sm',
                                link.active
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-muted text-muted-foreground hover:bg-muted/80',
                                !link.url && 'pointer-events-none opacity-50',
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

