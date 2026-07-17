<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';
import { store } from '@/routes/password/confirm';
</script>

<template>
    <Head title="Confirmar senha" />

    <div class="min-h-screen bg-[radial-gradient(circle_at_10%_0%,#102a5f_0%,#07132d_35%,#030917_100%)] text-[#dbe9ff]">
        <header class="border-b border-[#16315f] bg-[#07132de6] backdrop-blur">
            <div class="mx-auto flex h-14 max-w-6xl items-center justify-between px-5">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg border border-[#2e63c8] bg-[#0a1f49] text-lg font-bold text-white">
                        IN
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-white">Confirmacao de seguranca</p>
                        <p class="text-xs text-[#89a7df]">Confirme sua senha para continuar</p>
                    </div>
                </div>

                <Link :href="dashboard().url" class="rounded-md border border-[#2d4f87] bg-[#0b2248] px-3 py-1.5 text-xs text-[#c9ddff]">
                    Voltar ao dashboard
                </Link>
            </div>
        </header>

        <main class="mx-auto flex max-w-6xl items-start justify-center px-5 py-8">
            <section class="w-full max-w-xl rounded-xl border border-[#284b83] bg-[#081b3b] p-5">
                <h1 class="text-lg font-semibold text-white">Confirmar senha</h1>
                <p class="mt-1 text-sm text-[#95b4e8]">
                    Esta e uma area segura da plataforma. Digite sua senha atual para continuar.
                </p>

                <Form
                    v-bind="store.form()"
                    reset-on-success
                    v-slot="{ errors, processing }"
                    class="mt-5 space-y-5"
                >
                    <div class="grid gap-2">
                        <Label htmlFor="password" class="text-[#cfe0ff]">Senha</Label>
                        <Input
                            id="password"
                            type="password"
                            name="password"
                            class="mt-1 w-full border-[#30558f] bg-[#0b2248] text-white"
                            required
                            autocomplete="current-password"
                            autofocus
                            placeholder="Digite sua senha"
                        />

                        <InputError :message="errors.password" />
                    </div>

                    <Button
                        class="w-full bg-[#1f5ecf] hover:bg-[#2c6ee3]"
                        :disabled="processing"
                        data-test="confirm-password-button"
                    >
                        <Spinner v-if="processing" />
                        Confirmar senha
                    </Button>
                </Form>
            </section>
        </main>
    </div>
</template>
