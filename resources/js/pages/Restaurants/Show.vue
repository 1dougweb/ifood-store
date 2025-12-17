<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { dashboard } from '@/routes';
import restaurantsRoute from '@/routes/restaurants';
import ordersRoute from '@/routes/orders';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Store,
    Phone,
    Mail,
    MapPin,
    Package,
    Bell,
    Settings,
    Link as LinkIcon,
} from 'lucide-vue-next';

interface Props {
    restaurant: {
        id: number;
        name: string;
        cnpj: string | null;
        address: string | null;
        phone: string | null;
        whatsapp_number: string | null;
        is_active: boolean;
        ifood_merchant_id: string | null;
        orders: Array<{
            id: number;
            ifood_order_id: string;
            short_reference: string;
            status: string;
            total_amount: number;
            placed_at: string;
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
        title: 'Restaurantes',
        href: restaurantsRoute.index().url,
    },
    {
        title: props.restaurant.name,
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

const formatCNPJ = (cnpj: string | null) => {
    if (!cnpj) return '-';
    return cnpj.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5');
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
    <Head :title="restaurant.name" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="restaurantsRoute.index().url">
                        <Button variant="ghost" size="icon">
                            <ArrowLeft class="h-5 w-5" />
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-3xl font-bold">{{ restaurant.name }}</h1>
                        <p class="text-muted-foreground">
                            Detalhes e informações do restaurante
                        </p>
                    </div>
                </div>
                <Link :href="restaurantsRoute.edit({ restaurant: restaurant.id }).url">
                    <Button>
                        <Settings class="mr-2 h-4 w-4" />
                        Editar
                    </Button>
                </Link>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <!-- Restaurant Info -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Store class="h-5 w-5" />
                            Informações
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Status</p>
                            <Badge
                                :variant="restaurant.is_active ? 'default' : 'secondary'"
                                class="mt-1"
                            >
                                {{ restaurant.is_active ? 'Ativo' : 'Inativo' }}
                            </Badge>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">CNPJ</p>
                            <p class="mt-1">{{ formatCNPJ(restaurant.cnpj) }}</p>
                        </div>
                        <div v-if="restaurant.address">
                            <p
                                class="text-sm font-medium text-muted-foreground flex items-center gap-2"
                            >
                                <MapPin class="h-4 w-4" />
                                Endereço
                            </p>
                            <p class="mt-1">{{ restaurant.address }}</p>
                        </div>
                        <div v-if="restaurant.phone">
                            <p
                                class="text-sm font-medium text-muted-foreground flex items-center gap-2"
                            >
                                <Phone class="h-4 w-4" />
                                Telefone
                            </p>
                            <p class="mt-1">{{ restaurant.phone }}</p>
                        </div>
                        <div v-if="restaurant.whatsapp_number">
                            <p
                                class="text-sm font-medium text-muted-foreground flex items-center gap-2"
                            >
                                <Mail class="h-4 w-4" />
                                WhatsApp
                            </p>
                            <p class="mt-1">{{ restaurant.whatsapp_number }}</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- iFood Integration -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <LinkIcon class="h-5 w-5" />
                            Integração iFood
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div
                            v-if="restaurant.ifood_merchant_id"
                            class="rounded-lg bg-green-50 p-4 dark:bg-green-900/20"
                        >
                            <p class="font-medium text-green-800 dark:text-green-200">
                                ✅ Conectado
                            </p>
                            <p class="mt-1 text-sm text-green-600 dark:text-green-300">
                                Merchant ID: {{ restaurant.ifood_merchant_id }}
                            </p>
                        </div>
                        <div
                            v-else
                            class="rounded-lg bg-yellow-50 p-4 dark:bg-yellow-900/20"
                        >
                            <p class="text-sm text-yellow-800 dark:text-yellow-200 flex gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                </svg>
                                Integração não configurada
                            </p>
                            <Link
                                :href="restaurantsRoute.edit({ restaurant: restaurant.id }).url"
                                class="mt-3 inline-block"
                            >
                                <Button variant="outline" size="sm">
                                    Configurar
                                </Button>
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Recent Orders -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Package class="h-5 w-5" />
                        Pedidos Recentes
                    </CardTitle>
                    <CardDescription>
                        Últimos pedidos deste restaurante
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div
                            v-for="order in restaurant.orders"
                            :key="order.id"
                            class="flex items-center justify-between rounded-lg border p-4"
                        >
                            <div class="flex-1">
                                <Link
                                    :href="ordersRoute.show({ order: order.id }).url"
                                    class="font-semibold hover:underline"
                                >
                                    #{{ order.short_reference || order.ifood_order_id }}
                                </Link>
                                <p class="text-sm text-muted-foreground">
                                    {{ formatDate(order.placed_at) }}
                                </p>
                            </div>
                            <div class="flex items-center gap-4">
                                <Badge :class="getStatusColor(order.status)">
                                    {{ order.status }}
                                </Badge>
                                <div class="text-right">
                                    <div class="font-semibold">
                                        {{ formatCurrency(order.total_amount) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div
                            v-if="restaurant.orders.length === 0"
                            class="py-8 text-center text-muted-foreground"
                        >
                            Nenhum pedido recente
                        </div>
                    </div>
                    <div v-if="restaurant.orders.length > 0" class="mt-4">
                        <Link :href="ordersRoute.index().url + '?restaurant_id=' + restaurant.id">
                            <Button variant="outline" class="w-full">
                                Ver todos os pedidos
                            </Button>
                        </Link>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

