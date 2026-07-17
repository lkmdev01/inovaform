<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { home, register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();

</script>

<template>

    <Head title="Entrar">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
        <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&display=swap"
            rel="stylesheet" />
    </Head>

    <div class="relative min-h-screen overflow-hidden bg-[#030916] text-[#d6e5ff]"
        style="font-family: 'Space Grotesk', ui-sans-serif, system-ui, sans-serif">
        <div
            class="absolute inset-0 bg-[radial-gradient(circle_at_15%_20%,#1347d455_0%,transparent_40%),radial-gradient(circle_at_80%_10%,#1e3d8a66_0%,transparent_40%),radial-gradient(circle_at_50%_120%,#0a1f4e_0%,transparent_55%)]" />
        <div
            class="pointer-events-none absolute inset-0 opacity-70 [background-image:radial-gradient(#8eb2ff_0.55px,transparent_0.55px)] [background-size:24px_24px]" />

        <div class="relative mx-auto flex min-h-screen w-full max-w-6xl flex-col p-6 lg:p-10">
            <header class="flex items-center justify-between">
                <Link :href="home()"
                    class="rounded-full border border-[#2952ad] bg-[#0a1633]/80 px-4 py-2 text-sm font-semibold tracking-wide">
                    INOVAFORM
                </Link>

                <div class="flex items-center gap-3 text-sm">
                    <span class="hidden text-[#97b4eb] md:inline">Acesse sua cabine de controle</span>
                    <Link v-if="canRegister" :href="register()"
                        class="rounded-full border border-[#3a62c2] bg-[#0e1f43] px-5 py-2 font-semibold text-[#d0e0ff] transition hover:bg-[#162e63]">
                        Criar conta
                    </Link>
                </div>
            </header>

            <main class="grid flex-1 items-center gap-10 py-10 lg:grid-cols-[1.05fr_0.95fr] lg:py-16">
                <section class="space-y-6">
                    <p
                        class="inline-flex rounded-full border border-[#2f60cb] bg-[#0c1f46] px-4 py-1.5 text-xs uppercase tracking-[0.2em] text-[#90b2ff]">
                        Area de acesso
                    </p>
                    <h1 class="max-w-2xl text-4xl leading-tight font-bold text-white lg:text-6xl">
                        Entre na sua orbita e retome seus funis.
                    </h1>
                    <p class="max-w-2xl text-lg text-[#a9c2f7]">
                        O painel da Inovaform continua com seu tema, etapas e leads prontos para operacao.
                    </p>
                </section>

                <section
                    class="rounded-[2rem] border border-[#2f4f9d] bg-[#081126]/95 p-6 shadow-[0_25px_80px_rgba(5,12,34,0.75)] lg:p-8">
                    <div class="mb-6">
                        <p class="text-sm font-medium uppercase tracking-[0.18em] text-[#8fb2f5]">Login</p>
                        <h2 class="mt-3 text-3xl font-bold text-white">Acessar conta</h2>
                        <p class="mt-2 text-sm leading-relaxed text-[#9ab9ea]">
                            Use seu e-mail e senha para entrar no painel.
                        </p>
                    </div>

                    <div v-if="status"
                        class="mb-4 rounded-2xl border border-emerald-400/30 bg-emerald-500/10 px-4 py-3 text-sm font-medium text-emerald-200">
                        {{ status }}
                    </div>

                    <Form v-bind="store.form()" :reset-on-success="['password']" v-slot="{ errors, processing }"
                        class="space-y-5">
                        <div class="space-y-2">
                            <Label for="email" class="text-[#cfe0ff]">E-mail</Label>
                            <Input id="email" type="email" name="email" required autofocus :tabindex="1"
                                autocomplete="email" placeholder="voce@empresa.com"
                                class="h-12 rounded-2xl border-[#274b99] bg-[#0a1733] text-white placeholder:text-[#6f89b8]" />
                            <InputError :message="errors.email" />
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center justify-between gap-3">
                                <Label for="password" class="text-[#cfe0ff]">Senha</Label>
                                <Link v-if="canResetPassword" :href="request()"
                                    class="text-sm text-[#8fb2f5] transition hover:text-white" :tabindex="5">
                                    Esqueci a senha
                                </Link>
                            </div>
                            <Input id="password" type="password" name="password" required :tabindex="2"
                                autocomplete="current-password" placeholder="Sua senha"
                                class="h-12 rounded-2xl border-[#274b99] bg-[#0a1733] text-white placeholder:text-[#6f89b8]" />
                            <InputError :message="errors.password" />
                        </div>

                        <Label for="remember" class="flex items-center gap-3 text-sm text-[#a8c2f0]">
                            <Checkbox id="remember" name="remember" :tabindex="3" />
                            <span>Manter sessao ativa neste dispositivo</span>
                        </Label>

                        <Button type="submit"
                            class="h-12 w-full rounded-2xl bg-[#2f60cb] text-sm font-semibold text-white hover:bg-[#3e73e1]"
                            :tabindex="4" :disabled="processing" data-test="login-button">
                            <Spinner v-if="processing" />
                            Entrar no painel
                        </Button>
                    </Form>

                    <p v-if="canRegister" class="mt-6 text-center text-sm text-[#8ea9d8]">
                        Ainda nao tem conta?
                        <Link :href="register()" :tabindex="6"
                            class="font-semibold text-white transition hover:text-[#a9c7ff]">
                            Criar agora
                        </Link>
                    </p>
                </section>
            </main>
        </div>
    </div>
</template>
