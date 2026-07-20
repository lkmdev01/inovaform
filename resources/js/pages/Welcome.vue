<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { dashboard, login, register } from '@/routes';
import type { Auth } from '@/types';

const page = usePage<{ auth: Auth }>();

const funnelSteps = [
    {
        title: 'Captura',
        description: 'Landing pages e formularios com blocos arrastaveis.',
    },
    {
        title: 'Qualificacao',
        description: 'Regras para score, tags e priorizacao de contatos.',
    },
    {
        title: 'Conversao',
        description: 'Automacoes para follow-up, vendas e onboarding.',
    },
];

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);
</script>

<template>
    <Head title="Inovaform">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link
            rel="preconnect"
            href="https://fonts.gstatic.com"
            crossorigin="anonymous"
        />
        <link
            href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div
        class="relative min-h-screen overflow-hidden bg-[#030916] text-[#d6e5ff]"
        style="
            font-family: 'Space Grotesk', ui-sans-serif, system-ui, sans-serif;
        "
    >
        <div
            class="absolute inset-0 bg-[radial-gradient(circle_at_15%_20%,#1347d455_0%,transparent_40%),radial-gradient(circle_at_80%_10%,#1e3d8a66_0%,transparent_40%),radial-gradient(circle_at_50%_120%,#0a1f4e_0%,transparent_55%)]"
        />
        <div
            class="pointer-events-none absolute inset-0 [background-image:radial-gradient(#8eb2ff_0.55px,transparent_0.55px)] [background-size:24px_24px] opacity-70"
        />

        <div
            class="relative mx-auto flex min-h-screen w-full max-w-6xl flex-col p-6 lg:p-10"
        >
            <header class="flex items-center justify-between">
                <div
                    class="rounded-full border border-[#2952ad] bg-[#0a1633]/80 px-4 py-2 text-sm font-semibold tracking-wide"
                >
                    INOVAFORM
                </div>

                <Link
                    v-if="page.props.auth.user"
                    :href="dashboard()"
                    class="rounded-full border border-[#3f6ed4] bg-[#10224d] px-5 py-2 text-sm font-medium text-[#d6e5ff] transition hover:bg-[#19336f]"
                >
                    Painel
                </Link>

                <template v-else>
                    <nav class="flex items-center gap-3 text-sm">
                        <Link
                            :href="login()"
                            class="rounded-full border border-transparent px-4 py-2 font-medium text-[#adc5f9] transition hover:border-[#305cbf]"
                        >
                            Entrar
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="register()"
                            class="rounded-full bg-[#2f60cb] px-5 py-2 font-semibold text-white transition hover:bg-[#3c72e4]"
                        >
                            Criar conta
                        </Link>
                    </nav>
                </template>
            </header>

            <main
                class="grid flex-1 items-center gap-8 py-10 lg:grid-cols-[1.2fr_1fr] lg:py-16"
            >
                <section class="space-y-6">
                    <p
                        class="inline-flex rounded-full border border-[#2f60cb] bg-[#0c1f46] px-4 py-1.5 text-xs tracking-[0.2em] text-[#90b2ff] uppercase"
                    >
                        SaaS para funis e formularios
                    </p>
                    <h1
                        class="max-w-2xl text-4xl leading-tight font-bold text-white lg:text-6xl"
                    >
                        Inovaform: formularios e funis com velocidade orbital.
                    </h1>
                    <p class="max-w-2xl text-lg text-[#a9c2f7]">
                        Crie jornadas de captura, qualificacao e venda em uma
                        interface moderna, elegante e focada em conversao.
                    </p>

                    <div class="flex flex-wrap items-center gap-3">
                        <Link
                            :href="page.props.auth.user ? dashboard() : login()"
                            class="rounded-full bg-[#2f60cb] px-6 py-3 text-sm font-semibold text-white transition hover:bg-[#3e73e1]"
                        >
                            {{
                                page.props.auth.user
                                    ? 'Ir para painel'
                                    : 'Comecar agora'
                            }}
                        </Link>
                        <Link
                            v-if="canRegister && !page.props.auth.user"
                            :href="register()"
                            class="rounded-full border border-[#3a62c2] bg-[#0e1f43] px-6 py-3 text-sm font-semibold text-[#d0e0ff] transition hover:bg-[#162e63]"
                        >
                            Teste gratuito
                        </Link>
                    </div>

                    <div class="grid gap-4 pt-4 sm:grid-cols-3">
                        <article
                            v-for="step in funnelSteps"
                            :key="step.title"
                            class="rounded-2xl border border-[#274b99] bg-[#0a1733]/90 p-4 shadow-[0_0_0_1px_rgba(81,129,243,0.2)]"
                        >
                            <h2
                                class="mb-2 text-sm font-semibold text-[#e1ecff]"
                            >
                                {{ step.title }}
                            </h2>
                            <p class="text-sm leading-relaxed text-[#9cb9ef]">
                                {{ step.description }}
                            </p>
                        </article>
                    </div>
                </section>

                <section
                    class="rounded-3xl border border-[#2f4f9d] bg-[#081126]/95 p-6 shadow-[0_25px_80px_rgba(5,12,34,0.75)]"
                >
                    <div class="mb-5 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-white">
                            Painel de orbitas
                        </h2>
                        <span
                            class="rounded-full bg-[#123679] px-3 py-1 text-xs font-medium text-[#c1d8ff]"
                        >
                            Ao vivo
                        </span>
                    </div>
                    <div class="space-y-4">
                        <div
                            class="rounded-xl border border-[#284a92] bg-[#0c1b3c] p-4"
                        >
                            <p
                                class="text-xs tracking-[0.12em] text-[#84aaf5] uppercase"
                            >
                                Conversao media
                            </p>
                            <p class="mt-2 text-3xl font-bold text-white">
                                28.4%
                            </p>
                            <p class="mt-1 text-xs text-[#87b0ff]">
                                +6,2% em relação ao último ciclo
                            </p>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div
                                class="rounded-xl border border-[#284a92] bg-[#0c1b3c] p-4"
                            >
                                <p class="text-xs text-[#8cb1f7]">Leads hoje</p>
                                <p
                                    class="mt-1 text-xl font-semibold text-white"
                                >
                                    1.284
                                </p>
                            </div>
                            <div
                                class="rounded-xl border border-[#284a92] bg-[#0c1b3c] p-4"
                            >
                                <p class="text-xs text-[#8cb1f7]">CPL medio</p>
                                <p
                                    class="mt-1 text-xl font-semibold text-white"
                                >
                                    R$ 7,32
                                </p>
                            </div>
                        </div>
                        <div
                            class="h-28 rounded-xl border border-[#274d9f] bg-gradient-to-r from-[#163a8d] via-[#133576] to-[#0f2756] p-4"
                        >
                            <div
                                class="h-full w-full animate-pulse rounded-md border border-[#6e9fff4f] bg-[linear-gradient(110deg,rgba(147,189,255,0.2)_10%,rgba(147,189,255,0.05)_45%,rgba(147,189,255,0.2)_90%)]"
                            />
                        </div>
                    </div>
                </section>
            </main>

            <footer
                class="flex flex-col gap-3 pb-6 text-center text-xs text-[#7d9fdb] lg:text-left"
            >
                <p>
                    Inovaform - Plataforma SaaS para funis, formularios e
                    automacao comercial.
                </p>
                <div class="flex flex-wrap items-center justify-center gap-4 lg:justify-start">
                    <Link
                        href="/politica-de-privacidade"
                        class="transition hover:text-white"
                    >
                        Politica de Privacidade
                    </Link>
                    <Link
                        href="/termos-de-servico"
                        class="transition hover:text-white"
                    >
                        Termos de Servico
                    </Link>
                </div>
            </footer>
        </div>
    </div>
</template>
