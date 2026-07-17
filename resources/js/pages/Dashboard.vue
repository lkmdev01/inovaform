<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    ChevronDown,
    Crown,
    EllipsisVertical,
    HelpCircle,
    ListFilter,
    Orbit,
    Plus,
    Search,
    Sparkles,
    Upload,
    WandSparkles,
    X,
} from 'lucide-vue-next';
import { computed, ref, useTemplateRef } from 'vue';
import FunnelController from '@/actions/App/Http/Controllers/FunnelController';
import { Dialog, DialogContent, DialogDescription, DialogTitle } from '@/components/ui/dialog';
import profile from '@/routes/profile';

type StageMeta = {
    header?: {
        show_logo?: boolean;
        show_progress?: boolean;
        allow_back?: boolean;
    };
};

type FunnelStage = {
    id: number;
    name: string;
    stage_order: number;
    conversion_rate: string | null;
    expected_volume: number | null;
    meta: StageMeta | null;
};

type FunnelItem = {
    id: number;
    name: string;
    description: string | null;
    target_leads: number | null;
    is_active: boolean;
    created_at: string;
    stages: FunnelStage[];
    user?: {
        id: number;
        name: string;
        email: string;
    };
    pivot?: {
        role: 'viewer' | 'editor';
    };
    dashboard_metrics?: {
        leads: number;
        started: number;
        completed: number;
        conversion: number;
    };
};

type DashboardStats = {
    currentFunnels: number;
    maxFunnels: number;
    registeredLeads: number;
    leadsQuota: number;
};

type FunnelTemplate = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    category: string | null;
    thumbnail_path: string | null;
    is_system: boolean;
    is_premium: boolean;
    version: number;
    source_funnel_id: number | null;
    stage_count: number;
    preview: {
        badge: string;
        accentColor: string;
        headline: string;
        chips: string[];
    };
};

type FunnelSort = 'recent' | 'name' | 'leads';

const props = defineProps<{
    funnels: FunnelItem[];
    sharedFunnels: FunnelItem[];
    templates: FunnelTemplate[];
    templateCategories: string[];
    stats: DashboardStats;
}>();

const page = usePage<{ auth: { user: { name: string; email: string } }; flash?: { status?: string } }>();

const userName = computed(() => page.props.auth?.user?.name ?? 'Usuario');
const userEmail = computed(() => page.props.auth?.user?.email ?? '-');
const flashStatus = computed(() => page.props.flash?.status ?? '');

const statusMessages: Record<string, string> = {
    'funnel-duplicated': 'Funil duplicado com sucesso.',
    'template-created': 'Template salvo na sua biblioteca.',
    'funnel-imported': 'Funil importado com sucesso.',
    'funnel-deleted': 'Funil excluido com sucesso.',
};

const flashStatusMessage = computed(() => statusMessages[flashStatus.value] ?? '');

const isCreateFunnelModalOpen = ref(false);
const selectedTemplateId = ref('blank');
const aiTopic = ref('');
const activeTab = ref<'mine' | 'shared'>('mine');
const searchQuery = ref('');
const sortBy = ref<FunnelSort>('recent');
const activeMenuFunnelId = ref<number | null>(null);
const copiedFunnelId = ref<number | null>(null);
const isShareModalOpen = ref(false);
const selectedShareFunnel = ref<FunnelItem | null>(null);
const isSaveTemplateModalOpen = ref(false);
const selectedTemplateFunnel = ref<FunnelItem | null>(null);
const templateSearchQuery = ref('');
const templateCategoryFilter = ref('');
const templateScope = ref<'all' | 'system' | 'mine'>('all');
const importFileInput = useTemplateRef<HTMLInputElement>('import-file-input');

const aiCharacterCount = computed(() => aiTopic.value.length);
const canCreateFunnel = computed(() => props.stats.currentFunnels < props.stats.maxFunnels);
const availableTemplates = computed(() => {
    const normalizedSearch = templateSearchQuery.value.trim().toLowerCase();

    return props.templates.filter((template) => {
        if (templateScope.value === 'system' && !template.is_system) {
            return false;
        }

        if (templateScope.value === 'mine' && template.is_system) {
            return false;
        }

        if (templateCategoryFilter.value !== '' && template.category !== templateCategoryFilter.value) {
            return false;
        }

        if (normalizedSearch.length === 0) {
            return true;
        }

        return [
            template.name,
            template.description ?? '',
            template.category ?? '',
            template.preview.headline,
            template.preview.chips.join(' '),
        ].join(' ').toLowerCase().includes(normalizedSearch);
    });
});
function resolveTemplateById(templateId: string): FunnelTemplate | null {
    if (templateId === 'blank') {
        return null;
    }

    return props.templates.find((template) => String(template.id) === templateId) ?? null;
}

function filterAndSortFunnels(funnels: FunnelItem[]): FunnelItem[] {
    const normalizedSearch = searchQuery.value.trim().toLowerCase();

    const filtered = funnels.filter((funnel) => {
        if (normalizedSearch.length === 0) {
            return true;
        }

        const stageNames = funnel.stages.map((stage) => stage.name).join(' ').toLowerCase();
        const description = (funnel.description ?? '').toLowerCase();
        const ownerName = (funnel.user?.name ?? '').toLowerCase();

        return funnel.name.toLowerCase().includes(normalizedSearch)
            || description.includes(normalizedSearch)
            || stageNames.includes(normalizedSearch)
            || ownerName.includes(normalizedSearch);
    });

    if (sortBy.value === 'name') {
        return filtered.sort((first, second) => first.name.localeCompare(second.name, 'pt-BR'));
    }

    if (sortBy.value === 'leads') {
        return filtered.sort((first, second) => getFunnelLeads(second) - getFunnelLeads(first));
    }

    return filtered.sort(
        (first, second) => new Date(second.created_at).getTime() - new Date(first.created_at).getTime(),
    );
}

