<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { Bell, MessageSquare, Mail } from 'lucide-vue-next';
import { ref, computed } from 'vue';

interface Props {
    restaurant?: {
        id: number;
        name: string;
        whatsapp_number: string | null;
        notification_settings: {
            enabled_events?: string[];
            quiet_hours?: {
                enabled: boolean;
                start: string;
                end: string;
            };
        } | null;
    } | null;
    restaurants?: Array<{
        id: number;
        name: string;
    }>;
}

const props = withDefaults(defineProps<Props>(), {
    restaurant: null,
    restaurants: () => [],
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Configurações',
        href: '#',
    },
    {
        title: 'Notificações',
    },
];

const selectedRestaurantId = ref<number | null>(
    props.restaurant?.id || (props.restaurants.length > 0 ? props.restaurants[0].id : null)
);

const form = useForm({
    whatsapp_number: props.restaurant?.whatsapp_number || '',
    notification_settings: {
        enabled_events: props.restaurant?.notification_settings?.enabled_events || [
            'new_order',
            'delayed_order',
            'delivered_order',
            'cancelled_order',
        ],
        quiet_hours: {
            enabled: props.restaurant?.notification_settings?.quiet_hours?.enabled || false,
            start: props.restaurant?.notification_settings?.quiet_hours?.start || '22:00',
            end: props.restaurant?.notification_settings?.quiet_hours?.end || '08:00',
        },
    },
});

const enabledEvents = computed(() => form.notification_settings?.enabled_events || []);

const eventOptions = [
    { value: 'new_order', label: 'Novo Pedido', description: 'Receber notificação quando um novo pedido for criado' },
    { value: 'delayed_order', label: 'Pedido em Atraso', description: 'Receber notificação quando um pedido estiver atrasado' },
    { value: 'delivered_order', label: 'Pedido Entregue', description: 'Receber notificação quando um pedido for entregue' },
    { value: 'cancelled_order', label: 'Pedido Cancelado', description: 'Receber notificação quando um pedido for cancelado' },
];

const toggleEvent = (event: string) => {
    const events = form.notification_settings.enabled_events || [];
    const index = events.indexOf(event);
    if (index > -1) {
        events.splice(index, 1);
    } else {
        events.push(event);
    }
    form.notification_settings.enabled_events = events;
};

const submit = () => {
    form.put(`/restaurants/${selectedRestaurantId.value}`, {
        preserveScroll: true,
    });
};

const loadRestaurant = (restaurantId: number) => {
    selectedRestaurantId.value = restaurantId;
    router.get(`/settings/notifications?restaurant_id=${restaurantId}`, {}, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Configurações de Notificações" />
        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall
                    title="Configurações de Notificações"
                    description="Configure como e quando receber notificações sobre seus pedidos"
                />

                <!-- Restaurant Selector -->
                <Card v-if="restaurants.length > 1">
                    <CardHeader>
                        <CardTitle>Restaurante</CardTitle>
                        <CardDescription>
                            Selecione o restaurante para configurar
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <select
                            v-model="selectedRestaurantId"
                            @change="loadRestaurant(Number(selectedRestaurantId))"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <option
                                v-for="restaurant in restaurants"
                                :key="restaurant.id"
                                :value="restaurant.id"
                            >
                                {{ restaurant.name }}
                            </option>
                        </select>
                    </CardContent>
                </Card>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- WhatsApp Configuration -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <MessageSquare class="h-5 w-5" />
                                WhatsApp
                            </CardTitle>
                            <CardDescription>
                                Configure o número do WhatsApp para receber notificações
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-2">
                                <Label for="whatsapp_number">Número do WhatsApp *</Label>
                                <Input
                                    id="whatsapp_number"
                                    v-model="form.whatsapp_number"
                                    type="tel"
                                    placeholder="5511999999999"
                                    required
                                />
                                <p class="text-xs text-muted-foreground">
                                    Número com código do país (ex: 5511999999999)
                                </p>
                                <p
                                    v-if="form.errors.whatsapp_number"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.whatsapp_number }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Event Notifications -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Bell class="h-5 w-5" />
                                Eventos de Notificação
                            </CardTitle>
                            <CardDescription>
                                Selecione quais eventos você deseja receber notificações
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div
                                v-for="event in eventOptions"
                                :key="event.value"
                                class="flex items-start space-x-3 rounded-lg border p-4"
                            >
                                <Checkbox
                                    :id="event.value"
                                    :checked="enabledEvents.includes(event.value)"
                                    @update:checked="toggleEvent(event.value)"
                                />
                                <div class="flex-1 space-y-1">
                                    <Label
                                        :for="event.value"
                                        class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                    >
                                        {{ event.label }}
                                    </Label>
                                    <p class="text-xs text-muted-foreground">
                                        {{ event.description }}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Quiet Hours -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Horário Silencioso</CardTitle>
                            <CardDescription>
                                Configure um horário para não receber notificações
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex items-center space-x-2">
                                <Checkbox
                                    id="quiet_hours_enabled"
                                    v-model:checked="form.notification_settings.quiet_hours.enabled"
                                />
                                <Label
                                    for="quiet_hours_enabled"
                                    class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                >
                                    Ativar horário silencioso
                                </Label>
                            </div>

                            <div
                                v-if="form.notification_settings.quiet_hours.enabled"
                                class="grid gap-4 md:grid-cols-2"
                            >
                                <div class="space-y-2">
                                    <Label for="quiet_hours_start">Início</Label>
                                    <Input
                                        id="quiet_hours_start"
                                        v-model="form.notification_settings.quiet_hours.start"
                                        type="time"
                                    />
                                </div>
                                <div class="space-y-2">
                                    <Label for="quiet_hours_end">Fim</Label>
                                    <Input
                                        id="quiet_hours_end"
                                        v-model="form.notification_settings.quiet_hours.end"
                                        type="time"
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <div class="flex justify-end gap-4">
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Salvando...' : 'Salvar Configurações' }}
                        </Button>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>

