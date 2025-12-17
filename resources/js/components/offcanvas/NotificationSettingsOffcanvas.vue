<script setup lang="ts">
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Bell, MessageSquare } from 'lucide-vue-next';

interface Props {
    open: boolean;
    restaurant: {
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
    };
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'saved'): void;
}>();

const form = useForm({
    whatsapp_number: props.restaurant.whatsapp_number || '',
    notification_settings: {
        enabled_events: props.restaurant.notification_settings?.enabled_events || [
            'new_order',
            'delayed_order',
            'delivered_order',
            'cancelled_order',
        ],
        quiet_hours: {
            enabled: props.restaurant.notification_settings?.quiet_hours?.enabled || false,
            start: props.restaurant.notification_settings?.quiet_hours?.start || '22:00',
            end: props.restaurant.notification_settings?.quiet_hours?.end || '08:00',
        },
    },
});

const enabledEvents = computed(() => form.notification_settings?.enabled_events || []);

const eventOptions = [
    {
        value: 'new_order',
        label: 'Novo Pedido',
        description: 'Receber notificação quando um novo pedido for criado',
    },
    {
        value: 'delayed_order',
        label: 'Pedido em Atraso',
        description: 'Receber notificação quando um pedido estiver atrasado',
    },
    {
        value: 'delivered_order',
        label: 'Pedido Entregue',
        description: 'Receber notificação quando um pedido for entregue',
    },
    {
        value: 'cancelled_order',
        label: 'Pedido Cancelado',
        description: 'Receber notificação quando um pedido for cancelado',
    },
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
    form.put(`/restaurants/${props.restaurant.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            emit('saved');
            emit('update:open', false);
        },
    });
};
</script>

<template>
    <Sheet :open="open" @update:open="emit('update:open', $event)">
        <SheetContent class="w-full sm:max-w-lg overflow-y-auto">
            <SheetHeader>
                <SheetTitle class="flex items-center gap-2">
                    <Bell class="h-5 w-5" />
                    Configurações de Notificações
                </SheetTitle>
                <SheetDescription>
                    Configure as notificações para {{ restaurant.name }}
                </SheetDescription>
            </SheetHeader>

            <form @submit.prevent="submit" class="mt-6 space-y-6">
                <!-- WhatsApp Configuration -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2">
                        <MessageSquare class="h-4 w-4" />
                        <h3 class="font-semibold">WhatsApp</h3>
                    </div>
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
                    </div>
                </div>

                <!-- Event Notifications -->
                <div class="space-y-4">
                    <h3 class="font-semibold">Eventos de Notificação</h3>
                    <div class="space-y-3">
                        <div
                            v-for="event in eventOptions"
                            :key="event.value"
                            class="flex items-start space-x-3 rounded-lg border p-3"
                        >
                            <Checkbox
                                :id="event.value"
                                :checked="enabledEvents.includes(event.value)"
                                @update:checked="toggleEvent(event.value)"
                            />
                            <div class="flex-1 space-y-1">
                                <Label
                                    :for="event.value"
                                    class="text-sm font-medium leading-none"
                                >
                                    {{ event.label }}
                                </Label>
                                <p class="text-xs text-muted-foreground">
                                    {{ event.description }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quiet Hours -->
                <div class="space-y-4">
                    <h3 class="font-semibold">Horário Silencioso</h3>
                    <div class="flex items-center space-x-2">
                        <Checkbox
                            id="quiet_hours_enabled"
                            v-model:checked="form.notification_settings.quiet_hours.enabled"
                        />
                        <Label for="quiet_hours_enabled">Ativar horário silencioso</Label>
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
                </div>

                <SheetFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="emit('update:open', false)"
                    >
                        Cancelar
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Salvando...' : 'Salvar' }}
                    </Button>
                </SheetFooter>
            </form>
        </SheetContent>
    </Sheet>
</template>

