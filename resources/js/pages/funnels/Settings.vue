<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    CheckCircle2,
    CircleHelp,
    CircleUserRound,
    Eye,
    Globe2,
    ListTree,
    Palette,
    PlugZap,
    Search,
    Settings,
    Share2,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import FunnelController from '@/actions/App/Http/Controllers/FunnelController';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { show as showPublicFunnel } from '@/routes/funnels/public';

type SettingsTab = 'publication' | 'seo' | 'domain' | 'connections';

type CustomDomainStatus = {
    status:
        | 'not_configured'
        | 'checking_disabled'
        | 'pending_dns'
        | 'tls_pending'
        | 'ready';
    label: string;
    message: string;
    dns_ready: boolean;
    tls_ready: boolean;
    expected_target: string | null;
    checked_at: string | null;
};

type FunnelSettings = {
    logoUrl?: string | null;
    faviconUrl?: string | null;
    seoTitle?: string | null;
    seoDescription?: string | null;
    seoImageUrl?: string | null;
    expiresAt?: string | null;
    unavailableTitle?: string | null;
    unavailableDescription?: string | null;
};

const props = defineProps<{
    funnel: {
        id: number;
        name: string;
        slug: string;
        is_active: boolean;
        custom_domain: string | null;
    };
    settings: FunnelSettings;
    customDomainStatus: CustomDomainStatus;
    permissions: {
        canEdit: boolean;
        canShare: boolean;
        canManageLeads: boolean;
        role: 'owner' | 'editor' | 'viewer';
    };
}>();

const page = usePage<{ flash?: { status?: string } }>();
const activeTab = ref<SettingsTab>('publication');
const isRefreshingDomainStatus = ref(false);

