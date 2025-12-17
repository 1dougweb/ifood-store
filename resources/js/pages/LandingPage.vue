<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { login, register } from '@/routes';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Mail, MessageSquare, Phone, TrendingUp } from 'lucide-vue-next';
import { ref } from 'vue';

const form = useForm({
    name: '',
    email: '',
    phone: '',
    restaurant_name: '',
    message: '',
});

const isSubmitting = ref(false);

const submit = () => {
    isSubmitting.value = true;
    form.post('/leads', {
        onSuccess: () => {
            form.reset();
            isSubmitting.value = false;
            alert('Obrigado pelo seu interesse! Entraremos em contato em breve.');
        },
        onError: () => {
            isSubmitting.value = false;
        },
    });
};
</script>

<template>
    <Head title="Monitoramento iFood - Gerencie seus pedidos em tempo real" />
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800">
        <!-- Header -->
        <header class="container mx-auto px-4 py-6">
            <nav class="flex items-center justify-between">
                <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">
                    iFood Monitor
                </div>
                <div class="flex gap-4">
                    <Link
                        :href="login()"
                        class="rounded-lg px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-gray-100"
                    >
                        Entrar
                    </Link>
                    <Link
                        :href="register()"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                    >
                        Cadastrar
                    </Link>
                </div>
            </nav>
        </header>

        <!-- Hero Section -->
        <section class="container mx-auto px-4 py-16">
            <div class="mx-auto max-w-4xl text-center">
                <h1 class="mb-6 text-5xl font-bold text-gray-900 dark:text-white">
                    Monitore seus pedidos do iFood em
                    <span class="text-indigo-600 dark:text-indigo-400">tempo real</span>
                </h1>
                <p class="mb-8 text-xl text-gray-600 dark:text-gray-300">
                    Receba alertas via WhatsApp, acompanhe métricas e gerencie seus pedidos
                    de forma inteligente.
                </p>
            </div>
        </section>

        <!-- Features Section -->
        <section class="container mx-auto px-4 py-16">
            <div class="grid gap-8 md:grid-cols-3">
                <Card>
                    <CardHeader>
                        <MessageSquare class="mb-4 h-12 w-12 text-indigo-600" />
                        <CardTitle>Notificações WhatsApp</CardTitle>
                        <CardDescription>
                            Receba alertas instantâneos de novos pedidos, pedidos em atraso
                            e entregas via WhatsApp.
                        </CardDescription>
                    </CardHeader>
                </Card>

                <Card>
                    <CardHeader>
                        <TrendingUp class="mb-4 h-12 w-12 text-indigo-600" />
                        <CardTitle>Métricas e Analytics</CardTitle>
                        <CardDescription>
                            Acompanhe suas vendas, tempo médio de entrega e outras métricas
                            importantes do seu restaurante.
                        </CardDescription>
                    </CardHeader>
                </Card>

                <Card>
                    <CardHeader>
                        <Phone class="mb-4 h-12 w-12 text-indigo-600" />
                        <CardTitle>Dashboard em Tempo Real</CardTitle>
                        <CardDescription>
                            Visualize todos os seus pedidos em um dashboard intuitivo e
                            atualizado em tempo real.
                        </CardDescription>
                    </CardHeader>
                </Card>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="container mx-auto px-4 py-16">
            <Card class="mx-auto max-w-2xl">
                <CardHeader>
                    <CardTitle class="text-center text-3xl">
                        Comece a monitorar seus pedidos hoje
                    </CardTitle>
                    <CardDescription class="text-center">
                        Preencha o formulário abaixo e nossa equipe entrará em contato
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="name">Nome</Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Seu nome"
                                    required
                                />
                            </div>
                            <div class="space-y-2">
                                <Label for="email">Email</Label>
                                <Input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    placeholder="seu@email.com"
                                    required
                                />
                            </div>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="phone">Telefone</Label>
                                <Input
                                    id="phone"
                                    v-model="form.phone"
                                    type="tel"
                                    placeholder="(11) 99999-9999"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label for="restaurant_name">Nome do Restaurante</Label>
                                <Input
                                    id="restaurant_name"
                                    v-model="form.restaurant_name"
                                    type="text"
                                    placeholder="Meu Restaurante"
                                />
                            </div>
                        </div>
                        <div class="space-y-2">
                            <Label for="message">Mensagem (opcional)</Label>
                            <textarea
                                id="message"
                                v-model="form.message"
                                class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                placeholder="Conte-nos mais sobre suas necessidades..."
                            />
                        </div>
                        <Button type="submit" class="w-full" :disabled="isSubmitting">
                            <Mail class="mr-2 h-4 w-4" />
                            {{ isSubmitting ? 'Enviando...' : 'Enviar' }}
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </section>

        <!-- Footer -->
        <footer class="container mx-auto border-t border-gray-200 px-4 py-8 dark:border-gray-700">
            <div class="text-center text-sm text-gray-600 dark:text-gray-400">
                © 2025 iFood Monitor. Todos os direitos reservados.
            </div>
        </footer>
    </div>
</template>