const filteredFunnels = computed(() => {
    return filterAndSortFunnels(props.funnels);
});

const filteredSharedFunnels = computed(() => {
    return filterAndSortFunnels(props.sharedFunnels);
});

const leadsProgress = computed(() => {
    if (props.stats.leadsQuota <= 0) {
        return 0;
    }

    return Math.min(100, Math.round((props.stats.registeredLeads / props.stats.leadsQuota) * 100));
});

const createFunnelForm = useForm({
    name: '',
    template_id: null as number | null,
    description: '',
    target_leads: '500' as string | null,
    is_active: true,
    stages: [] as Array<{
        name: string;
        conversion_rate: number | null;
        expected_volume: number | null;
    }>,
});

const shareFunnelForm = useForm({
    email: '',
    role: 'viewer' as 'viewer' | 'editor',
});

const saveTemplateForm = useForm({
    name: '',
    description: '',
    category: '',
    thumbnail_path: '',
    is_active: true,
    is_premium: false,
});

const importFunnelForm = useForm({
    file: null as File | null,
    name: '',
});

function openCreateFunnelModal(): void {
    if (!canCreateFunnel.value) {
        return;
    }

    createFunnelForm.clearErrors();
    isCreateFunnelModalOpen.value = true;
}

function closeCreateFunnelModal(): void {
    createFunnelForm.clearErrors();
    isCreateFunnelModalOpen.value = false;
}

function openShareModal(funnel: FunnelItem): void {
    selectedShareFunnel.value = funnel;
    shareFunnelForm.reset();
    shareFunnelForm.role = 'viewer';
    shareFunnelForm.clearErrors();
    isShareModalOpen.value = true;
    activeMenuFunnelId.value = null;
}

function closeShareModal(): void {
    isShareModalOpen.value = false;
    selectedShareFunnel.value = null;
}

function openSaveTemplateModal(funnel: FunnelItem): void {
    selectedTemplateFunnel.value = funnel;
    saveTemplateForm.reset();
    saveTemplateForm.name = funnel.name;
    saveTemplateForm.description = funnel.description ?? '';
    saveTemplateForm.category = '';
    saveTemplateForm.thumbnail_path = '';
    saveTemplateForm.is_active = true;
    saveTemplateForm.is_premium = false;
    saveTemplateForm.clearErrors();
    isSaveTemplateModalOpen.value = true;
    activeMenuFunnelId.value = null;
}

function closeSaveTemplateModal(): void {
    isSaveTemplateModalOpen.value = false;
    selectedTemplateFunnel.value = null;
}

function buildStagesByTemplate(): Array<{
    name: string;
    conversion_rate: number | null;
    expected_volume: number | null;
}> {
    return [
        { name: 'Etapa 1', conversion_rate: 100, expected_volume: 3000 },
        { name: 'Etapa 2', conversion_rate: 30, expected_volume: 900 },
    ];
}

function selectTemplate(templateId: string): void {
    selectedTemplateId.value = templateId;
    createFunnelForm.clearErrors('template_id', 'stages');

    if (templateId === 'blank') {
        createFunnelForm.template_id = null;

        return;
    }

    const template = resolveTemplateById(templateId);
    createFunnelForm.template_id = template?.id ?? null;

    if (template !== null && createFunnelForm.name.trim() === '') {
        createFunnelForm.name = template.name;
    }
}

function submitCreateFunnel(): void {
    if (!canCreateFunnel.value) {
        return;
    }

    const activeTemplate = resolveTemplateById(selectedTemplateId.value);
    const trimmedName = createFunnelForm.name.trim();

    createFunnelForm.name = activeTemplate !== null && trimmedName.length === 0
        ? activeTemplate.name
        : trimmedName;
    createFunnelForm.template_id = activeTemplate !== null ? Number(selectedTemplateId.value) : null;
    createFunnelForm.description = aiTopic.value.trim();
    createFunnelForm.stages = activeTemplate === null ? buildStagesByTemplate() : [];
    createFunnelForm.target_leads = activeTemplate === null ? createFunnelForm.target_leads : null;

    createFunnelForm.transform((data) => {
        const payload: Record<string, unknown> = {
            ...data,
        };

        if (activeTemplate !== null) {
            delete payload.stages;
        }

        return payload;
    });

    createFunnelForm.submit(FunnelController.store(), {
        preserveScroll: true,
        onSuccess: () => {
            createFunnelForm.reset();
            createFunnelForm.template_id = null;
            createFunnelForm.target_leads = '500';
            createFunnelForm.is_active = true;
            selectedTemplateId.value = 'blank';
            aiTopic.value = '';
            closeCreateFunnelModal();
        },
        onFinish: () => {
            createFunnelForm.transform((data) => data);
        },
    });
}

function duplicateFunnel(funnel: FunnelItem): void {
    router.post(FunnelController.duplicate(funnel.id).url, {}, {
        preserveScroll: true,
        onFinish: () => {
            activeMenuFunnelId.value = null;
        },
    });
}

function exportFunnel(funnel: FunnelItem): void {
    window.location.href = FunnelController.export(funnel.id).url;
    activeMenuFunnelId.value = null;
}

