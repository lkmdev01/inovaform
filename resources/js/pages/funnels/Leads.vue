<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { BookOpen, CircleUserRound, Download, ListTree, Palette, Play, RotateCcw, Settings, Share2 } from 'lucide-vue-next';
import { computed, reactive, watch } from 'vue';
import FunnelController from '@/actions/App/Http/Controllers/FunnelController';
import profile from '@/routes/profile';

type LeadItem = {
    id: number;
    status: string;
    lead_name: string | null;
    lead_email: string | null;
    lead_phone: string | null;
    submitted_at: string | null;
    tags: string[];
    notes: string;
    last_contacted_at: string | null;
    next_follow_up_at: string | null;
    priority: string;
    assignee: {
        id: number | null;
        name: string;
    };
    stage_values: Record<string, string>;
    answers: Array<{
        id: number;
        stage_name: string;
        block_label: string;
        value: string;
    }>;
    timeline: Array<{
        id: string;
        type: string;
        source: string;
        actor_name: string;
        title: string;
        description: string;
        metadata: Record<string, unknown>;
        created_at: string | null;
    }>;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type StageStat = {
    id: number;
    name: string;
    count: number;
    conversion: number;
};

const props = defineProps<{
    funnel: {
        id: number;
        name: string;
        slug: string;
        is_active: boolean;
    };
    designSettings: {
        alignment: 'left' | 'center';
        width: 'small' | 'medium' | 'large';
        elementSize: 'compact' | 'default' | 'large';
        spacing: 'compact' | 'default' | 'large';
        radius: 'small' | 'medium' | 'large';
        showLogo: boolean;
        showProgress: boolean;
        allowBack: boolean;
        accentColor: string;
        pageColor: string;
        cardColor: string;
        headingColor: string;
        textColor: string;
        buttonColor: string;
        buttonTextColor: string;
        fontStyle: 'modern' | 'clean' | 'serif';
    };
    permissions: {
        canEdit: boolean;
        canShare: boolean;
        canManageLeads: boolean;
        role: 'owner' | 'editor' | 'viewer';
    };
    metrics: {
        visits_and_access: number;
        leads_acquired: number;
        interaction_rate: number;
        qualified_leads: number;
        completed_flows: number;
    };
    stageStats: StageStat[];
    leads: {
        data: LeadItem[];
        links: PaginationLink[];
    };
    filters: {
        q: string;
        status: string;
        tag: string;
        assignee_id: string;
        priority: string;
        has_notes: string;
        period: '24h' | '7d' | '30d';
    };
    assigneeOptions: Array<{ value: number; label: string }>;
    statusOptions: Array<{ value: string; label: string }>;
    priorityOptions: Array<{ value: string; label: string }>;
}>();
const page = usePage<{ flash?: { status?: string } }>();

const filters = reactive({
    q: props.filters.q ?? '',
    status: props.filters.status ?? '',
    tag: props.filters.tag ?? '',
    assignee_id: props.filters.assignee_id ?? '',
    priority: props.filters.priority ?? '',
    has_notes: props.filters.has_notes ?? '',
    period: props.filters.period ?? '30d',
});

const expandedLeadId = reactive<{ value: number | null }>({ value: null });

const leadDrafts = reactive<Record<number, {
    status: string;
    assignee_id: number | null;
    priority: string;
    next_follow_up_at: string;
    tags: string;
    notes: string;
}>>({});

function syncLeadDrafts(): void {
    for (const lead of props.leads.data) {
        leadDrafts[lead.id] = {
            status: lead.status,
            assignee_id: lead.assignee.id,
            priority: lead.priority,
            next_follow_up_at: lead.next_follow_up_at ? lead.next_follow_up_at.slice(0, 16) : '',
            tags: lead.tags.join(', '),
            notes: lead.notes,
        };
    }
}

watch(() => props.leads.data, syncLeadDrafts, { immediate: true });

function applyFilters(): void {
    router.get(
        FunnelController.leads(props.funnel.id).url,
        {
            q: filters.q,
            status: filters.status,
            tag: filters.tag,
            assignee_id: filters.assignee_id,
            priority: filters.priority,
            has_notes: filters.has_notes,
            period: filters.period,
        },
        {
            replace: true,
            preserveScroll: true,
            preserveState: true,
        },
    );
}

function resetFilters(): void {
    filters.q = '';
    filters.status = '';
    filters.tag = '';
    filters.assignee_id = '';
    filters.priority = '';
    filters.has_notes = '';
    filters.period = '30d';
    applyFilters();
}

function setPeriod(period: '24h' | '7d' | '30d'): void {
    filters.period = period;
    applyFilters();
}

function saveLeadDetails(leadId: number): void {
    const draft = leadDrafts[leadId];

    if (!draft) {
        return;
    }

    router.patch(`/leads/${leadId}`, {
        status: draft.status,
        assignee_id: draft.assignee_id,
        priority: draft.priority,
        next_follow_up_at: draft.next_follow_up_at || null,
        tags: draft.tags.split(',').map((item) => item.trim()).filter((item) => item.length > 0),
        notes: draft.notes,
    }, {
        preserveScroll: true,
        preserveState: true,
    });
}

function toggleLeadDetails(leadId: number): void {
    expandedLeadId.value = expandedLeadId.value === leadId ? null : leadId;
}

const exportUrl = computed(() => {
    const params = new URLSearchParams({
        funnel_id: String(props.funnel.id),
        period: filters.period,
    });

    if (filters.q !== '') {
        params.set('q', filters.q);
    }

    if (filters.status !== '') {
        params.set('status', filters.status);
    }

    if (filters.tag !== '') {
        params.set('tag', filters.tag);
    }

    if (filters.assignee_id !== '') {
        params.set('assignee_id', filters.assignee_id);
    }

    if (filters.priority !== '') {
        params.set('priority', filters.priority);
    }

    if (filters.has_notes !== '') {
        params.set('has_notes', filters.has_notes);
    }

    return `/leads/export/csv?${params.toString()}`;
});

const saveForm = useForm({
    design_settings: { ...props.designSettings },
    is_active: props.funnel.is_active,
});
const flashStatus = computed(() => page.props.flash?.status ?? '');

function saveFunnel(isPublishing = false): void {
    if (!props.permissions.canEdit) {
        return;
    }

    saveForm.design_settings = { ...props.designSettings };
    saveForm.is_active = isPublishing ? true : props.funnel.is_active;

    saveForm.submit(FunnelController.updateDesign(props.funnel.id), {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
}

function statusLabel(status: string): string {
    return props.statusOptions.find((option) => option.value === status)?.label ?? status;
}

function formatDate(date: string | null): string {
    if (!date) {
        return '-';
    }

    return new Date(date).toLocaleString('pt-BR');
}

function formatTags(tags: string[]): string {
    return tags.length > 0 ? tags.join(', ') : 'Sem tags';
}

function priorityLabel(value: string): string {
    return props.priorityOptions.find((option) => option.value === value)?.label ?? value;
}

function paginationLabel(label: string): string {
    return label
        .replaceAll('&laquo;', '«')
        .replaceAll('&raquo;', '»')
        .replaceAll('&hellip;', '…');
}
</script>

<template>
    <Head :title="`${props.funnel.name} - Leads`" />

    <div class="min-h-screen bg-[#050d22] text-[#d8e7ff]">
        <header class="border-b border-[#1e3157] bg-[#071430] px-4 py-3">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Link href="/dashboard" class="flex h-10 w-10 items-center justify-center rounded-lg border border-[#2f4f8c] bg-[#081b3c] text-base font-bold text-white">
                        IN
                    </Link>
                    <div>
                        <p class="text-lg font-semibold text-white">{{ props.funnel.name }}</p>
                        <p class="text-sm text-[#88a8df]">... / {{ props.funnel.slug }}</p>
                    </div>
                    <div v-if="props.permissions.role === 'viewer'" class="rounded-full border border-[#4e6eaa] bg-[#163463] px-2.5 py-1 text-xs text-[#d4e5ff]">
                        Somente leitura
                    </div>
                </div>

                <nav class="flex items-center gap-1.5 rounded-lg border border-[#253f70] bg-[#081a39] p-1.5 text-sm">
                    <Link :href="FunnelController.builder(props.funnel.id).url" class="rounded-md px-3.5 py-1.5 text-[#9ebbf0] hover:bg-[#0f274f]">
                        <span class="inline-flex items-center gap-1"><BookOpen class="size-4" /> Construtor</span>
                    </Link>
                    <Link :href="FunnelController.flow(props.funnel.id).url" class="rounded-md px-3.5 py-1.5 text-[#9ebbf0] hover:bg-[#0f274f]">
                        <span class="inline-flex items-center gap-1"><ListTree class="size-4" /> Fluxo</span>
                    </Link>
                    <Link :href="FunnelController.design(props.funnel.id).url" class="rounded-md px-3.5 py-1.5 text-[#9ebbf0] hover:bg-[#0f274f]">
                        <span class="inline-flex items-center gap-1"><Palette class="size-4" /> Design</span>
                    </Link>
                    <button class="rounded-md bg-[#1e4e9e] px-3.5 py-1.5 font-medium text-white">
                        <span class="inline-flex items-center gap-1"><CircleUserRound class="size-4" /> Leads</span>
                    </button>
                </nav>

                <div class="flex items-center gap-1.5">
                    <Link :href="profile.edit().url" class="rounded-md border border-[#2a487c] bg-[#081b39] p-1.5 text-[#b6cdff]">
                        <Settings class="size-4" />
                    </Link>
                    <button :disabled="!props.permissions.canShare" class="rounded-md border border-[#2a487c] bg-[#081b39] p-1.5 text-[#b6cdff] disabled:opacity-40">
                        <Share2 class="size-4" />
                    </button>
                    <Link :href="`/f/${props.funnel.slug}`" class="rounded-md border border-[#2a487c] bg-[#081b39] p-1.5 text-[#b6cdff]">
                        <Play class="size-4" />
                    </Link>
                    <button
                        :disabled="!props.permissions.canEdit || saveForm.processing"
                        class="rounded-md border border-[#3860a7] bg-[#0a2c61] px-4 py-1.5 text-sm font-medium text-white disabled:opacity-50"
                        @click="saveFunnel(false)"
                    >
                        Salvar
                    </button>
                    <button
                        :disabled="!props.permissions.canEdit || saveForm.processing"
                        class="rounded-md bg-linear-to-r from-[#1d5fd2] to-[#3f8dff] px-4 py-1.5 text-sm font-semibold text-white disabled:opacity-50"
                        @click="saveFunnel(true)"
                    >
                        Publicar
                    </button>
                    <span v-if="saveForm.processing" class="text-xs text-[#9ebbf0]">Salvando...</span>
                    <span v-else-if="flashStatus === 'design-saved'" class="text-xs text-emerald-300">Salvo</span>
                    <span v-else-if="flashStatus === 'funnel-published'" class="text-xs text-emerald-300">Publicado</span>
                </div>
            </div>
        </header>

        <main class="p-5">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3 border-b border-[#1d3159] pb-3">
                <div class="flex items-center gap-6 text-lg">
                    <button class="border-b-2 border-[#4f8fff] pb-2 text-white">Respostas</button>
                    <button class="pb-2 text-[#7f9fd5]">Resultados</button>
                    <button class="pb-2 text-[#7f9fd5]">Performance</button>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a :href="exportUrl" class="inline-flex items-center gap-2 rounded-md border border-[#2f4f8c] bg-[#0a234d] px-3 py-2 text-sm">
                        <Download class="size-4" />
                        Download
                    </a>
                    <button class="inline-flex items-center gap-2 rounded-md border border-[#2f4f8c] bg-[#0a234d] px-3 py-2 text-sm" @click="resetFilters">
                        <RotateCcw class="size-4" />
                        Resetar dados
                    </button>
                </div>
            </div>

            <section class="mb-4 grid gap-3 xl:grid-cols-5 md:grid-cols-2">
                <article class="rounded-xl border border-[#223a67] bg-[#081a39] p-4">
                    <p class="text-sm text-[#9dbcf0]">Visitas e Acessos</p>
                    <p class="mt-2 text-4xl font-semibold text-white">{{ props.metrics.visits_and_access }}</p>
                    <p class="mt-1 text-sm text-[#7f9fd5]">Visitantes que acessaram o funil</p>
                </article>
                <article class="rounded-xl border border-[#223a67] bg-[#081a39] p-4">
                    <p class="text-sm text-[#9dbcf0]">Leads adquiridos</p>
                    <p class="mt-2 text-4xl font-semibold text-white">{{ props.metrics.leads_acquired }}</p>
                    <p class="mt-1 text-sm text-[#7f9fd5]">Iniciaram alguma interacao com o funil</p>
                </article>
                <article class="rounded-xl border border-[#223a67] bg-[#081a39] p-4">
                    <p class="text-sm text-[#9dbcf0]">Taxa de interacao</p>
                    <p class="mt-2 text-4xl font-semibold text-white">{{ props.metrics.interaction_rate.toFixed(1) }}%</p>
                    <p class="mt-1 text-sm text-[#7f9fd5]">Leads com respostas na primeira etapa</p>
                </article>
                <article class="rounded-xl border border-[#223a67] bg-[#081a39] p-4">
                    <p class="text-sm text-[#9dbcf0]">Leads qualificados</p>
                    <p class="mt-2 text-4xl font-semibold text-white">{{ props.metrics.qualified_leads }}</p>
                    <p class="mt-1 text-sm text-[#7f9fd5]">Marcados com status qualificado</p>
                </article>
                <article class="rounded-xl border border-[#223a67] bg-[#081a39] p-4">
                    <p class="text-sm text-[#9dbcf0]">Fluxos completos</p>
                    <p class="mt-2 text-4xl font-semibold text-white">{{ props.metrics.completed_flows }}</p>
                    <p class="mt-1 text-sm text-[#7f9fd5]">Chegaram ate a ultima etapa</p>
                </article>
            </section>

            <section class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-[#223a67] bg-[#081a39] p-3">
                <div class="flex flex-wrap items-center gap-2">
                    <input
                        v-model="filters.q"
                        type="text"
                        placeholder="Buscar lead por nome, email ou telefone..."
                        class="w-[320px] max-w-full rounded-lg border border-[#2e4f8a] bg-[#0b2147] px-3 py-2 text-sm text-white outline-none"
                        @keyup.enter="applyFilters"
                    />
                    <select v-model="filters.status" class="rounded-lg border border-[#2e4f8a] bg-[#0b2147] px-3 py-2 text-sm text-white outline-none" @change="applyFilters">
                        <option v-for="status in props.statusOptions" :key="status.value" :value="status.value">{{ status.label }}</option>
                    </select>
                    <input
                        v-model="filters.tag"
                        type="text"
                        placeholder="Filtrar por tag..."
                        class="rounded-lg border border-[#2e4f8a] bg-[#0b2147] px-3 py-2 text-sm text-white outline-none"
                        @keyup.enter="applyFilters"
                    />
                    <select v-model="filters.assignee_id" class="rounded-lg border border-[#2e4f8a] bg-[#0b2147] px-3 py-2 text-sm text-white outline-none" @change="applyFilters">
                        <option value="">Todos os responsaveis</option>
                        <option v-for="assignee in props.assigneeOptions" :key="assignee.value" :value="String(assignee.value)">{{ assignee.label }}</option>
                    </select>
                    <select v-model="filters.priority" class="rounded-lg border border-[#2e4f8a] bg-[#0b2147] px-3 py-2 text-sm text-white outline-none" @change="applyFilters">
                        <option value="">Todas as prioridades</option>
                        <option v-for="priority in props.priorityOptions" :key="priority.value" :value="priority.value">{{ priority.label }}</option>
                    </select>
                    <select v-model="filters.has_notes" class="rounded-lg border border-[#2e4f8a] bg-[#0b2147] px-3 py-2 text-sm text-white outline-none" @change="applyFilters">
                        <option value="">Com e sem notas</option>
                        <option value="yes">Somente com notas</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        class="rounded-md border px-3 py-1.5 text-sm"
                        :class="filters.period === '30d' ? 'border-[#4f8fff] bg-[#12386d]' : 'border-[#2e4f8a] bg-[#0b2147]'"
                        @click="setPeriod('30d')"
                    >
                        30 dias
                    </button>
                    <button
                        class="rounded-md border px-3 py-1.5 text-sm"
                        :class="filters.period === '7d' ? 'border-[#4f8fff] bg-[#12386d]' : 'border-[#2e4f8a] bg-[#0b2147]'"
                        @click="setPeriod('7d')"
                    >
                        7 dias
                    </button>
                    <button
                        class="rounded-md border px-3 py-1.5 text-sm"
                        :class="filters.period === '24h' ? 'border-[#4f8fff] bg-[#12386d]' : 'border-[#2e4f8a] bg-[#0b2147]'"
                        @click="setPeriod('24h')"
                    >
                        24 horas
                    </button>
                </div>
            </section>

            <section class="overflow-hidden rounded-xl border border-[#223a67] bg-[#081a39]">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1080px] text-left text-sm">
                        <thead>
                            <tr class="border-b border-[#1e3561] bg-[#0a224c] text-[#cfe1ff]">
                                <th class="px-4 py-3">[ID] Lead</th>
                                <th class="px-4 py-3">Contato</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Data</th>
                                <th v-for="stage in props.stageStats" :key="stage.id" class="px-4 py-3 min-w-[220px]">
                                    <p class="font-semibold">{{ stage.name }}</p>
                                    <p class="text-xs text-[#8db3f2]">{{ stage.conversion.toFixed(1) }}% | {{ stage.count }} leads</p>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="lead in props.leads.data" :key="lead.id">
                            <tr class="border-b border-[#1a2f57] align-top">
                                <td class="px-4 py-3 font-medium text-white">
                                    <div class="flex items-start gap-2">
                                        <button class="mt-0.5 rounded border border-[#2e4f8a] px-1.5 py-0.5 text-[11px] text-[#9ec0fa]" @click="toggleLeadDetails(lead.id)">
                                            {{ expandedLeadId.value === lead.id ? '-' : '+' }}
                                        </button>
                                        <span>#{{ lead.id }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-white">{{ lead.lead_name || 'Sem nome' }}</p>
                                    <p class="text-xs text-[#91b2e9]">{{ lead.lead_email || 'Sem e-mail' }}</p>
                                    <p class="text-xs text-[#7898d0]">{{ lead.lead_phone || 'Sem telefone' }}</p>
                                    <p class="mt-1 text-[11px]"><Link :href="`/leads/${lead.id}`" class="text-[#7fb0ff] hover:text-white">Abrir detalhe</Link></p>
                                    <p class="mt-2 text-[11px] text-[#82a3da]">Tags atuais: {{ formatTags(lead.tags) }}</p>
                                    <p class="mt-1 text-[11px] text-[#6f91c8]">Responsavel: {{ lead.assignee.name || 'Nao definido' }}</p>
                                    <p class="mt-1 text-[11px] text-[#6f91c8]">Prioridade: {{ priorityLabel(lead.priority) }}</p>
                                    <p class="mt-1 text-[11px] text-[#6f91c8]">Ultimo contato: {{ formatDate(lead.last_contacted_at) }}</p>
                                    <p class="mt-1 text-[11px] text-[#6f91c8]">Proximo follow-up: {{ formatDate(lead.next_follow_up_at) }}</p>
                                    <div class="mt-2 space-y-2 rounded-lg border border-[#1f3b6c] bg-[#091e41] p-2">
                                        <select
                                            v-model="leadDrafts[lead.id].assignee_id"
                                            class="w-full rounded-md border border-[#2e4f8a] bg-[#0b2147] px-2 py-1.5 text-xs text-white outline-none"
                                        >
                                            <option :value="null">Nao definido</option>
                                            <option v-for="assignee in props.assigneeOptions" :key="assignee.value" :value="assignee.value">{{ assignee.label }}</option>
                                        </select>
                                        <select
                                            v-model="leadDrafts[lead.id].priority"
                                            class="w-full rounded-md border border-[#2e4f8a] bg-[#0b2147] px-2 py-1.5 text-xs text-white outline-none"
                                        >
                                            <option v-for="priority in props.priorityOptions" :key="priority.value" :value="priority.value">{{ priority.label }}</option>
                                        </select>
                                        <input
                                            v-model="leadDrafts[lead.id].next_follow_up_at"
                                            type="datetime-local"
                                            class="w-full rounded-md border border-[#2e4f8a] bg-[#0b2147] px-2 py-1.5 text-xs text-white outline-none"
                                        />
                                        <input
                                            v-model="leadDrafts[lead.id].tags"
                                            type="text"
                                            placeholder="Tags separadas por virgula"
                                            class="w-full rounded-md border border-[#2e4f8a] bg-[#0b2147] px-2 py-1.5 text-xs text-white outline-none"
                                        />
                                        <textarea
                                            v-model="leadDrafts[lead.id].notes"
                                            rows="3"
                                            placeholder="Observacoes internas..."
                                            class="w-full rounded-md border border-[#2e4f8a] bg-[#0b2147] px-2 py-1.5 text-xs text-white outline-none"
                                        ></textarea>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <select
                                        v-model="leadDrafts[lead.id].status"
                                        class="rounded-md border border-[#2e4f8a] bg-[#0b2147] px-2 py-1 text-xs text-white outline-none"
                                    >
                                        <option v-for="status in props.statusOptions.filter((item) => item.value !== '')" :key="status.value" :value="status.value">
                                            {{ status.label }}
                                        </option>
                                    </select>
                                    <p class="mt-1 text-[11px] text-[#88a8df]">{{ statusLabel(leadDrafts[lead.id]?.status ?? lead.status) }}</p>
                                    <button class="mt-2 rounded-md border border-[#3d6cc0] bg-[#12386d] px-2 py-1 text-[11px] text-white" @click="saveLeadDetails(lead.id)">
                                        Atualizar lead
                                    </button>
                                </td>
                                <td class="px-4 py-3 text-xs text-[#9ec0fa]">{{ formatDate(lead.submitted_at) }}</td>
                                <td v-for="stage in props.stageStats" :key="`${lead.id}-${stage.id}`" class="px-4 py-3 text-xs text-[#cfe1ff]">
                                    {{ lead.stage_values[String(stage.id)] || '-' }}
                                </td>
                            </tr>
                            <tr v-if="expandedLeadId.value === lead.id" class="border-b border-[#1a2f57] bg-[#07162f]">
                                <td :colspan="4 + props.stageStats.length" class="px-4 py-4">
                                    <div class="grid gap-4 xl:grid-cols-2">
                                        <section class="rounded-xl border border-[#20365f] bg-[#081a39] p-4">
                                            <p class="text-sm font-semibold text-white">Respostas do lead</p>
                                            <div v-if="lead.answers.length > 0" class="mt-3 space-y-2">
                                                <article v-for="answer in lead.answers" :key="answer.id" class="rounded-lg border border-[#1e3561] bg-[#091e41] p-3">
                                                    <p class="text-[11px] uppercase tracking-[0.18em] text-[#7fa3de]">{{ answer.stage_name || 'Etapa' }}</p>
                                                    <p class="mt-1 text-sm font-medium text-white">{{ answer.block_label || 'Campo sem titulo' }}</p>
                                                    <p class="mt-1 text-sm text-[#cfe1ff]">{{ answer.value || '-' }}</p>
                                                </article>
                                            </div>
                                            <p v-else class="mt-3 text-sm text-[#8fb0e6]">Sem respostas registradas.</p>
                                        </section>

                                        <section class="rounded-xl border border-[#20365f] bg-[#081a39] p-4">
                                            <p class="text-sm font-semibold text-white">Linha do tempo</p>
                                            <div v-if="lead.timeline.length > 0" class="mt-3 space-y-3">
                                                <article v-for="event in lead.timeline" :key="event.id" class="rounded-lg border border-[#1e3561] bg-[#091e41] p-3">
                                                    <div class="flex items-center justify-between gap-3">
                                                        <p class="text-sm font-medium text-white">{{ event.title }}</p>
                                                        <p class="text-[11px] text-[#7fa3de]">{{ formatDate(event.created_at) }}</p>
                                                    </div>
                                                    <p class="mt-1 text-[11px] uppercase tracking-[0.16em] text-[#6f91c8]">{{ event.actor_name }} / {{ event.source }}</p>
                                                    <p v-if="event.description" class="mt-1 text-sm text-[#9ec0fa]">{{ event.description }}</p>
                                                    <div v-if="Object.keys(event.metadata || {}).length > 0" class="mt-2 rounded-md border border-[#214171] bg-[#0a2248] p-2">
                                                        <p class="text-[10px] uppercase tracking-[0.16em] text-[#7fa3de]">Metadata</p>
                                                        <pre class="mt-1 overflow-x-auto text-[11px] text-[#cfe1ff]">{{ JSON.stringify(event.metadata, null, 2) }}</pre>
                                                    </div>
                                                </article>
                                            </div>
                                            <p v-else class="mt-3 text-sm text-[#8fb0e6]">Sem eventos registrados.</p>
                                        </section>
                                    </div>
                                </td>
                            </tr>
                            </template>
                            <tr v-if="props.leads.data.length === 0">
                                <td :colspan="4 + props.stageStats.length" class="px-4 py-10 text-center text-[#95b2e8]">
                                    Nenhum lead encontrado com os filtros atuais.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="mt-4 flex flex-wrap gap-2">
                <Link
                    v-for="link in props.leads.links"
                    :key="link.label"
                    :href="link.url || ''"
                    :class="[
                        'rounded-md border px-3 py-1.5 text-xs',
                        link.active ? 'border-[#4f8fff] bg-[#12386d] text-white' : 'border-[#2e4f8a] text-[#cfe1ff]',
                        !link.url ? 'pointer-events-none opacity-40' : '',
                    ]"
                    preserve-state
                    preserve-scroll
                >
                    {{ paginationLabel(link.label) }}
                </Link>
            </div>
        </main>
    </div>
</template>
