<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import { dashboard } from '@/routes';
import { edit as appearanceEdit } from '@/routes/appearance';
import { edit as profileEdit } from '@/routes/profile';
import { show as twoFactorShow } from '@/routes/two-factor';
import { edit as passwordEdit } from '@/routes/user-password';
</script>

<template>
    <Head title="Aparencia" />

    <div
        class="min-h-screen bg-[radial-gradient(circle_at_10%_0%,#102a5f_0%,#07132d_35%,#030917_100%)] text-[#dbe9ff]"
    >
        <header class="border-b border-[#16315f] bg-[#07132de6] backdrop-blur">
            <div
                class="mx-auto flex h-14 max-w-6xl items-center justify-between px-5"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-lg border border-[#2e63c8] bg-[#0a1f49] text-lg font-bold text-white"
                    >
                        IN
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-white">
                            Configuracoes da conta
                        </p>
                        <p class="text-xs text-[#89a7df]">
                            Tema e preferencias visuais
                        </p>
                    </div>
                </div>

                <Link
                    :href="dashboard().url"
                    class="rounded-md border border-[#2d4f87] bg-[#0b2248] px-3 py-1.5 text-xs text-[#c9ddff]"
                >
                    Voltar ao painel
                </Link>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-5 py-6">
            <nav
                class="mb-5 flex flex-wrap gap-2 rounded-xl border border-[#244677] bg-[#061534] p-2 text-xs"
            >
                <Link
                    :href="profileEdit().url"
                    class="rounded-lg px-3 py-2"
                    :class="
                        $page.component === 'settings/Profile'
                            ? 'bg-[#1f5ecf] text-white'
                            : 'text-[#9ec0ff] hover:bg-[#0f2851]'
                    "
                >
                    Perfil
                </Link>
                <Link
                    :href="passwordEdit().url"
                    class="rounded-lg px-3 py-2"
                    :class="
                        $page.component === 'settings/Password'
                            ? 'bg-[#1f5ecf] text-white'
                            : 'text-[#9ec0ff] hover:bg-[#0f2851]'
                    "
                >
                    Senha
                </Link>
                <Link
                    :href="appearanceEdit().url"
                    class="rounded-lg px-3 py-2"
                    :class="
                        $page.component === 'settings/Appearance'
                            ? 'bg-[#1f5ecf] text-white'
                            : 'text-[#9ec0ff] hover:bg-[#0f2851]'
                    "
                >
                    Aparencia
                </Link>
                <Link
                    :href="twoFactorShow().url"
                    class="rounded-lg px-3 py-2"
                    :class="
                        $page.component === 'settings/TwoFactor'
                            ? 'bg-[#1f5ecf] text-white'
                            : 'text-[#9ec0ff] hover:bg-[#0f2851]'
                    "
                >
                    2FA
                </Link>
            </nav>

            <section
                class="rounded-xl border border-[#284b83] bg-[#081b3b] p-5"
            >
                <h1 class="text-lg font-semibold text-white">Aparencia</h1>
                <p class="mt-1 text-sm text-[#95b4e8]">
                    Defina se quer usar tema claro, escuro ou sistema.
                </p>

                <div
                    class="mt-5 rounded-lg border border-[#30558f] bg-[#0b2248] p-4"
                >
                    <AppearanceTabs />
                </div>
            </section>
        </main>
    </div>
</template>
