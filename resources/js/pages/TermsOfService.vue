<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Termos de Servico" />

    <div class="min-h-screen bg-slate-950 text-slate-100">
        <div class="mx-auto flex min-h-screen max-w-4xl flex-col px-6 py-10 lg:px-8">
            <header class="flex flex-wrap items-center justify-between gap-4 border-b border-white/10 pb-6">
                <div>
                    <p class="text-sm font-medium tracking-[0.2em] text-sky-300 uppercase">
                        InovaForm
                    </p>
                    <h1 class="mt-2 text-3xl font-semibold text-white">
                        Termos de Servico
                    </h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-300">
                        Estes termos regem o acesso e o uso da plataforma
                        InovaForm.
                    </p>
                </div>

                <nav class="flex gap-3 text-sm">
                    <Link
                        href="/"
                        class="rounded-full border border-white/15 px-4 py-2 text-slate-200 transition hover:border-sky-400 hover:text-white"
                    >
                        Inicio
                    </Link>
                    <Link
                        href="/politica-de-privacidade"
                        class="rounded-full border border-white/15 px-4 py-2 text-slate-200 transition hover:border-sky-400 hover:text-white"
                    >
                        Privacidade
                    </Link>
                </nav>
            </header>

            <main class="space-y-8 py-8 text-sm leading-7 text-slate-300">
                <section class="rounded-2xl border border-white/10 bg-white/5 p-6">
                    <h2 class="text-lg font-semibold text-white">1. Aceitacao</h2>
                    <p class="mt-3">
                        Ao acessar ou utilizar a InovaForm, o usuario declara que
                        leu, compreendeu e concorda com estes termos e com a
                        Politica de Privacidade aplicavel.
                    </p>
                </section>

                <section class="rounded-2xl border border-white/10 bg-white/5 p-6">
                    <h2 class="text-lg font-semibold text-white">2. Uso da plataforma</h2>
                    <p class="mt-3">
                        A plataforma deve ser utilizada de forma licita, etica e
                        em conformidade com a legislacao vigente. E vedado o uso
                        para fraude, spam, coleta indevida de dados ou qualquer
                        atividade que comprometa a seguranca do servico.
                    </p>
                </section>

                <section class="rounded-2xl border border-white/10 bg-white/5 p-6">
                    <h2 class="text-lg font-semibold text-white">3. Conta e responsabilidade</h2>
                    <p class="mt-3">
                        O usuario e responsavel pela veracidade das informacoes
                        fornecidas, pela guarda de suas credenciais e pelas acoes
                        executadas em sua conta. O compartilhamento indevido de
                        acesso e de responsabilidade do titular da conta.
                    </p>
                </section>

                <section class="rounded-2xl border border-white/10 bg-white/5 p-6">
                    <h2 class="text-lg font-semibold text-white">4. Conteudo e dados</h2>
                    <p class="mt-3">
                        O usuario permanece responsavel pelo conteudo, pelos leads,
                        formularios, arquivos e dados tratados em sua operacao na
                        plataforma, inclusive quanto a bases legais e consentimentos
                        eventualmente exigidos.
                    </p>
                </section>

                <section class="rounded-2xl border border-white/10 bg-white/5 p-6">
                    <h2 class="text-lg font-semibold text-white">5. Disponibilidade</h2>
                    <p class="mt-3">
                        A InovaForm busca manter disponibilidade e estabilidade,
                        mas nao garante funcionamento ininterrupto. Podem ocorrer
                        pausas para manutencao, atualizacoes, falhas de terceiros
                        ou eventos fora do controle razoavel da operacao.
                    </p>
                </section>

                <section class="rounded-2xl border border-white/10 bg-white/5 p-6">
                    <h2 class="text-lg font-semibold text-white">6. Alteracoes e contato</h2>
                    <p class="mt-3">
                        Estes termos podem ser atualizados para refletir ajustes
                        legais, tecnicos ou operacionais. Em caso de duvidas, entre
                        em contato pelo e-mail
                        <a
                            href="mailto:lkmdev01@gmail.com"
                            class="font-medium text-sky-300 underline underline-offset-4"
                        >
                            lkmdev01@gmail.com
                        </a>.
                    </p>
                </section>
            </main>
        </div>
    </div>
</template>
