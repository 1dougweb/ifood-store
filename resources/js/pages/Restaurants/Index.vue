<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { dashboard } from '@/routes';
import restaurantsRoute from '@/routes/restaurants';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Plus, Store, Phone, Mail, Settings } from 'lucide-vue-next';

interface Props {
    restaurants: {
        data: Array<{
            id: number;
            name: string;
            cnpj: string | null;
            phone: string | null;
            whatsapp_number: string | null;
            is_active: boolean;
            ifood_merchant_id: string | null;
            orders_count: number;
            notifications_count: number;
        }>;
        links: any;
        meta: any;
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
];

const deleteRestaurant = (id: number) => {
    if (confirm('Tem certeza que deseja excluir este restaurante?')) {
            router.delete(restaurantsRoute.destroy({ restaurant: id }).url);
    }
};

const formatCNPJ = (cnpj: string | null) => {
    if (!cnpj) return '-';
    return cnpj.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5');
};
</script>

<template>
    <Head title="Restaurantes" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">Restaurantes</h1>
                    <p class="text-muted-foreground">
                        Gerencie seus restaurantes e integrações
                    </p>
                </div>
                <Link :href="restaurantsRoute.create().url">
                    <Button>
                        <Plus class="mr-2 h-4 w-4" />
                        Novo Restaurante
                    </Button>
                </Link>
            </div>

            <!-- Restaurants Grid -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <Card
                    v-for="restaurant in restaurants.data"
                    :key="restaurant.id"
                    class="hover:shadow-lg transition-shadow"
                >
                    <CardHeader>
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10"
                                >
                                    <Store class="h-6 w-6 text-primary" />
                                </div>
                                <div>
                                    <CardTitle class="text-lg">
                                        {{ restaurant.name }}
                                    </CardTitle>
                                    <CardDescription v-if="restaurant.cnpj">
                                        {{ formatCNPJ(restaurant.cnpj) }}
                                    </CardDescription>
                                </div>
                            </div>
                            <Badge
                                :variant="restaurant.is_active ? 'default' : 'secondary'"
                            >
                                {{ restaurant.is_active ? 'Ativo' : 'Inativo' }}
                            </Badge>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="space-y-2 text-sm">
                            <div
                                v-if="restaurant.phone"
                                class="flex items-center gap-2 text-muted-foreground"
                            >
                                <Phone class="h-4 w-4" />
                                {{ restaurant.phone }}
                            </div>
                            <div
                                v-if="restaurant.whatsapp_number"
                                class="flex items-center gap-2 text-muted-foreground"
                            >
                                <Mail class="h-4 w-4" />
                                {{ restaurant.whatsapp_number }}
                            </div>
                        </div>

                        <div class="flex items-center justify-between border-t pt-4">
                            <div class="text-sm text-muted-foreground">
                                <div>{{ restaurant.orders_count }} pedidos</div>
                                <div>{{ restaurant.notifications_count }} notificações</div>
                            </div>
                            <div class="flex gap-2">
                                <Link :href="restaurantsRoute.show({ restaurant: restaurant.id }).url">
                                    <Button variant="outline" size="sm">
                                        Ver
                                    </Button>
                                </Link>
                                <Link :href="restaurantsRoute.edit({ restaurant: restaurant.id }).url">
                                    <Button variant="outline" size="sm">
                                        <Settings class="h-4 w-4" />
                                    </Button>
                                </Link>
                            </div>
                        </div>

                        <div
                            v-if="!restaurant.ifood_merchant_id"
                            class="rounded-lg bg-yellow-50 p-3 text-sm text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-200 flex gap-2"
                        >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 mt-[2px]">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                        Integração com iFood não configurada
                        </div>
                    </CardContent>
                </Card>

                <div
                    v-if="restaurants.data.length === 0"
                    class="col-span-full py-12 text-center"
                >
                    <Store class="mx-auto h-12 w-12 text-muted-foreground" />
                    <h3 class="mt-4 text-lg font-semibold">Nenhum restaurante</h3>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Comece adicionando seu primeiro restaurante
                    </p>
                    <Link :href="restaurantsRoute.create().url" class="mt-4 inline-block">
                        <Button>
                            <Plus class="mr-2 h-4 w-4" />
                            Adicionar Restaurante
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Pagination -->
            <div
                v-if="restaurants.links.length > 3"
                class="flex justify-center gap-2"
            >
                <Link
                    v-for="link in restaurants.links"
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
        </div>
    </AppLayout>
</template>

