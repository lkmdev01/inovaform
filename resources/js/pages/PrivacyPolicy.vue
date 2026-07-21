<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Politica de Privacidade" />

    <div class="min-h-screen bg-slate-950 text-slate-100">
        <div
            class="mx-auto flex min-h-screen max-w-4xl flex-col px-6 py-10 lg:px-8"
        >
            <header
                class="flex flex-wrap items-center justify-between gap-4 border-b border-white/10 pb-6"
            >
                <div>
                    <p
                        class="text-sm font-medium tracking-[0.2em] text-sky-300 uppercase"
                    >
                        InovaForm
                    </p>
                    <h1 class="mt-2 text-3xl font-semibold text-white">
                        Politica de Privacidade
                    </h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-300">
                        Esta politica descreve como coletamos, usamos e
                        protegemos os dados tratados pela plataforma InovaForm.
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
                        href="/termos-de-servico"
                        class="rounded-full border border-white/15 px-4 py-2 text-slate-200 transition hover:border-sky-400 hover:text-white"
                    >
                        Termos
                    </Link>
                </nav>
            </header>

            <main class="space-y-8 py-8 text-sm leading-7 text-slate-300">
                <section
                    class="rounded-2xl border border-white/10 bg-white/5 p-6"
                >
                    <h2 class="text-lg font-semibold text-white">
                        1. Dados coletados
                    </h2>
                    <p class="mt-3">
                        Podemos coletar dados fornecidos diretamente por voce,
                        como nome, e-mail, telefone, informacoes de
                        autenticacao, respostas enviadas em formularios e outros
                        dados necessarios para uso da plataforma.
                    </p>
                </section>

                <section
                    class="rounded-2xl border border-white/10 bg-white/5 p-6"
                >
                    <h2 class="text-lg font-semibold text-white">
                        2. Finalidade de uso
                    </h2>
                    <p class="mt-3">
                        Utilizamos os dados para autenticar usuarios, operar os
                        funis e formularios criados na plataforma, prestar
                        suporte, melhorar funcionalidades, prevenir abusos e
                        cumprir obrigacoes legais e regulatorias.
                    </p>
                    <p class="mt-3">
                        Quando o usuario opta por entrar com Google, usamos
                        apenas o identificador da conta, nome, e-mail e foto de
                        perfil fornecidos pelo Google para criar ou acessar a
                        conta no InovaForm. Esses dados nao sao usados para
                        publicidade e nao solicitamos acesso a e-mails,
                        arquivos, contatos ou calendario do Google.
                    </p>
                </section>

                <section
                    class="rounded-2xl border border-white/10 bg-white/5 p-6"
                >
                    <h2 class="text-lg font-semibold text-white">
                        3. Compartilhamento
                    </h2>
                    <p class="mt-3">
                        Os dados podem ser compartilhados com provedores de
                        infraestrutura, armazenamento, autenticacao e
                        comunicacao estritamente para viabilizar a operacao do
                        servico, sempre dentro de necessidades tecnicas e
                        contratuais.
                    </p>
                </section>

                <section
                    class="rounded-2xl border border-white/10 bg-white/5 p-6"
                >
                    <h2 class="text-lg font-semibold text-white">
                        4. Seguranca e retencao
                    </h2>
                    <p class="mt-3">
                        Adotamos medidas tecnicas e administrativas para reduzir
                        riscos de acesso nao autorizado, perda ou alteracao
                        indevida. Os dados sao mantidos pelo tempo necessario
                        para cumprir as finalidades desta politica e exigencias
                        legais.
                    </p>
                </section>

                <section
                    class="rounded-2xl border border-white/10 bg-white/5 p-6"
                >
                    <h2 class="text-lg font-semibold text-white">
                        5. Direitos do titular
                    </h2>
                    <p class="mt-3">
                        O titular pode solicitar informacoes sobre tratamento,
                        correcao, atualizacao ou exclusao de dados, quando
                        aplicavel, por meio do canal de suporte informado pela
                        InovaForm.
                    </p>
                </section>

                <section
                    class="rounded-2xl border border-white/10 bg-white/5 p-6"
                >
                    <h2 class="text-lg font-semibold text-white">6. Contato</h2>
                    <p class="mt-3">
                        Para duvidas sobre esta politica ou sobre o tratamento
                        de dados, entre em contato pelo e-mail
                        <a
                            href="mailto:lkmdev01@gmail.com"
                            class="font-medium text-sky-300 underline underline-offset-4"
                        >
                            lkmdev01@gmail.com </a
                        >.
                    </p>
                </section>
            </main>
        </div>
    </div>
</template>