function submitSaveTemplate(): void {
    if (selectedTemplateFunnel.value === null) {
        return;
    }

    saveTemplateForm.name = saveTemplateForm.name.trim();
    saveTemplateForm.description = saveTemplateForm.description.trim();
    saveTemplateForm.category = saveTemplateForm.category.trim();
    saveTemplateForm.thumbnail_path = saveTemplateForm.thumbnail_path.trim();

    saveTemplateForm.submit(FunnelController.storeTemplate(selectedTemplateFunnel.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeSaveTemplateModal();
        },
    });
}

function triggerImportFunnel(): void {
    importFileInput.value?.click();
}

function handleImportFunnelChange(event: Event): void {
    const target = event.target as HTMLInputElement | null;
    const file = target?.files?.[0] ?? null;

    if (file === null) {
        return;
    }

    importFunnelForm.file = file;
    importFunnelForm.name = '';
    importFunnelForm.post('/funnels/import', {
        preserveScroll: true,
        forceFormData: true,
        onFinish: () => {
            if (target) {
                target.value = '';
            }
        },
    });
}

function formatDate(dateText: string): string {
    return new Intl.DateTimeFormat('pt-BR', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    }).format(new Date(dateText));
}

function getFunnelLeads(funnel: FunnelItem): number {
    if (funnel.dashboard_metrics) {
        return funnel.dashboard_metrics.leads;
    }

    const firstStage = funnel.stages.find((stage) => stage.stage_order === 1);

    return firstStage?.expected_volume ?? funnel.target_leads ?? 0;
}

function getFunnelConversion(funnel: FunnelItem): string {
    if (funnel.dashboard_metrics) {
        return `${funnel.dashboard_metrics.conversion.toFixed(2)}%`;
    }

    if (funnel.stages.length < 2) {
        return '0.00%';
    }

    const conversion = funnel.stages
        .slice(1)
        .map((stage) => Number(stage.conversion_rate ?? '0'))
        .reduce((carry, value) => carry * (value / 100), 1);

    return `${(conversion * 100).toFixed(2)}%`;
}

function buildFunnelUpdatePayload(funnel: FunnelItem, isActive: boolean): {
    name: string;
    is_active: boolean;
    stages: Array<{
        id: number;
        name: string;
        conversion_rate: number | null;
        expected_volume: number | null;
        meta: StageMeta | null;
    }>;
} {
    return {
        name: funnel.name,
        is_active: isActive,
        stages: funnel.stages
            .slice()
            .sort((first, second) => first.stage_order - second.stage_order)
            .map((stage) => ({
                id: stage.id,
                name: stage.name,
                conversion_rate: stage.conversion_rate !== null ? Number(stage.conversion_rate) : null,
                expected_volume: stage.expected_volume,
                meta: stage.meta,
            })),
    };
}

function toggleFunnelStatus(funnel: FunnelItem): void {
    router.patch(
        FunnelController.update(funnel.id).url,
        buildFunnelUpdatePayload(funnel, !funnel.is_active),
        {
            preserveScroll: true,
            onFinish: () => {
                activeMenuFunnelId.value = null;
            },
        },
    );
}

function copyFunnelBuilderLink(funnelId: number): void {
    const builderPath = FunnelController.builder(funnelId).url;
    const builderUrl = new URL(builderPath, window.location.origin).toString();

    navigator.clipboard.writeText(builderUrl).then(() => {
        copiedFunnelId.value = funnelId;
        window.setTimeout(() => {
            copiedFunnelId.value = null;
        }, 1500);
    });
}

function deleteFunnel(funnel: FunnelItem): void {
    const confirmed = window.confirm(`Excluir o funil "${funnel.name}"? Essa acao nao pode ser desfeita.`);

    if (!confirmed) {
        return;
    }

    router.delete(FunnelController.destroy(funnel.id).url, {
        preserveScroll: true,
        onFinish: () => {
            activeMenuFunnelId.value = null;
        },
    });
}

function submitShareFunnel(): void {
    if (selectedShareFunnel.value === null) {
        return;
    }

    shareFunnelForm.email = shareFunnelForm.email.trim().toLowerCase();

    shareFunnelForm.submit(FunnelController.share(selectedShareFunnel.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeShareModal();
        },
    });
}
</script>

