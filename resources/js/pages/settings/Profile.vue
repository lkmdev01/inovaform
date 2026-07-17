<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import { edit as appearanceEdit } from '@/routes/appearance';
import { edit } from '@/routes/profile';
import { show as twoFactorShow } from '@/routes/two-factor';
import { edit as passwordEdit } from '@/routes/user-password';
import { send } from '@/routes/verification';

type Props = {
    mustVerifyEmail: boolean;
    status?: string;
};

const props = defineProps<Props>();

const page = usePage<{ auth: { user: { name: string; email: string; email_verified_at: string | null } } }>();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <Head title="Perfil" />

    <div class="min-h-screen bg-[radial-gradient(circle_at_10%_0%,#102a5f_0%,#07132d_35%,#030917_100%)] text-[#dbe9ff]">
        <header class="border-b border-[#16315f] bg-[#07132de6] backdrop-blur">
            <div class="mx-auto flex h-14 max-w-6xl items-center justify-between px-5">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg border border-[#2e63c8] bg-[#0a1f49] text-lg font-bold text-white">
                        IN
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-white">Configuracoes da conta</p>
                        <p class="text-xs text-[#89a7df]">Atualize seu perfil e seguranca</p>
                    </div>
                </div>

                <Link
                    :href="dashboard().url"
                    class="rounded-md border border-[#2d4f87] bg-[#0b2248] px-3 py-1.5 text-xs text-[#c9ddff]"
                >
                    Voltar ao dashboard
                </Link>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-5 py-6">
            <nav class="mb-5 flex flex-wrap gap-2 rounded-xl border border-[#244677] bg-[#061534] p-2 text-xs">
                <Link
                    :href="edit().url"
                    class="rounded-lg px-3 py-2"
                    :class="$page.component === 'settings/Profile' ? 'bg-[#1f5ecf] text-white' : 'text-[#9ec0ff] hover:bg-[#0f2851]'"
                >
                    Perfil
                </Link>
                <Link
                    :href="passwordEdit().url"
                    class="rounded-lg px-3 py-2"
                    :class="$page.component === 'settings/Password' ? 'bg-[#1f5ecf] text-white' : 'text-[#9ec0ff] hover:bg-[#0f2851]'"
                >
                    Senha
                </Link>
                <Link
                    :href="appearanceEdit().url"
                    class="rounded-lg px-3 py-2"
                    :class="$page.component === 'settings/Appearance' ? 'bg-[#1f5ecf] text-white' : 'text-[#9ec0ff] hover:bg-[#0f2851]'"
                >
                    Aparencia
                </Link>
                <Link
                    :href="twoFactorShow().url"
                    class="rounded-lg px-3 py-2"
                    :class="$page.component === 'settings/TwoFactor' ? 'bg-[#1f5ecf] text-white' : 'text-[#9ec0ff] hover:bg-[#0f2851]'"
                >
                    2FA
                </Link>
            </nav>

            <section class="rounded-xl border border-[#284b83] bg-[#081b3b] p-5">
                <h1 class="text-lg font-semibold text-white">Informacoes do perfil</h1>
                <p class="mt-1 text-sm text-[#95b4e8]">Atualize nome e e-mail da sua conta.</p>

                <Form
                    v-bind="ProfileController.update.form()"
                    class="mt-5 space-y-5"
                    v-slot="{ errors, processing, recentlySuccessful }"
                >
                    <div class="grid gap-2">
                        <Label for="name" class="text-[#cfe0ff]">Nome</Label>
                        <Input
                            id="name"
                            class="mt-1 w-full border-[#30558f] bg-[#0b2248] text-white"
                            name="name"
                            :default-value="user.name"
                            required
                            autocomplete="name"
                            placeholder="Seu nome"
                        />
                        <InputError class="mt-1" :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email" class="text-[#cfe0ff]">E-mail</Label>
                        <Input
                            id="email"
                            type="email"
                            class="mt-1 w-full border-[#30558f] bg-[#0b2248] text-white"
                            name="email"
                            :default-value="user.email"
                            required
                            autocomplete="username"
                            placeholder="seu@email.com"
                        />
                        <InputError class="mt-1" :message="errors.email" />
                    </div>

                    <div v-if="props.mustVerifyEmail && !user.email_verified_at" class="rounded-lg border border-[#3b5f96] bg-[#0a2146] p-3">
                        <p class="text-sm text-[#c2d9ff]">
                            Seu e-mail ainda nao foi verificado.
                            <Link :href="send()" as="button" class="ml-1 underline underline-offset-4">
                                Reenviar verificacao
                            </Link>
                        </p>
                        <p v-if="props.status === 'verification-link-sent'" class="mt-2 text-sm text-emerald-300">
                            Link enviado com sucesso.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <Button :disabled="processing" data-test="update-profile-button" class="bg-[#1f5ecf] hover:bg-[#2c6ee3]">
                            Salvar alteracoes
                        </Button>
                        <p v-show="recentlySuccessful" class="text-sm text-[#9fd0ff]">Salvo.</p>
                    </div>
                </Form>
            </section>

            <section class="mt-6 rounded-xl border border-[#284b83] bg-[#081b3b] p-5">
                <DeleteUser />
            </section>
        </main>
    </div>
</template>