function normalizeDateTimeLocalValue(value: string | null | undefined): string {
    if (!value) {
        return '';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    const offsetMilliseconds = date.getTimezoneOffset() * 60_000;

    return new Date(date.getTime() - offsetMilliseconds)
        .toISOString()
        .slice(0, 16);
}

function serializeDateTimeLocalValue(value: string): string | null {
    if (value.trim() === '') {
        return null;
    }

    const date = new Date(value);

    return Number.isNaN(date.getTime()) ? null : date.toISOString();
}

const form = useForm({
    is_active: props.funnel.is_active,
    custom_domain: props.funnel.custom_domain ?? '',
    logo_url: props.settings.logoUrl ?? '',
    favicon_url: props.settings.faviconUrl ?? '',
    seo_title: props.settings.seoTitle ?? '',
    seo_description: props.settings.seoDescription ?? '',
    seo_image_url: props.settings.seoImageUrl ?? '',
    expires_at: normalizeDateTimeLocalValue(props.settings.expiresAt),
    unavailable_title: props.settings.unavailableTitle ?? '',
    unavailable_description: props.settings.unavailableDescription ?? '',
});

const flashMessage = computed(() => {
    const status = page.props.flash?.status ?? '';

    return (
        {
            'funnel-settings-saved': 'Configurações salvas',
            'funnel-published': 'Funil publicado',
            'funnel-unpublished': 'Funil despublicado',
        }[status] ?? ''
    );
});

const domainStatusToneClass = computed(() => {
    if (props.customDomainStatus.status === 'ready') {
        return 'border-emerald-400/40 bg-emerald-400/10 text-emerald-100';
    }

    if (
        props.customDomainStatus.status === 'pending_dns' ||
        props.customDomainStatus.status === 'tls_pending'
    ) {
        return 'border-amber-400/40 bg-amber-400/10 text-amber-100';
    }

    return 'border-[#2d4f89] bg-[#0a1e45] text-[#dceaff]';
});

const fieldClass =
    'w-full rounded-xl border border-[#2d4f89] bg-[#0a1e45] px-4 py-3 text-[#dceaff] outline-none transition placeholder:text-[#6f8fca] focus:border-[#4f8fff] disabled:cursor-not-allowed disabled:opacity-60';

const tabs: Array<{
    id: SettingsTab;
    label: string;
    description: string;
    icon: typeof Settings;
}> = [
    {
        id: 'publication',
        label: 'Publicação',
        description: 'Status e disponibilidade',
        icon: CheckCircle2,
    },
    {
        id: 'seo',
        label: 'SEO',
        description: 'Busca e compartilhamento',
        icon: Search,
    },
    {
        id: 'domain',
        label: 'Domínio',
        description: 'Endereço personalizado',
        icon: Globe2,
    },
    {
        id: 'connections',
        label: 'Conexões',
        description: 'Integrações do funil',
        icon: PlugZap,
    },
];

function saveSettings(): void {
    if (!props.permissions.canEdit) {
        return;
    }

    form.custom_domain = form.custom_domain.trim().toLowerCase();
    form.transform((data) => ({
        ...data,
        expires_at: serializeDateTimeLocalValue(data.expires_at),
    }));
    form.submit(FunnelController.updateSettings(props.funnel.id), {
        preserveScroll: true,
        preserveState: true,
    });
}

function refreshDomainStatus(): void {
    if (!props.funnel.custom_domain || isRefreshingDomainStatus.value) {
        return;
    }

    isRefreshingDomainStatus.value = true;
    router.get(
        FunnelController.settings(props.funnel.id).url,
        { refresh_domain: 1 },
        {
            only: ['customDomainStatus'],
            preserveScroll: true,
            preserveState: true,
            replace: true,
            onFinish: () => {
                isRefreshingDomainStatus.value = false;
            },
        },
    );
}

function formatDomainCheckedAt(value: string | null): string {
    if (!value) {
        return '';
    }

    const date = new Date(value);

    return Number.isNaN(date.getTime()) ? '' : date.toLocaleString('pt-BR');
}
</script>

<template>
    <Head :title="`${props.funnel.name} - Configurações`" />

    <div class="flex min-h-screen flex-col bg-[#050d22] text-[#d8e7ff]">
        <header
            class="border-b border-[#1e3157] bg-[#071430] px-2 py-2 sm:px-4 sm:py-3"
        >
            <div
                class="flex flex-wrap items-center justify-between gap-2 xl:gap-4"
            >
                <div class="flex min-w-0 flex-1 items-center gap-2 sm:gap-3">
                    <Link
                        href="/dashboard"
                        class="flex h-10 w-10 items-center justify-center rounded-lg border border-[#2f4f8c] bg-[#081b3c] text-base font-bold text-white"
                    >
                        IN
                    </Link>
                    <div class="min-w-0">
                        <p
                            class="truncate text-sm font-semibold text-white sm:text-lg"
                        >
                            {{ props.funnel.name }}
                        </p>
                        <p
                            class="hidden truncate text-sm text-[#88a8df] sm:block"
                        >
                            ... / {{ props.funnel.slug }}
                        </p>
                    </div>
                </div>

                <nav
                    class="order-3 flex w-full items-center gap-1 overflow-x-auto rounded-lg border border-[#253f70] bg-[#081a39] p-1.5 text-sm xl:order-none xl:w-auto xl:overflow-visible"
                >
                    <Link
                        :href="FunnelController.builder(props.funnel.id).url"
                        class="shrink-0 rounded-md px-3.5 py-1.5 text-[#9ebbf0] hover:bg-[#0f274f]"
                    >
                        <span class="inline-flex items-center gap-1"
                            ><BookOpen class="size-4" /> Construtor</span
                        >
                    </Link>
                    <Link
                        :href="FunnelController.flow(props.funnel.id).url"
                        class="shrink-0 rounded-md px-3.5 py-1.5 text-[#9ebbf0] hover:bg-[#0f274f]"
                    >
                        <span class="inline-flex items-center gap-1"
                            ><ListTree class="size-4" /> Fluxo</span
                        >
                    </Link>
                    <Link
                        :href="FunnelController.design(props.funnel.id).url"
                        class="shrink-0 rounded-md px-3.5 py-1.5 text-[#9ebbf0] hover:bg-[#0f274f]"
                    >
                        <span class="inline-flex items-center gap-1"
                            ><Palette class="size-4" /> Design</span
                        >
                    </Link>
                    <Link
                        v-if="props.permissions.canManageLeads"
                        :href="FunnelController.leads(props.funnel.id).url"
                        class="shrink-0 rounded-md px-3.5 py-1.5 text-[#9ebbf0] hover:bg-[#0f274f]"
                    >
                        <span class="inline-flex items-center gap-1"
                            ><CircleUserRound class="size-4" /> Leads</span
                        >
                    </Link>
                </nav>

                <div class="flex shrink-0 items-center gap-1.5">
                    <Link
                        :href="FunnelController.settings(props.funnel.id).url"
                        aria-current="page"
                        aria-label="Configurações do funil"
                        title="Configurações do funil"
                        data-testid="funnel-settings-button"
                        class="rounded-md border border-[#4f8fff] bg-[#12356f] p-1.5 text-white"
                    >
                        <Settings class="size-4" />
                    </Link>
                    <button
                        type="button"
                        :disabled="!props.permissions.canShare"
                        aria-label="Compartilhar funil"
                        class="rounded-md border border-[#2a487c] bg-[#081b39] p-1.5 text-[#b6cdff] disabled:opacity-40"
                    >
                        <Share2 class="size-4" />
                    </button>
                    <a
                        :href="showPublicFunnel(props.funnel.slug).url"
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="Ver resultado do funil"
                        title="Ver resultado do funil"
                        data-testid="funnel-preview-button"
                        class="rounded-md border border-[#2a487c] bg-[#081b39] p-1.5 text-[#b6cdff]"
                    >
                        <Eye class="size-4" />
                    </a>
                    <button
                        type="button"
                        :disabled="
                            !props.permissions.canEdit || form.processing
                        "
                        data-testid="save-funnel-settings"
                        class="rounded-md bg-linear-to-r from-[#1d5fd2] to-[#3f8dff] px-4 py-1.5 text-sm font-semibold text-white disabled:opacity-50"
                        @click="saveSettings"
                    >
                        {{ form.processing ? 'Salvando...' : 'Salvar' }}
                    </button>
                    <span
                        v-if="flashMessage"
                        class="text-xs text-emerald-300"
                        >{{ flashMessage }}</span
                    >
                </div>
            </div>
        </header>

        <main
            class="mx-auto grid w-full max-w-7xl flex-1 gap-4 px-3 py-4 lg:grid-cols-[280px_minmax(0,1fr)] lg:gap-6 lg:px-8 lg:py-6"
        >
            <aside
                class="h-fit rounded-2xl border border-[#203b6b] bg-[#071633] p-3 lg:sticky lg:top-6"
            >
                <div class="px-3 py-3">
                    <p
                        class="text-xs font-semibold tracking-[0.18em] text-[#7395cf] uppercase"
                    >
                        Configurações
                    </p>
                    <h1 class="mt-2 text-xl font-semibold text-white">
                        Configurações do funil
                    </h1>
                </div>
                <div class="mt-2 grid gap-1">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        type="button"
                        :data-testid="`settings-tab-${tab.id}`"
                        class="flex items-start gap-3 rounded-xl px-3 py-3 text-left transition"
                        :class="
                            activeTab === tab.id
                                ? 'bg-[#153b79] text-white'
                                : 'text-[#9cb9e9] hover:bg-[#0d274f]'
                        "
                        @click="activeTab = tab.id"
                    >
                        <component :is="tab.icon" class="mt-0.5 size-4" />
                        <span>
                            <span class="block text-sm font-semibold">{{
                                tab.label
                            }}</span>
                            <span class="mt-0.5 block text-xs opacity-70">{{
                                tab.description
                            }}</span>
                        </span>
                    </button>
                </div>
            </aside>

            <section
                class="min-w-0 rounded-2xl border border-[#203b6b] bg-[#071633] p-5 sm:p-7"
            >
                <div
                    v-if="activeTab === 'publication'"
                    data-testid="settings-publication-panel"
                >
                    <p class="text-sm font-semibold text-[#7fa8ee]">
                        PUBLICAÇÃO
                    </p>
                    <h2 class="mt-2 text-2xl font-semibold text-white">
                        Disponibilidade do funil
                    </h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-[#91acd9]">
                        Controle quando o funil pode receber acessos e como a
                        página indisponível será apresentada.
                    </p>

                    <div class="mt-7 grid gap-5 md:grid-cols-2">
                        <label
                            class="flex items-center justify-between gap-4 rounded-xl border border-[#2d4f89] bg-[#0a1e45] px-4 py-4"
                        >
                            <span>
                                <span class="block font-semibold text-white"
                                    >Funil publicado</span
                                >
                                <span class="mt-1 block text-xs text-[#88a8df]"
                                    >Permite acesso pela URL pública.</span
                                >
                            </span>
                            <input
                                v-model="form.is_active"
                                :disabled="!props.permissions.canEdit"
                                type="checkbox"
                                data-testid="settings-is-active"
                                class="size-5 accent-[#3d8bff]"
                            />
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-sm text-[#9bb8e8]"
                                >Expira em</span
                            >
                            <input
                                v-model="form.expires_at"
                                :disabled="!props.permissions.canEdit"
                                :class="fieldClass"
                                type="datetime-local"
                                data-testid="settings-expires-at"
                            />
                            <span
                                v-if="form.errors.expires_at"
                                class="mt-1 block text-xs text-rose-300"
                                >{{ form.errors.expires_at }}</span
                            >
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-sm text-[#9bb8e8]"
                                >Logo do funil</span
                            >
                            <input
                                v-model="form.logo_url"
                                :disabled="!props.permissions.canEdit"
                                :class="fieldClass"
                                placeholder="https://..."
                            />
                            <span
                                v-if="form.errors.logo_url"
                                class="mt-1 block text-xs text-rose-300"
                                >{{ form.errors.logo_url }}</span
                            >
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-sm text-[#9bb8e8]"
                                >Favicon do funil</span
                            >
                            <input
                                v-model="form.favicon_url"
                                :disabled="!props.permissions.canEdit"
                                :class="fieldClass"
                                placeholder="https://..."
                            />
                            <span
                                v-if="form.errors.favicon_url"
                                class="mt-1 block text-xs text-rose-300"
                                >{{ form.errors.favicon_url }}</span
                            >
                        </label>
                        <label class="block md:col-span-2">
                            <span class="mb-1.5 block text-sm text-[#9bb8e8]"
                                >Título da página indisponível</span
                            >
                            <input
                                v-model="form.unavailable_title"
                                :disabled="!props.permissions.canEdit"
                                :class="fieldClass"
                                maxlength="120"
                            />
                            <span
                                v-if="form.errors.unavailable_title"
                                class="mt-1 block text-xs text-rose-300"
                                >{{ form.errors.unavailable_title }}</span
                            >
                        </label>
                        <label class="block md:col-span-2">
                            <span class="mb-1.5 block text-sm text-[#9bb8e8]"
                                >Descrição da página indisponível</span
                            >
                            <textarea
                                v-model="form.unavailable_description"
                                :disabled="!props.permissions.canEdit"
                                :class="fieldClass"
                                maxlength="300"
                                rows="4"
                            />
                            <span
                                v-if="form.errors.unavailable_description"
                                class="mt-1 block text-xs text-rose-300"
                                >{{ form.errors.unavailable_description }}</span
                            >
                        </label>
                    </div>
                </div>

                <div
                    v-else-if="activeTab === 'seo'"
                    data-testid="settings-seo-panel"
                >
                    <p class="text-sm font-semibold text-[#7fa8ee]">SEO</p>
                    <h2 class="mt-2 text-2xl font-semibold text-white">
                        Busca e compartilhamento
                    </h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-[#91acd9]">
                        Defina como o funil aparece em mecanismos de busca e ao
                        compartilhar o link.
                    </p>
                    <div class="mt-7 grid gap-5">
                        <label class="block">
                            <span
                                class="mb-1.5 flex justify-between gap-3 text-sm text-[#9bb8e8]"
                                ><span>Título SEO</span
                                ><span
                                    >{{ form.seo_title.length }}/120</span
                                ></span
                            >
                            <input
                                v-model="form.seo_title"
                                :disabled="!props.permissions.canEdit"
                                :class="fieldClass"
                                maxlength="120"
                                placeholder="Título para mecanismos de busca"
                                data-testid="settings-seo-title"
                            />
                            <span
                                v-if="form.errors.seo_title"
                                class="mt-1 block text-xs text-rose-300"
                                >{{ form.errors.seo_title }}</span
                            >
                        </label>
                        <label class="block">
                            <span
                                class="mb-1.5 flex justify-between gap-3 text-sm text-[#9bb8e8]"
                                ><span>Descrição SEO</span
                                ><span
                                    >{{ form.seo_description.length }}/180</span
                                ></span
                            >
                            <textarea
                                v-model="form.seo_description"
                                :disabled="!props.permissions.canEdit"
                                :class="fieldClass"
                                maxlength="180"
                                rows="4"
                                placeholder="Descrição curta para busca e compartilhamento"
                                data-testid="settings-seo-description"
                            />
                            <span
                                v-if="form.errors.seo_description"
                                class="mt-1 block text-xs text-rose-300"
                                >{{ form.errors.seo_description }}</span
                            >
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-sm text-[#9bb8e8]"
                                >Imagem SEO</span
                            >
                            <input
                                v-model="form.seo_image_url"
                                :disabled="!props.permissions.canEdit"
                                :class="fieldClass"
                                placeholder="https://..."
                                data-testid="settings-seo-image"
                            />
                            <span
                                v-if="form.errors.seo_image_url"
                                class="mt-1 block text-xs text-rose-300"
                                >{{ form.errors.seo_image_url }}</span
                            >
                        </label>
                    </div>
                </div>

                <div
                    v-else-if="activeTab === 'domain'"
                    data-testid="settings-domain-panel"
                >
                    <div
                        class="flex flex-wrap items-start justify-between gap-4"
                    >
                        <div>
                            <p class="text-sm font-semibold text-[#7fa8ee]">
                                DOMÍNIO
                            </p>
                            <h2 class="mt-2 text-2xl font-semibold text-white">
                                Domínio personalizado
                            </h2>
                            <p
                                class="mt-2 max-w-2xl text-sm leading-6 text-[#91acd9]"
                            >
                                Use um endereço da sua marca para publicar este
                                funil.
                            </p>
                        </div>
                        <Dialog>
                            <DialogTrigger as-child>
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-2 rounded-lg border border-[#315993] bg-[#0a2148] px-3 py-2 text-sm text-[#bcd2fa]"
                                >
                                    <CircleHelp class="size-4" /> Como
                                    configurar
                                </button>
                            </DialogTrigger>
                            <DialogContent
                                class="border-[#315993] bg-[#071633] text-[#dceaff] sm:max-w-xl"
                            >
                                <DialogHeader>
                                    <DialogTitle
                                        >Como configurar seu
                                        domínio</DialogTitle
                                    >
                                    <DialogDescription class="text-[#9bb8e8]"
                                        >No provedor em que você comprou o
                                        domínio, crie o registro DNS indicado
                                        abaixo.</DialogDescription
                                    >
                                </DialogHeader>
                                <ol
                                    class="grid gap-3 text-sm leading-6 text-[#b9cdf0]"
                                >
                                    <li>
                                        1. Informe abaixo o subdomínio completo,
                                        sem <code>https://</code>.
                                    </li>
                                    <li>
                                        2. Crie um registro CNAME apontando para
                                        <code>{{
                                            props.customDomainStatus
                                                .expected_target ||
                                            'o endereço informado pela Inovaform'
                                        }}</code
                                        >.
                                    </li>
                                    <li>
                                        3. Salve e aguarde a propagação do DNS.
                                        Depois use “Verificar novamente”.
                                    </li>
                                </ol>
                            </DialogContent>
                        </Dialog>
                    </div>

                    <label class="mt-7 block">
                        <span class="mb-1.5 block text-sm text-[#9bb8e8]"
                            >Domínio</span
                        >
                        <input
                            v-model="form.custom_domain"
                            :disabled="!props.permissions.canEdit"
                            :class="fieldClass"
                            placeholder="quiz.seudominio.com"
                            data-testid="settings-custom-domain"
                        />
                        <span
                            v-if="form.errors.custom_domain"
                            class="mt-1 block text-xs text-rose-300"
                            >{{ form.errors.custom_domain }}</span
                        >
                    </label>

                    <div
                        data-testid="custom-domain-status"
                        class="mt-5 rounded-xl border p-4"
                        :class="domainStatusToneClass"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold">
                                    {{ props.customDomainStatus.label }}
                                </p>
                                <p class="mt-1 text-xs leading-5 opacity-80">
                                    {{ props.customDomainStatus.message }}
                                </p>
                            </div>
                            <span
                                class="mt-1 size-2.5 shrink-0 rounded-full"
                                :class="
                                    props.customDomainStatus.status === 'ready'
                                        ? 'bg-emerald-400'
                                        : props.customDomainStatus.status ===
                                                'pending_dns' ||
                                            props.customDomainStatus.status ===
                                                'tls_pending'
                                          ? 'bg-amber-400'
                                          : 'bg-slate-400'
                                "
                            />
                        </div>
                        <div
                            v-if="props.funnel.custom_domain"
                            class="mt-3 flex flex-wrap gap-2 text-[11px]"
                        >
                            <span
                                class="rounded-full border border-current/20 px-2 py-1"
                                >DNS:
                                {{
                                    props.customDomainStatus.dns_ready
                                        ? 'validado'
                                        : 'pendente'
                                }}</span
                            >
                            <span
                                class="rounded-full border border-current/20 px-2 py-1"
                                >HTTPS:
                                {{
                                    props.customDomainStatus.tls_ready
                                        ? 'validado'
                                        : 'pendente'
                                }}</span
                            >
                        </div>
                        <div
                            v-if="props.funnel.custom_domain"
                            class="mt-3 flex flex-wrap items-center justify-between gap-3 border-t border-current/15 pt-3"
                        >
                            <span class="text-[10px] opacity-60">{{
                                formatDomainCheckedAt(
                                    props.customDomainStatus.checked_at,
                                )
                                    ? `Verificado em ${formatDomainCheckedAt(props.customDomainStatus.checked_at)}`
                                    : 'Ainda não verificado'
                            }}</span>
                            <button
                                type="button"
                                :disabled="isRefreshingDomainStatus"
                                class="rounded-lg border border-current/25 px-3 py-1.5 text-xs font-semibold disabled:opacity-50"
                                @click="refreshDomainStatus"
                            >
                                {{
                                    isRefreshingDomainStatus
                                        ? 'Verificando...'
                                        : 'Verificar novamente'
                                }}
                            </button>
                        </div>
                    </div>
                </div>

                <div v-else data-testid="settings-connections-panel">
                    <p class="text-sm font-semibold text-[#7fa8ee]">CONEXÕES</p>
                    <h2 class="mt-2 text-2xl font-semibold text-white">
                        Integrações do funil
                    </h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-[#91acd9]">
                        Este é o espaço central para conectar o funil a serviços
                        externos.
                    </p>
                    <div
                        class="mt-7 rounded-2xl border border-dashed border-[#315993] bg-[#0a1e45]/70 px-6 py-12 text-center"
                    >
                        <PlugZap class="mx-auto size-9 text-[#6795e6]" />
                        <h3 class="mt-4 text-lg font-semibold text-white">
                            Nenhuma conexão disponível ainda
                        </h3>
                        <p
                            class="mx-auto mt-2 max-w-lg text-sm leading-6 text-[#8faade]"
                        >
                            Os conectores serão adicionados aqui quando cada
                            integração tiver contrato, credenciais e
                            comportamento definidos. Nenhuma conexão fictícia
                            foi ativada.
                        </p>
                    </div>
                </div>
            </section>
        </main>
    </div>
</template>
