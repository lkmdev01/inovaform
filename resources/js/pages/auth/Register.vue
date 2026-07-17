<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { home, login } from '@/routes';
import { store } from '@/routes/register';
</script>

<template>

    <Head title="Criar conta">
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
            class="pointer-events-none absolute inset-0 bg-[radial-gradient(#8eb2ff_0.55px,transparent_0.55px)] bg-size-[24px_24px] opacity-70" />

        <div class="relative mx-auto flex min-h-screen w-full max-w-6xl flex-col p-6 lg:p-10">
            <header class="flex items-center justify-between">
                <Link :href="home()"
                    class="rounded-full border border-[#2952ad] bg-[#0a1633]/80 px-4 py-2 text-sm font-semibold tracking-wide">
                    INOVAFORM
                </Link>

                <Link :href="login()"
                    class="rounded-full border border-[#3a62c2] bg-[#0e1f43] px-5 py-2 text-sm font-semibold text-[#d0e0ff] transition hover:bg-[#162e63]">
                    Ja tenho conta
                </Link>
            </header>

            <main class="grid flex-1 items-center gap-10 py-10 lg:grid-cols-[1.05fr_0.95fr] lg:py-16">
                <section class="space-y-6">
                    <p
                        class="inline-flex rounded-full border border-[#2f60cb] bg-[#0c1f46] px-4 py-1.5 text-xs uppercase tracking-[0.2em] text-[#90b2ff]">
                        Criacao de conta
                    </p>
                    <h1 class="max-w-2xl text-4xl leading-tight font-bold text-white lg:text-6xl">
                        Coloque sua operacao em orbita com a Inovaform.
                    </h1>
                    <p class="max-w-2xl text-lg text-[#a9c2f7]">
                        Crie sua conta e comece a estruturar formularios, funis e paginas de alta conversao no mesmo
                        ecossistema.
                    </p>
                </section>

                <section
                    class="rounded-4xl border border-[#2f4f9d] bg-[#081126]/95 p-6 shadow-[0_25px_80px_rgba(5,12,34,0.75)] lg:p-8">
                    <div class="mb-6">
                        <p class="text-sm font-medium uppercase tracking-[0.18em] text-[#8fb2f5]">Cadastro</p>
                        <h2 class="mt-3 text-3xl font-bold text-white">Criar conta</h2>
                        <p class="mt-2 text-sm leading-relaxed text-[#9ab9ea]">
                            Preencha seus dados e abra seu ambiente de trabalho.
                        </p>
                    </div>

                    <Form v-bind="store.form()" :reset-on-success="['password', 'password_confirmation']"
                        v-slot="{ errors, processing }" class="space-y-5">
                        <div class="space-y-2">
                            <Label for="name" class="text-[#cfe0ff]">Nome</Label>
                            <Input id="name" type="text" required autofocus :tabindex="1" autocomplete="name"
                                name="name" placeholder="Seu nome completo"
                                class="h-12 rounded-2xl border-[#274b99] bg-[#0a1733] text-white placeholder:text-[#6f89b8]" />
                            <InputError :message="errors.name" />
                        </div>

                        <div class="space-y-2">
                            <Label for="email" class="text-[#cfe0ff]">E-mail</Label>
                            <Input id="email" type="email" required :tabindex="2" autocomplete="email" name="email"
                                placeholder="voce@empresa.com"
                                class="h-12 rounded-2xl border-[#274b99] bg-[#0a1733] text-white placeholder:text-[#6f89b8]" />
                            <InputError :message="errors.email" />
                        </div>

                        <div class="space-y-2">
                            <Label for="password" class="text-[#cfe0ff]">Senha</Label>
                            <Input id="password" type="password" required :tabindex="3" autocomplete="new-password"
                                name="password" placeholder="Crie uma senha"
                                class="h-12 rounded-2xl border-[#274b99] bg-[#0a1733] text-white placeholder:text-[#6f89b8]" />
                            <InputError :message="errors.password" />
                        </div>

                        <div class="space-y-2">
                            <Label for="password_confirmation" class="text-[#cfe0ff]">Confirmar senha</Label>
                            <Input id="password_confirmation" type="password" required :tabindex="4"
                                autocomplete="new-password" name="password_confirmation" placeholder="Repita sua senha"
                                class="h-12 rounded-2xl border-[#274b99] bg-[#0a1733] text-white placeholder:text-[#6f89b8]" />
                            <InputError :message="errors.password_confirmation" />
                        </div>

                        <Button type="submit"
                            class="h-12 w-full rounded-2xl bg-[#2f60cb] text-sm font-semibold text-white hover:bg-[#3e73e1]"
                            tabindex="5" :disabled="processing" data-test="register-user-button">
                            <Spinner v-if="processing" />
                            Criar conta
                        </Button>
                    </Form>

                    <p class="mt-6 text-center text-sm text-[#8ea9d8]">
                        Ja possui acesso?
                        <Link :href="login()" class="font-semibold text-white transition hover:text-[#a9c7ff]"
                            :tabindex="6">
                            Entrar
                        </Link>
                    </p>
                </section>
            </main>
        </div>
    </div>
</template>
