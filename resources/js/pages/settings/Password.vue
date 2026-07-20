<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import PasswordController from '@/actions/App/Http/Controllers/Settings/PasswordController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import { edit as appearanceEdit } from '@/routes/appearance';
import { edit as profileEdit } from '@/routes/profile';
import { show as twoFactorShow } from '@/routes/two-factor';
import { edit as passwordEdit } from '@/routes/user-password';
</script>

<template>
    <Head title="Senha" />

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
                            Seguranca e acessos
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
                <h1 class="text-lg font-semibold text-white">
                    Atualizar senha
                </h1>
                <p class="mt-1 text-sm text-[#95b4e8]">
                    Use uma senha forte e exclusiva para manter sua conta
                    segura.
                </p>

                <Form
                    v-bind="PasswordController.update.form()"
                    :options="{ preserveScroll: true }"
                    reset-on-success
                    :reset-on-error="[
                        'password',
                        'password_confirmation',
                        'current_password',
                    ]"
                    class="mt-5 space-y-5"
                    v-slot="{ errors, processing, recentlySuccessful }"
                >
                    <div class="grid gap-2">
                        <Label for="current_password" class="text-[#cfe0ff]"
                            >Senha atual</Label
                        >
                        <Input
                            id="current_password"
                            name="current_password"
                            type="password"
                            class="mt-1 w-full border-[#30558f] bg-[#0b2248] text-white"
                            autocomplete="current-password"
                            placeholder="Senha atual"
                        />
                        <InputError :message="errors.current_password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password" class="text-[#cfe0ff]"
                            >Nova senha</Label
                        >
                        <Input
                            id="password"
                            name="password"
                            type="password"
                            class="mt-1 w-full border-[#30558f] bg-[#0b2248] text-white"
                            autocomplete="new-password"
                            placeholder="Nova senha"
                        />
                        <InputError :message="errors.password" />
                    </div>

                    <div class="grid gap-2">
                        <Label
                            for="password_confirmation"
                            class="text-[#cfe0ff]"
                            >Confirmar nova senha</Label
                        >
                        <Input
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            class="mt-1 w-full border-[#30558f] bg-[#0b2248] text-white"
                            autocomplete="new-password"
                            placeholder="Confirme a nova senha"
                        />
                        <InputError :message="errors.password_confirmation" />
                    </div>

                    <div class="flex items-center gap-3">
                        <Button
                            :disabled="processing"
                            data-test="update-password-button"
                            class="bg-[#1f5ecf] hover:bg-[#2c6ee3]"
                        >
                            Salvar senha
                        </Button>
                        <p
                            v-show="recentlySuccessful"
                            class="text-sm text-[#9fd0ff]"
                        >
                            Salvo.
                        </p>
                    </div>
                </Form>
            </section>
        </main>
    </div>
</template>