<template>

    <Head title="Dashboard" />

    <div class="min-h-screen bg-[radial-gradient(circle_at_10%_0%,#102a5f_0%,#07132d_35%,#030917_100%)] text-[#dbe9ff]">
        <header class="border-b border-[#16315f] bg-[#07132de6] backdrop-blur">
            <div class="mx-auto flex h-14 max-w-6xl items-center justify-between px-5">
                <div class="flex items-center gap-5">
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-lg border border-[#2e63c8] bg-[#0a1f49] text-lg font-bold text-white">
                        IN
                    </div>
                    <a href="mailto:suporte@inovaform.com" class="flex items-center gap-2 text-sm text-[#9ab9f4]">
                        <HelpCircle class="size-4 text-[#7ea7f2]" />
                        Precisa de ajuda?
                    </a>
                </div>

                <div class="flex items-center gap-6">
                    <div class="min-w-72 border-r border-[#18335e] pr-5">
                        <div class="mb-1 flex items-center justify-between text-sm text-[#9db7e8]">
                            <span>leads cadastrados</span>
                            <span>{{ props.stats.registeredLeads }} / {{ props.stats.leadsQuota }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-[#122b57]">
                            <div class="h-2 rounded-full bg-gradient-to-r from-[#2e7cff] to-[#4de0ff]"
                                :style="{ width: `${leadsProgress}%` }" />
                        </div>
                    </div>
                    <Link :href="profile.edit().url"
                        class="flex items-center gap-2.5 rounded-md px-2 py-1 text-[#89a7df] transition hover:bg-[#0b2349]">
                        <div>
                            <p class="text-sm text-[#dbe9ff]">
                                Ola <span class="font-bold">{{ userName }}</span>
                            </p>
                            <p class="text-xs text-[#89a7df]">{{ userEmail }}</p>
                        </div>
                        <ChevronDown class="size-4" />
                    </Link>
                </div>
            </div>
        </header>

        <main class="pb-16">
            <section v-if="flashStatusMessage || importFunnelForm.errors.file" class="px-5 pt-4 md:px-8">
                <div
                    v-if="flashStatusMessage"
                    class="rounded-xl border border-emerald-400/35 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-100"
                >
                    {{ flashStatusMessage }}
                </div>
                <div
                    v-if="importFunnelForm.errors.file"
                    class="mt-3 rounded-xl border border-rose-400/35 bg-rose-400/10 px-4 py-3 text-sm text-rose-100"
                >
                    {{ importFunnelForm.errors.file }}
                </div>
            </section>

            <section class="border-b border-[#183764] bg-[#07132dd9] px-5 py-4 md:px-8">
                <div class="flex w-full items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <h1 class="text-3xl font-bold text-white">Dashboard</h1>
                        <span
                            class="rounded-md border border-[#284a84] bg-[#0b2248] px-2.5 py-1 text-sm text-[#bfd6ff]">
                            {{ props.stats.currentFunnels }}/{{ props.stats.maxFunnels }} funis
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <input ref="import-file-input" type="file" accept="application/json,.json" class="hidden" @change="handleImportFunnelChange" />
                        <button @click="triggerImportFunnel" :disabled="importFunnelForm.processing"
                            class="inline-flex items-center gap-2 rounded-lg border border-[#335b9c] bg-[#0b2148] px-4 py-2 text-sm font-medium text-[#d8e7ff] transition hover:bg-[#11305f] disabled:opacity-50">
                            <Upload class="size-4" />
                            {{ importFunnelForm.processing ? 'Importando...' : 'Importar funil' }}
                        </button>
                        <button @click="openCreateFunnelModal" :disabled="!canCreateFunnel"
                            class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-[#2a67d9] to-[#3f88ff] px-5 py-2 text-sm font-semibold text-white transition hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-50">
                            <Plus class="size-4" />
                            {{ canCreateFunnel ? 'Criar Funil' : 'Limite de funis atingido' }}
                        </button>
                    </div>
                </div>
            </section>

            <section class="border-b border-[#183764] bg-[#07132dd9] px-5 py-4 md:px-8">
                <div class="flex w-full items-center justify-between gap-6">
                    <div class="flex items-center gap-2 text-lg text-[#c6ddff]">
                        <ListFilter class="size-4 text-[#8eb5f7]" />
                        <span class="font-medium">Mais recentes</span>
                    </div>

                    <div class="flex items-center gap-2">
                        <select v-model="sortBy"
                            class="rounded-full border border-[#2b4d87] bg-[#0a2147] px-3 py-1.5 text-xs text-[#cde0ff] outline-none">
                            <option value="recent">Mais recentes</option>
                            <option value="name">Nome</option>
                            <option value="leads">Mais leads</option>
                        </select>
                    </div>

                    <div class="flex rounded-full border border-[#2b4d87] bg-[#0a2147] p-1">
                        <button @click="activeTab = 'mine'" class="rounded-full px-5 py-1.5 text-sm"
                            :class="activeTab === 'mine' ? 'bg-[#15386f] font-medium text-[#d2e4ff]' : 'text-[#9bb8e9]'">
                            Meus funis
                        </button>
                        <button @click="activeTab = 'shared'" class="rounded-full px-5 py-1.5 text-sm"
                            :class="activeTab === 'shared' ? 'bg-[#15386f] font-medium text-[#d2e4ff]' : 'text-[#9bb8e9]'">
                            Compartilhados comigo
                        </button>
                    </div>

                    <div class="flex items-center gap-2 text-[#9bb1da]">
                        <input v-model="searchQuery"
                            class="w-48 rounded-full bg-[#0c2349] px-3 py-1.5 text-sm italic outline-none placeholder:text-[#8ba5d6]"
                            placeholder="Buscar funil..." />
                        <Search class="size-4 text-[#cde0ff]" />
                    </div>
                </div>
            </section>

            <section class="mx-auto max-w-6xl px-5 pt-5">
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <article v-for="funnel in activeTab === 'mine' ? filteredFunnels : filteredSharedFunnels"
                        :key="funnel.id"
                        class="w-full rounded-xl border border-[#2b4f8d] bg-gradient-to-br from-[#0d2857] to-[#081a39] p-4 shadow-[0_10px_26px_rgba(2,8,24,0.34)] transition hover:border-[#4a7fd4]">
                        <div class="flex items-start justify-between">
                            <div>
                                <div
                                    class="mb-2 inline-flex items-center gap-1.5 rounded-full bg-[#123a7b] px-2.5 py-1 text-[10px] uppercase tracking-[0.12em] text-[#bdd8ff]">
                                    <Orbit class="size-3.5" />
                                    {{ funnel.is_active ? 'Funil ativo' : 'Funil pausado' }}
                                </div>
                                <h2 class="text-2xl font-medium text-white">{{ funnel.name }}</h2>
                                <p class="mt-1.5 text-sm text-[#98b7ea]">{{ formatDate(funnel.created_at) }}</p>
                                <p v-if="activeTab === 'shared'" class="mt-1 text-xs text-[#8fb1ea]">
                                    Compartilhado por {{ funnel.user?.name ?? 'usuario' }}
                                    <span
                                        class="ml-1 rounded-full border border-[#3e6fb8] bg-[#0e2c5d] px-2 py-0.5 text-[10px] uppercase tracking-[0.08em]">
                                        {{ funnel.pivot?.role === 'editor' ? 'editor' : 'viewer' }}
                                    </span>
                                </p>
                                <div class="mt-3 flex gap-2 text-xs">
                                    <span class="rounded-full bg-[#12386f] px-2.5 py-1 text-[#c7ddff]">
                                        Leads: {{ getFunnelLeads(funnel) }}
                                    </span>
                                    <span class="rounded-full bg-[#114965] px-2.5 py-1 text-[#bdf1ff]">
                                        Conversao: {{ getFunnelConversion(funnel) }}
                                    </span>
                                </div>
                                <div class="mt-3 flex items-center gap-2">
                                    <Link :href="FunnelController.builder(funnel.id).url"
                                        class="rounded-md bg-[#1d5fd2] px-3 py-1.5 text-xs font-medium text-white">
                                        Abrir Builder
                                    </Link>
                                    <button v-if="activeTab === 'mine'" @click="openShareModal(funnel)"
                                        class="rounded-md border border-[#3e6fb8] px-3 py-1.5 text-xs text-[#cfe1ff]">
                                        Compartilhar
                                    </button>
                                    <button v-if="activeTab === 'mine'" @click="toggleFunnelStatus(funnel)"
                                        class="rounded-md border border-[#3e6fb8] px-3 py-1.5 text-xs text-[#cfe1ff]">
                                        {{ funnel.is_active ? 'Pausar' : 'Ativar' }}
                                    </button>
                                </div>
                            </div>
                            <div class="relative">
                                <button v-if="activeTab === 'mine'"
                                    @click="activeMenuFunnelId = activeMenuFunnelId === funnel.id ? null : funnel.id"
                                    class="rounded-md p-1 text-[#a7c4f6]">
                                    <EllipsisVertical class="size-5" />
                                </button>
                                <div v-if="activeMenuFunnelId === funnel.id"
                                    class="absolute top-8 right-0 z-10 min-w-36 rounded-md border border-[#2f568f] bg-[#0a2147] p-1.5 text-xs shadow-xl">
                                    <button @click="copyFunnelBuilderLink(funnel.id)"
                                        class="block w-full rounded px-2 py-1.5 text-left text-[#d5e6ff] hover:bg-[#11305f]">
                                        {{ copiedFunnelId === funnel.id ? 'Link copiado' : 'Copiar link' }}
                                    </button>
                                    <button @click="openShareModal(funnel)"
                                        class="block w-full rounded px-2 py-1.5 text-left text-[#d5e6ff] hover:bg-[#11305f]">
                                        Compartilhar por e-mail
                                    </button>
                                    <button @click="toggleFunnelStatus(funnel)"
                                        class="block w-full rounded px-2 py-1.5 text-left text-[#d5e6ff] hover:bg-[#11305f]">
                                        {{ funnel.is_active ? 'Pausar funil' : 'Ativar funil' }}
                                    </button>
                                    <button @click="duplicateFunnel(funnel)"
                                        class="block w-full rounded px-2 py-1.5 text-left text-[#d5e6ff] hover:bg-[#11305f]">
                                        Duplicar funil
                                    </button>
                                    <button @click="exportFunnel(funnel)"
                                        class="block w-full rounded px-2 py-1.5 text-left text-[#d5e6ff] hover:bg-[#11305f]">
                                        Exportar JSON
                                    </button>
                                    <button @click="openSaveTemplateModal(funnel)"
                                        class="block w-full rounded px-2 py-1.5 text-left text-[#d5e6ff] hover:bg-[#11305f]">
                                        Salvar como template
                                    </button>
                                    <button @click="deleteFunnel(funnel)"
                                        class="block w-full rounded px-2 py-1.5 text-left text-[#ffccd7] hover:bg-[#4a1730]">
                                        Excluir funil
                                    </button>
                                    <Link :href="FunnelController.builder(funnel.id).url"
                                        class="block w-full rounded px-2 py-1.5 text-left text-[#d5e6ff] hover:bg-[#11305f]">
                                        Abrir builder
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </article>

                    <article v-if="activeTab === 'mine' && filteredFunnels.length === 0"
                        class="col-span-full w-full rounded-xl border border-dashed border-[#2b4f8d] bg-[#081b3b] p-6 text-sm text-[#9fbce9]">
                        Nenhum funil encontrado para esse filtro.
                    </article>
                    <article v-if="activeTab === 'shared' && filteredSharedFunnels.length === 0"
                        class="col-span-full w-full rounded-xl border border-dashed border-[#2b4f8d] bg-[#081b3b] p-6 text-sm text-[#9fbce9]">
                        Ainda nao ha funis compartilhados com sua conta.
                    </article>
                </div>
            </section>

            <section class="mt-6 border-y border-[#1b3768] bg-[#050f25] py-6">
                <aside class="mx-auto max-w-6xl rounded-xl border border-[#254677] bg-[#061534] p-4">
                    <h3 class="text-sm uppercase tracking-[0.2em] text-[#8bb3f5]">
                        Resumo do dia
                    </h3>
                    <div class="mt-3 space-y-2.5">
                        <div class="rounded-lg border border-[#2f568f] bg-[#0b2755] p-3.5">
                            <p class="text-xs text-[#91b7f8]">Funis ativos</p>
                            <p class="mt-1 text-2xl font-semibold text-white">
                                {{props.funnels.filter((funnel) => funnel.is_active).length}}
                            </p>
                        </div>
                        <div class="rounded-lg border border-[#2f568f] bg-[#0b2755] p-3.5">
                            <p class="text-xs text-[#91b7f8]">Leads estimados</p>
                            <p class="mt-1 text-2xl font-semibold text-white">{{ props.stats.registeredLeads }}</p>
                        </div>
                        <div class="rounded-lg border border-[#2f568f] bg-[#0b2755] p-3.5">
                            <p class="text-xs text-[#91b7f8]">Capacidade de funis</p>
                            <p class="mt-1 text-2xl font-semibold text-white">
                                {{ props.stats.currentFunnels }}/{{ props.stats.maxFunnels }}
                            </p>
                        </div>
                    </div>
                    <div class="mt-3 rounded-lg border border-dashed border-[#2e588f] bg-[#0a2045] p-3.5">
                        <p class="text-xs text-[#9ebef5]">Status orbital</p>
                        <div class="mt-1.5 flex items-center gap-2 text-sm text-[#dbebff]">
                            <Sparkles class="size-4 text-[#7fd9ff]" />
                            {{ props.funnels.length > 0 ? 'Operacao ativa e captando leads' : 'Sem funis criados no momento' }}
                        </div>
                    </div>
                </aside>
            </section>
        </main>

        <Dialog :open="isCreateFunnelModalOpen" @update:open="isCreateFunnelModalOpen = $event">
            <DialogContent :show-close-button="false" class="w-[min(96vw,64rem)] max-w-4xl border-[#d8dce4] bg-[#f7f8fa] p-0 text-[#0f172a]">
                <div class="flex max-h-[90vh] min-h-0 flex-col p-4">
                    <DialogTitle class="sr-only">Criar funil</DialogTitle>
                    <DialogDescription class="sr-only">
                        Escolha um template ou comece em branco para criar um novo funil.
                    </DialogDescription>
                    <div class="mb-2.5 flex items-center justify-between">
                        <h2 class="text-xl font-medium">Criar funil</h2>
                        <button @click="closeCreateFunnelModal"
                            class="rounded-md p-0.5 text-[#1f2937] transition hover:bg-[#e9edf4]">
                            <X class="size-4" />
                        </button>
                    </div>

                    <div class="min-h-0 flex-1 space-y-2.5 overflow-y-auto pr-1">
                        <input data-testid="create-funnel-name" v-model="createFunnelForm.name"
                            class="w-full rounded-lg border border-[#cfd6e3] bg-transparent px-3 py-2 text-xs outline-none placeholder:italic placeholder:text-[#8a95a9] focus:border-[#5b8fff]"
                            placeholder="Titulo do seu funil..." />
                        <p v-if="createFunnelForm.errors.name" class="-mt-1 text-xs text-[#cf3451]">
                            {{ createFunnelForm.errors.name }}
                        </p>
                        <p v-if="createFunnelForm.errors.template_id" class="-mt-1 text-xs text-[#cf3451]">
                            {{ createFunnelForm.errors.template_id }}
                        </p>
                        <p v-if="createFunnelForm.errors.stages" class="-mt-1 text-xs text-[#cf3451]">
                            {{ createFunnelForm.errors.stages }}
                        </p>

                        <section class="rounded-xl border border-[#d6dbe6] bg-[#f5f6f8] p-2.5">
                            <div class="mb-2 grid gap-2 sm:grid-cols-[1fr_auto_auto]">
                                <input
                                    v-model="templateSearchQuery"
                                    class="rounded-lg border border-[#cfd6e3] bg-white px-3 py-2 text-xs outline-none placeholder:text-[#8a95a9] focus:border-[#5b8fff]"
                                    placeholder="Buscar template..."
                                />
                                <select v-model="templateCategoryFilter" class="rounded-lg border border-[#cfd6e3] bg-white px-3 py-2 text-xs outline-none focus:border-[#5b8fff]">
                                    <option value="">Todas categorias</option>
                                    <option v-for="category in props.templateCategories" :key="category" :value="category">
                                        {{ category }}
                                    </option>
                                </select>
                                <select v-model="templateScope" class="rounded-lg border border-[#cfd6e3] bg-white px-3 py-2 text-xs outline-none focus:border-[#5b8fff]">
                                    <option value="all">Todos</option>
                                    <option value="system">Sistema</option>
                                    <option value="mine">Meus</option>
                                </select>
                            </div>
                            <p class="mb-2 text-xs text-[#5d6a81]">Templates</p>
                            <div class="grid gap-2 sm:grid-cols-2">
                            <button
                                data-testid="template-card-blank"
                                @click="selectTemplate('blank')"
                                class="rounded-lg border bg-white p-1 text-left transition" :class="selectedTemplateId === 'blank'
                                    ? 'border-[#3f82ff] shadow-[0_0_0_1px_#3f82ff]'
                                    : 'border-[#d4dbe8]'">
                                <div class="relative mb-1 flex h-24 items-center justify-center overflow-hidden rounded-md border"
                                    :class="selectedTemplateId === 'blank'
                                        ? 'border-[#3f82ff] bg-[#f4f7ff]'
                                        : 'border-[#e3e7ef] bg-[linear-gradient(180deg,#ffffff_0%,#f3f5fa_100%)]'">
                                    <div v-if="selectedTemplateId === 'blank'"
                                        class="absolute top-1.5 left-1.5 h-5 w-5 rounded-full bg-[#2f6de2]" />
                                    <span class="text-3xl text-[#111827]">+</span>
                                </div>
                                <p class="text-center text-[11px] font-medium text-[#0f172a]">
                                    Em branco
                                </p>
                            </button>
                            <button v-for="template in availableTemplates" :key="template.id"
                                :data-testid="`template-card-${template.id}`"
                                @click="selectTemplate(String(template.id))"
                                class="rounded-lg border bg-white p-1 text-left transition"
                                :class="selectedTemplateId === String(template.id)
                                    ? 'border-[#3f82ff] shadow-[0_0_0_1px_#3f82ff]'
                                    : 'border-[#d4dbe8]'">
                                <div class="relative mb-1 overflow-hidden rounded-md border border-[#e3e7ef] bg-[linear-gradient(180deg,#ffffff_0%,#f3f5fa_100%)] p-2">
                                    <img v-if="template.thumbnail_path" :src="template.thumbnail_path" alt="Thumbnail do template" class="mb-2 h-24 w-full rounded-md object-cover" />
                                    <div v-if="selectedTemplateId === String(template.id)"
                                        class="absolute top-1.5 left-1.5 h-5 w-5 rounded-full bg-[#2f6de2]" />
                                    <div class="mb-2 flex items-center justify-between gap-2">
                                        <span
                                            class="rounded-full px-2 py-0.5 text-[9px] uppercase tracking-[0.12em] text-white"
                                            :style="{ backgroundColor: template.preview.accentColor }">
                                            {{ template.preview.badge }}
                                        </span>
                                        <span class="text-[10px] text-[#5d6a81]">
                                            v{{ template.version }} · {{ template.stage_count }} etapas
                                        </span>
                                    </div>
                                    <div class="mb-2 flex flex-wrap gap-1">
                                        <span v-if="template.is_premium" class="inline-flex items-center gap-1 rounded-full bg-[#fff1d6] px-2 py-0.5 text-[9px] font-semibold uppercase tracking-[0.08em] text-[#9f6500]">
                                            <Crown class="size-3" />
                                            Premium
                                        </span>
                                        <span class="rounded-full bg-[#eef3fb] px-2 py-0.5 text-[9px] text-[#53627c]">
                                            {{ template.is_system ? 'Sistema' : 'Meu template' }}
                                        </span>
                                        <span v-if="template.category" class="rounded-full bg-[#eef3fb] px-2 py-0.5 text-[9px] text-[#53627c]">
                                            {{ template.category }}
                                        </span>
                                    </div>
                                    <div class="mb-2 h-1 rounded"
                                        :style="{ backgroundColor: template.preview.accentColor }" />
                                    <p class="line-clamp-2 min-h-8 text-[11px] font-medium text-[#0f172a]">
                                        {{ template.preview.headline || template.description || 'Template pronto para acelerar a criacao do funil.' }}
                                    </p>
                                    <div class="mt-2 flex flex-wrap gap-1">
                                        <span v-for="chip in template.preview.chips" :key="chip"
                                            class="rounded-full bg-[#eef3fb] px-2 py-0.5 text-[9px] text-[#53627c]">
                                            {{ chip }}
                                        </span>
                                    </div>
                                </div>
                                <p class="text-center text-[11px] font-medium text-[#0f172a]">
                                    {{ template.name }}
                                </p>
                            </button>
                            </div>
                            <p v-if="availableTemplates.length === 0" class="mt-2 text-xs text-[#6c7890]">
                                Nenhum template encontrado para esse filtro.
                            </p>
                        </section>

                        <section class="rounded-xl border border-[#d6dbe6] bg-[#f5f6f8] p-2.5">
                            <div class="mb-1.5 flex items-center justify-between text-[11px] text-[#41506b]">
                                <p class="inline-flex items-center gap-1.5">
                                    <WandSparkles class="size-3.5" />
                                    Utilizar auxilio de IA
                                </p>
                                <span>{{ aiCharacterCount }}/280</span>
                            </div>
                            <textarea v-model="aiTopic" maxlength="280"
                                class="min-h-16 w-full resize-none rounded-lg border border-[#cfd6e3] bg-transparent px-2.5 py-2 text-[11px] outline-none placeholder:italic placeholder:text-[#8a95a9] focus:border-[#5b8fff]"
                                placeholder="Descreva o tema do seu funil..." />
                        </section>
                    </div>

                    <button data-testid="create-funnel-submit" @click="submitCreateFunnel" :disabled="createFunnelForm.processing"
                        class="mt-3 w-full rounded-lg bg-[#020b1f] py-2 text-xs font-medium text-white transition hover:bg-[#07163a] disabled:cursor-not-allowed disabled:opacity-70">
                        {{ createFunnelForm.processing ? 'Criando funil...' : 'Continuar' }}
                    </button>
                </div>
            </DialogContent>
        </Dialog>

        <Dialog :open="isShareModalOpen" @update:open="isShareModalOpen = $event">
            <DialogContent :show-close-button="false" class="max-w-md border-[#d8dce4] bg-[#f7f8fa] p-0 text-[#0f172a]">
                <div class="p-4">
                    <DialogTitle class="sr-only">Compartilhar funil</DialogTitle>
                    <DialogDescription class="sr-only">
                        Compartilhe o funil com outro usuario por e-mail.
                    </DialogDescription>
                    <div class="mb-2.5 flex items-center justify-between">
                        <h2 class="text-lg font-medium">Compartilhar funil</h2>
                        <button @click="closeShareModal"
                            class="rounded-md p-0.5 text-[#1f2937] transition hover:bg-[#e9edf4]">
                            <X class="size-4" />
                        </button>
                    </div>

                    <p class="mb-2 text-xs text-[#5d6a81]">
                        Funil: {{ selectedShareFunnel?.name ?? '-' }}
                    </p>

                    <input v-model="shareFunnelForm.email"
                        class="mb-2.5 w-full rounded-lg border border-[#cfd6e3] bg-transparent px-3 py-2 text-xs outline-none placeholder:italic placeholder:text-[#8a95a9] focus:border-[#5b8fff]"
                        placeholder="Email do usuario..." />
                    <p v-if="shareFunnelForm.errors.email" class="-mt-1 mb-2 text-xs text-[#cf3451]">
                        {{ shareFunnelForm.errors.email }}
                    </p>

                    <label class="mb-1 block text-xs text-[#5d6a81]">Permissao</label>
                    <select v-model="shareFunnelForm.role"
                        class="mb-2.5 w-full rounded-lg border border-[#cfd6e3] bg-transparent px-3 py-2 text-xs outline-none focus:border-[#5b8fff]">
                        <option value="viewer">Visualizador (somente visualizacao)</option>
                        <option value="editor">Editor (pode editar)</option>
                    </select>
                    <p v-if="shareFunnelForm.errors.role" class="-mt-1 mb-2 text-xs text-[#cf3451]">
                        {{ shareFunnelForm.errors.role }}
                    </p>

                    <button @click="submitShareFunnel" :disabled="shareFunnelForm.processing"
                        class="w-full rounded-lg bg-[#020b1f] py-2 text-xs font-medium text-white transition hover:bg-[#07163a] disabled:cursor-not-allowed disabled:opacity-70">
                        {{ shareFunnelForm.processing ? 'Compartilhando...' : 'Compartilhar' }}
                    </button>
                </div>
            </DialogContent>
        </Dialog>

        <Dialog :open="isSaveTemplateModalOpen" @update:open="isSaveTemplateModalOpen = $event">
            <DialogContent :show-close-button="false" class="max-w-md border-[#d8dce4] bg-[#f7f8fa] p-0 text-[#0f172a]">
                <div class="p-4">
                    <DialogTitle class="sr-only">Salvar funil como template</DialogTitle>
                    <DialogDescription class="sr-only">
                        Salve o funil atual como template reutilizavel na sua biblioteca.
                    </DialogDescription>
                    <div class="mb-2.5 flex items-center justify-between">
                        <h2 class="text-lg font-medium">Salvar como template</h2>
                        <button @click="closeSaveTemplateModal"
                            class="rounded-md p-0.5 text-[#1f2937] transition hover:bg-[#e9edf4]">
                            <X class="size-4" />
                        </button>
                    </div>

                    <input v-model="saveTemplateForm.name"
                        class="mb-2.5 w-full rounded-lg border border-[#cfd6e3] bg-transparent px-3 py-2 text-xs outline-none focus:border-[#5b8fff]"
                        placeholder="Nome do template" />
                    <p v-if="saveTemplateForm.errors.name" class="-mt-1 mb-2 text-xs text-[#cf3451]">
                        {{ saveTemplateForm.errors.name }}
                    </p>
                    <textarea v-model="saveTemplateForm.description"
                        class="mb-2.5 min-h-20 w-full rounded-lg border border-[#cfd6e3] bg-transparent px-3 py-2 text-xs outline-none focus:border-[#5b8fff]"
                        placeholder="Descricao do template" />
                    <p v-if="saveTemplateForm.errors.description" class="-mt-1 mb-2 text-xs text-[#cf3451]">
                        {{ saveTemplateForm.errors.description }}
                    </p>
                    <input v-model="saveTemplateForm.category"
                        class="mb-2.5 w-full rounded-lg border border-[#cfd6e3] bg-transparent px-3 py-2 text-xs outline-none focus:border-[#5b8fff]"
                        placeholder="Categoria" />
                    <p v-if="saveTemplateForm.errors.category" class="-mt-1 mb-2 text-xs text-[#cf3451]">
                        {{ saveTemplateForm.errors.category }}
                    </p>
                    <input v-model="saveTemplateForm.thumbnail_path"
                        class="mb-2.5 w-full rounded-lg border border-[#cfd6e3] bg-transparent px-3 py-2 text-xs outline-none focus:border-[#5b8fff]"
                        placeholder="Thumbnail (URL opcional)" />
                    <p v-if="saveTemplateForm.errors.thumbnail_path" class="-mt-1 mb-2 text-xs text-[#cf3451]">
                        {{ saveTemplateForm.errors.thumbnail_path }}
                    </p>

                    <label class="mb-2 flex items-center justify-between rounded-lg border border-[#d3dae8] bg-white px-3 py-2 text-xs text-[#334155]">
                        Ativo na biblioteca
                        <input v-model="saveTemplateForm.is_active" type="checkbox" class="accent-[#2f6de2]" />
                    </label>
                    <label class="mb-3 flex items-center justify-between rounded-lg border border-[#d3dae8] bg-white px-3 py-2 text-xs text-[#334155]">
                        Marcar como premium
                        <input v-model="saveTemplateForm.is_premium" type="checkbox" class="accent-[#2f6de2]" />
                    </label>

                    <button @click="submitSaveTemplate" :disabled="saveTemplateForm.processing"
                        class="w-full rounded-lg bg-[#020b1f] py-2 text-xs font-medium text-white transition hover:bg-[#07163a] disabled:cursor-not-allowed disabled:opacity-70">
                        {{ saveTemplateForm.processing ? 'Salvando template...' : 'Salvar template' }}
                    </button>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>
