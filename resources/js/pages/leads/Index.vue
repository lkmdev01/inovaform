<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive, watch } from 'vue';

type LeadItem = {
    id: number;
    status: string;
    lead_name: string | null;
    lead_email: string | null;
    lead_phone: string | null;
    submitted_at: string | null;
    score: number;
    tags: string[];
    notes: string;
    last_contacted_at: string | null;
    next_follow_up_at: string | null;
    priority: string;
    assignee: {
        id: number | null;
        name: string;
    };
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
    funnel: {
        id: number | null;
        name: string | null;
        slug: string | null;
    };
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type FunnelOption = {
    id: number;
    name: string;
    slug: string;
};

const props = defineProps<{
    leads: {
        data: LeadItem[];
        links: PaginationLink[];
    };
    filters: {
        q: string;
        status: string;
        funnel_id: string;
        tag: string;
        assignee_id: string;
        priority: string;
        has_notes: string;
    };
    funnels: FunnelOption[];
    assigneeOptions: Array<{ value: number; label: string }>;
    statusOptions: Array<{ value: string; label: string }>;
    priorityOptions: Array<{ value: string; label: string }>;
}>();

const filters = reactive({
    q: props.filters.q ?? '',
    status: props.filters.status ?? '',
    funnel_id: props.filters.funnel_id ?? '',
    tag: props.filters.tag ?? '',
    assignee_id: props.filters.assignee_id ?? '',
    priority: props.filters.priority ?? '',
    has_notes: props.filters.has_notes ?? '',
});

const leadDrafts = reactive<Record<number, {
    status: string;
    assignee_id: number | null;
    priority: string;
    next_follow_up_at: string;
    tags: string;
    notes: string;
}>>({});

const expandedLeadId = reactive<{ value: number | null }>({ value: null });

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
    router.get('/leads', filters, {
        replace: true,
        preserveState: true,
        preserveScroll: true,
    });
}

function clearFilters(): void {
    filters.q = '';
    filters.status = '';
    filters.funnel_id = '';
    filters.tag = '';
    filters.assignee_id = '';
    filters.priority = '';
    filters.has_notes = '';
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
    const params = new URLSearchParams();

    if (filters.q !== '') {
        params.set('q', filters.q);
    }

    if (filters.status !== '') {
        params.set('status', filters.status);
    }

    if (filters.funnel_id !== '') {
        params.set('funnel_id', filters.funnel_id);
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

    const queryString = params.toString();

    return queryString.length > 0 ? `/leads/export/csv?${queryString}` : '/leads/export/csv';
});

function formatTags(tags: string[]): string {
    return tags.length > 0 ? tags.join(', ') : 'Sem tags';
}

