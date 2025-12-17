<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { dashboard } from '@/routes';
import restaurantsRoute from '@/routes/restaurants';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { ref, watch } from 'vue';

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
        title: 'Novo Restaurante',
    },
];

const form = useForm({
    name: '',
    cnpj: '',
    address: '',
    phone: '',
    whatsapp_number: '',
    ifood_client_id: '',
    ifood_client_secret: '',
    is_active: true,
    notification_settings: {
        enabled_events: [
            'new_order',
            'delayed_order',
            'delivered_order',
            'cancelled_order',
        ],
    },
});

// Máscaras
const cnpjMask = (value: string) => {
    const numbers = value.replace(/\D/g, '');
    if (numbers.length <= 14) {
        return numbers
            .replace(/(\d{2})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1/$2')
            .replace(/(\d{4})(\d)/, '$1-$2');
    }
    return value;
};

const phoneMask = (value: string) => {
    const numbers = value.replace(/\D/g, '');
    if (numbers.length <= 10) {
        return numbers
            .replace(/(\d{2})(\d)/, '($1) $2')
            .replace(/(\d{4})(\d)/, '$1-$2');
    } else if (numbers.length <= 11) {
        return numbers
            .replace(/(\d{2})(\d)/, '($1) $2')
            .replace(/(\d{5})(\d)/, '$1-$2');
    }
    return value;
};

const whatsappMask = (value: string) => {
    return value.replace(/\D/g, '');
};

// Campos com máscara visual
const cnpjDisplay = ref('');
const phoneDisplay = ref('');

watch(cnpjDisplay, (value) => {
    const masked = cnpjMask(value);
    cnpjDisplay.value = masked;
    form.cnpj = masked.replace(/\D/g, '');
});

watch(phoneDisplay, (value) => {
    const masked = phoneMask(value);
    phoneDisplay.value = masked;
    form.phone = masked.replace(/\D/g, '');
});

watch(() => form.whatsapp_number, (value) => {
    form.whatsapp_number = whatsappMask(value);
});

const submit = () => {
    form.post(restaurantsRoute.store().url);
};
</script>

<template>
    <Head title="Novo Restaurante" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
            <div class="flex items-center gap-4">
                <Link :href="restaurantsRoute.index().url">
                    <Button variant="ghost" size="icon">
                        <ArrowLeft class="h-5 w-5" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-3xl font-bold">Novo Restaurante</h1>
                    <p class="text-muted-foreground">
                        Adicione um novo restaurante para monitorar
                    </p>
                </div>
            </div>

            <form @submit.prevent="submit" class="max-w-2xl space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Informações Básicas</CardTitle>
                        <CardDescription>
                            Dados principais do restaurante
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="space-y-2">
                            <Label for="name">Nome do Restaurante *</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                placeholder="Ex: Restaurante do João"
                                required
                            />
                            <p
                                v-if="form.errors.name"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="cnpj">CNPJ</Label>
                            <Input
                                id="cnpj"
                                v-model="cnpjDisplay"
                                type="text"
                                placeholder="00.000.000/0000-00"
                                maxlength="18"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label for="address">Endereço</Label>
                            <textarea
                                id="address"
                                v-model="form.address"
                                class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                placeholder="Endereço completo do restaurante"
                            />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Contato</CardTitle>
                        <CardDescription>
                            Informações de contato e WhatsApp
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="space-y-2">
                            <Label for="phone">Telefone</Label>
                            <Input
                                id="phone"
                                v-model="phoneDisplay"
                                type="tel"
                                placeholder="(11) 99999-9999"
                                maxlength="15"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label for="whatsapp_number">WhatsApp *</Label>
                            <Input
                                id="whatsapp_number"
                                v-model="form.whatsapp_number"
                                type="tel"
                                placeholder="5511999999999"
                                maxlength="15"
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

                <Card>
                    <CardHeader>
                        <CardTitle>Configurações</CardTitle>
                        <CardDescription>
                            Opções de ativação e notificações
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex items-center space-x-2">
                            <Checkbox
                                id="is_active"
                                v-model:checked="form.is_active"
                            />
                            <Label
                                for="is_active"
                                class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                            >
                                Restaurante ativo
                            </Label>
                        </div>
                    </CardContent>
                </Card>

                <div class="flex gap-4">
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Salvando...' : 'Salvar' }}
                    </Button>
                    <Link :href="restaurantsRoute.index().url">
                        <Button type="button" variant="outline">Cancelar</Button>
                    </Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