function formatDate(value: string | null): string {
    if (!value) {
        return '-';
    }

    return new Date(value).toLocaleString('pt-BR');
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
    <Head title="Leads" />

    <div class="min-h-screen bg-[#040d22] p-5 text-[#d8e7ff]">
        <div class="mx-auto max-w-7xl">
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-semibold text-white">Leads</h1>
                    <p class="mt-1 text-sm text-[#9dbcf0]">Gestao das respostas capturadas nos funis.</p>
                </div>
                <div class="flex items-center gap-2">
                    <a :href="exportUrl" class="rounded-lg border border-[#2e4f8a] bg-[#0d2a57] px-4 py-2 text-sm text-[#d8e7ff]">Exportar CSV</a>
                    <Link href="/dashboard" class="rounded-lg border border-[#2e4f8a] px-4 py-2 text-sm text-[#d8e7ff]">Dashboard</Link>
                </div>
            </div>

            <section class="mb-4 grid gap-3 rounded-xl border border-[#20365f] bg-[#071733] p-4 md:grid-cols-4">
                <input v-model="filters.q" type="text" placeholder="Buscar por nome, email ou telefone" class="rounded-lg border border-[#2e4f8a] bg-[#0a2147] px-3 py-2 text-sm text-white outline-none md:col-span-2" @keyup.enter="applyFilters" />

                <select v-model="filters.status" class="rounded-lg border border-[#2e4f8a] bg-[#0a2147] px-3 py-2 text-sm text-white outline-none">
                    <option value="">Todos os status</option>
                    <option v-for="status in props.statusOptions" :key="status.value" :value="status.value">{{ status.label }}</option>
                </select>

                <select v-model="filters.funnel_id" class="rounded-lg border border-[#2e4f8a] bg-[#0a2147] px-3 py-2 text-sm text-white outline-none">
                    <option value="">Todos os funis</option>
                    <option v-for="funnel in props.funnels" :key="funnel.id" :value="String(funnel.id)">{{ funnel.name }}</option>
                </select>

                <input v-model="filters.tag" type="text" placeholder="Filtrar por tag" class="rounded-lg border border-[#2e4f8a] bg-[#0a2147] px-3 py-2 text-sm text-white outline-none md:col-span-2" @keyup.enter="applyFilters" />

                <select v-model="filters.assignee_id" class="rounded-lg border border-[#2e4f8a] bg-[#0a2147] px-3 py-2 text-sm text-white outline-none">
                    <option value="">Todos os responsaveis</option>
                    <option v-for="assignee in props.assigneeOptions" :key="assignee.value" :value="String(assignee.value)">{{ assignee.label }}</option>
                </select>

                <select v-model="filters.priority" class="rounded-lg border border-[#2e4f8a] bg-[#0a2147] px-3 py-2 text-sm text-white outline-none">
                    <option value="">Todas as prioridades</option>
                    <option v-for="priority in props.priorityOptions" :key="priority.value" :value="priority.value">{{ priority.label }}</option>
                </select>

                <select v-model="filters.has_notes" class="rounded-lg border border-[#2e4f8a] bg-[#0a2147] px-3 py-2 text-sm text-white outline-none">
                    <option value="">Com e sem notas</option>
                    <option value="yes">Somente com notas</option>
                </select>

                <div class="md:col-span-4 flex items-center gap-2">
                    <button class="rounded-lg bg-gradient-to-r from-[#1d5fd2] to-[#3f8dff] px-4 py-2 text-sm font-semibold text-white" @click="applyFilters">Filtrar</button>
                    <button class="rounded-lg border border-[#2e4f8a] px-4 py-2 text-sm text-[#d8e7ff]" @click="clearFilters">Limpar</button>
                </div>
            </section>

            <section class="overflow-hidden rounded-xl border border-[#20365f] bg-[#071733]">
                <table class="w-full text-left text-sm">
                    <thead class="bg-[#0b2044] text-[#9ec0fa]">
                        <tr>
                            <th class="px-4 py-3">Lead</th>
                            <th class="px-4 py-3">Contato</th>
                            <th class="px-4 py-3">Funil</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="lead in props.leads.data" :key="lead.id">
                            <tr class="border-t border-[#1a3157]">
                                <td class="px-4 py-3">
                                    <div class="flex items-start gap-2">
                                        <button class="mt-0.5 rounded border border-[#2e4f8a] px-1.5 py-0.5 text-[11px] text-[#9ec0fa]" @click="toggleLeadDetails(lead.id)">
                                            {{ expandedLeadId.value === lead.id ? '-' : '+' }}
                                        </button>
                                        <div>
                                            <span>{{ lead.lead_name || 'Sem nome' }}</span>
                                            <div class="mt-1">
                                                <Link :href="`/leads/${lead.id}`" class="text-[11px] text-[#7fb0ff] hover:text-white">Abrir detalhe</Link>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <p>{{ lead.lead_email || 'Sem email' }}</p>
                                    <p class="text-xs text-[#87a9e6]">{{ lead.lead_phone || 'Sem telefone' }}</p>
                                    <p class="mt-2 text-[11px] text-[#8fb0e6]">Tags atuais: {{ formatTags(lead.tags) }}</p>
                                    <p class="mt-1 text-[11px] text-[#7092cc]">Responsavel: {{ lead.assignee.name || 'Nao definido' }}</p>
                                    <p class="mt-1 text-[11px] text-[#7092cc]">Prioridade: {{ priorityLabel(lead.priority) }}</p>
                                    <p class="mt-1 text-[11px] text-[#7092cc]">Pontuacao: {{ lead.score }}</p>
                                    <p class="mt-1 text-[11px] text-[#7092cc]">Ultimo contato: {{ formatDate(lead.last_contacted_at) }}</p>
                                    <p class="mt-1 text-[11px] text-[#7092cc]">Proximo follow-up: {{ formatDate(lead.next_follow_up_at) }}</p>
                                    <div class="mt-2 space-y-2 rounded-lg border border-[#1f3b6c] bg-[#091e41] p-2">
                                        <select v-model="leadDrafts[lead.id].assignee_id" class="w-full rounded-md border border-[#2e4f8a] bg-[#0a2147] px-2 py-1.5 text-xs text-white outline-none">
                                            <option :value="null">Nao definido</option>
                                            <option v-for="assignee in props.assigneeOptions" :key="assignee.value" :value="assignee.value">{{ assignee.label }}</option>
                                        </select>
                                        <select v-model="leadDrafts[lead.id].priority" class="w-full rounded-md border border-[#2e4f8a] bg-[#0a2147] px-2 py-1.5 text-xs text-white outline-none">
                                            <option v-for="priority in props.priorityOptions" :key="priority.value" :value="priority.value">{{ priority.label }}</option>
                                        </select>
                                        <input v-model="leadDrafts[lead.id].next_follow_up_at" type="datetime-local" class="w-full rounded-md border border-[#2e4f8a] bg-[#0a2147] px-2 py-1.5 text-xs text-white outline-none" />
                                        <input v-model="leadDrafts[lead.id].tags" type="text" placeholder="Tags separadas por virgula" class="w-full rounded-md border border-[#2e4f8a] bg-[#0a2147] px-2 py-1.5 text-xs text-white outline-none" />
                                        <textarea v-model="leadDrafts[lead.id].notes" rows="3" placeholder="Observacoes internas..." class="w-full rounded-md border border-[#2e4f8a] bg-[#0a2147] px-2 py-1.5 text-xs text-white outline-none"></textarea>
                                    </div>
                                </td>
                                <td class="px-4 py-3">{{ lead.funnel.name || '-' }}</td>
                                <td class="px-4 py-3">
                                    <select v-model="leadDrafts[lead.id].status" class="rounded-md border border-[#2e4f8a] bg-[#0a2147] px-2 py-1 text-xs text-white outline-none">
                                        <option v-for="status in props.statusOptions.filter((item) => item.value !== '')" :key="status.value" :value="status.value">{{ status.label }}</option>
                                    </select>
                                    <button class="mt-2 rounded-md border border-[#3d6cc0] bg-[#12386d] px-2 py-1 text-[11px] text-white" @click="saveLeadDetails(lead.id)">Atualizar lead</button>
                                </td>
                                <td class="px-4 py-3 text-xs text-[#9ec0fa]">{{ formatDate(lead.submitted_at) }}</td>
                            </tr>
                            <tr v-if="expandedLeadId.value === lead.id" class="border-t border-[#1a3157] bg-[#06142d]">
                                <td colspan="5" class="px-4 py-4">
                                    <div class="grid gap-4 xl:grid-cols-2">
                                        <section class="rounded-xl border border-[#20365f] bg-[#071733] p-4">
                                            <p class="text-sm font-semibold text-white">Respostas do lead</p>
                                            <div v-if="lead.answers.length > 0" class="mt-3 space-y-2">
                                                <article v-for="answer in lead.answers" :key="answer.id" class="rounded-lg border border-[#1a3157] bg-[#0a2147] p-3">
                                                    <p class="text-[11px] uppercase tracking-[0.18em] text-[#7fa3de]">{{ answer.stage_name || 'Etapa' }}</p>
                                                    <p class="mt-1 text-sm font-medium text-white">{{ answer.block_label || 'Campo sem titulo' }}</p>
                                                    <p class="mt-1 text-sm text-[#cfe1ff]">{{ answer.value || '-' }}</p>
                                                </article>
                                            </div>
                                            <p v-else class="mt-3 text-sm text-[#8fb0e6]">Sem respostas registradas.</p>
                                        </section>
                                        <section class="rounded-xl border border-[#20365f] bg-[#071733] p-4">
                                            <p class="text-sm font-semibold text-white">Linha do tempo</p>
                                            <div v-if="lead.timeline.length > 0" class="mt-3 space-y-2">
                                                <article v-for="event in lead.timeline" :key="event.id" class="rounded-lg border border-[#1a3157] bg-[#0a2147] p-3">
                                                    <div class="flex items-center justify-between gap-3">
                                                        <p class="text-sm font-medium text-white">{{ event.title }}</p>
                                                        <p class="text-[11px] text-[#7fa3de]">{{ formatDate(event.created_at) }}</p>
                                                    </div>
                                                    <p class="mt-1 text-[11px] uppercase tracking-[0.16em] text-[#6f91c8]">{{ event.actor_name }} / {{ event.source }}</p>
                                                    <p v-if="event.description" class="mt-1 text-sm text-[#cfe1ff]">{{ event.description }}</p>
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
                            <td colspan="5" class="px-4 py-8 text-center text-[#9ec0fa]">Nenhum lead encontrado com os filtros aplicados.</td>
                        </tr>
                    </tbody>
                </table>
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
        </div>
    </div>
</template>
