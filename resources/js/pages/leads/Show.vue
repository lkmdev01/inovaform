<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

type LeadAnswer = {
    id: number;
    stage_name: string;
    block_label: string;
    value: string;
};

type LeadTimelineEvent = {
    id: string;
    type: string;
    source: string;
    actor_name: string;
    title: string;
    description: string;
    created_at: string | null;
};

const props = defineProps<{
    lead: {
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
        answers: LeadAnswer[];
        timeline: LeadTimelineEvent[];
        funnel: {
            id: number | null;
            name: string | null;
            slug: string | null;
        };
    };
    assigneeOptions: Array<{ value: number; label: string }>;
    statusOptions: Array<{ value: string; label: string }>;
    priorityOptions: Array<{ value: string; label: string }>;
}>();

const form = reactive({
    status: props.lead.status,
    assignee_id: props.lead.assignee.id,
    priority: props.lead.priority,
    next_follow_up_at: props.lead.next_follow_up_at ? props.lead.next_follow_up_at.slice(0, 16) : '',
    tags: props.lead.tags.join(', '),
    notes: props.lead.notes,
});

const groupedAnswers = computed(() => {
    const groups = new Map<string, LeadAnswer[]>();

    for (const answer of props.lead.answers) {
        const key = answer.stage_name || 'Sem etapa';
        const current = groups.get(key) ?? [];
        current.push(answer);
        groups.set(key, current);
    }

    return [...groups.entries()].map(([stageName, answers]) => ({ stageName, answers }));
});

function saveLead(): void {
    router.patch(`/leads/${props.lead.id}`, {
        status: form.status,
        assignee_id: form.assignee_id,
        priority: form.priority,
        next_follow_up_at: form.next_follow_up_at || null,
        tags: form.tags.split(',').map((item) => item.trim()).filter((item) => item.length > 0),
        notes: form.notes,
    }, {
        preserveScroll: true,
        preserveState: true,
    });
}

function formatDate(value: string | null): string {
    if (!value) {
        return '-';
    }

    return new Date(value).toLocaleString('pt-BR');
}
</script>

<template>
    <Head :title="`Lead #${props.lead.id}`" />

    <div class="min-h-screen bg-[#040d22] p-5 text-[#d8e7ff]">
        <div class="mx-auto max-w-6xl space-y-5">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-sm text-[#88a8df]">Lead detalhado</p>
                    <h1 class="text-3xl font-semibold text-white">{{ props.lead.lead_name || 'Sem nome' }}</h1>
                    <p class="mt-1 text-sm text-[#9dbcf0]">
                        {{ props.lead.funnel.name || 'Sem funil' }}<span v-if="props.lead.funnel.slug"> / {{ props.lead.funnel.slug }}</span>
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        v-if="props.lead.funnel.id"
                        :href="`/funnels/${props.lead.funnel.id}/leads`"
                        class="rounded-lg border border-[#2e4f8a] px-4 py-2 text-sm text-[#d8e7ff]"
                    >
                        Voltar ao funil
                    </Link>
                    <Link href="/leads" class="rounded-lg border border-[#2e4f8a] bg-[#0d2a57] px-4 py-2 text-sm text-[#d8e7ff]">
                        Todos os leads
                    </Link>
                </div>
            </div>

            <section class="grid gap-4 lg:grid-cols-[1.2fr_0.8fr]">
                <div class="space-y-4">
                    <article class="rounded-xl border border-[#20365f] bg-[#071733] p-5">
                        <h2 class="text-lg font-semibold text-white">Contato</h2>
                        <div class="mt-4 grid gap-3 md:grid-cols-2">
                            <div class="rounded-lg border border-[#1f3b6c] bg-[#091e41] p-3">
                                <p class="text-xs uppercase tracking-[0.08em] text-[#88a8df]">E-mail</p>
                                <p class="mt-1 text-sm text-white">{{ props.lead.lead_email || '-' }}</p>
                            </div>
                            <div class="rounded-lg border border-[#1f3b6c] bg-[#091e41] p-3">
                                <p class="text-xs uppercase tracking-[0.08em] text-[#88a8df]">Telefone</p>
                                <p class="mt-1 text-sm text-white">{{ props.lead.lead_phone || '-' }}</p>
                            </div>
                            <div class="rounded-lg border border-[#1f3b6c] bg-[#091e41] p-3">
                                <p class="text-xs uppercase tracking-[0.08em] text-[#88a8df]">Enviado em</p>
                                <p class="mt-1 text-sm text-white">{{ formatDate(props.lead.submitted_at) }}</p>
                            </div>
                            <div class="rounded-lg border border-[#1f3b6c] bg-[#091e41] p-3">
                                <p class="text-xs uppercase tracking-[0.08em] text-[#88a8df]">Pontuacao</p>
                                <p class="mt-1 text-sm text-white">{{ props.lead.score }}</p>
                            </div>
                            <div class="rounded-lg border border-[#1f3b6c] bg-[#091e41] p-3">
                                <p class="text-xs uppercase tracking-[0.08em] text-[#88a8df]">Ultimo contato</p>
                                <p class="mt-1 text-sm text-white">{{ formatDate(props.lead.last_contacted_at) }}</p>
                            </div>
                            <div class="rounded-lg border border-[#1f3b6c] bg-[#091e41] p-3">
                                <p class="text-xs uppercase tracking-[0.08em] text-[#88a8df]">Responsavel</p>
                                <p class="mt-1 text-sm text-white">{{ props.lead.assignee.name || 'Nao definido' }}</p>
                            </div>
                            <div class="rounded-lg border border-[#1f3b6c] bg-[#091e41] p-3">
                                <p class="text-xs uppercase tracking-[0.08em] text-[#88a8df]">Proximo follow-up</p>
                                <p class="mt-1 text-sm text-white">{{ formatDate(props.lead.next_follow_up_at) }}</p>
                            </div>
                        </div>
                    </article>

                    <article class="rounded-xl border border-[#20365f] bg-[#071733] p-5">
                        <h2 class="text-lg font-semibold text-white">Respostas</h2>
                        <div class="mt-4 space-y-4">
                            <section v-for="group in groupedAnswers" :key="group.stageName" class="rounded-lg border border-[#1f3b6c] bg-[#091e41] p-4">
                                <h3 class="text-sm font-semibold uppercase tracking-[0.08em] text-[#88a8df]">{{ group.stageName }}</h3>
                                <div class="mt-3 space-y-2">
                                    <div v-for="answer in group.answers" :key="answer.id" class="rounded-lg border border-[#214171] bg-[#0a2248] px-3 py-2">
                                        <p class="text-xs text-[#88a8df]">{{ answer.block_label || 'Campo sem label' }}</p>
                                        <p class="mt-1 text-sm text-white">{{ answer.value || '-' }}</p>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </article>
                </div>

                <div class="space-y-4">
                    <article class="rounded-xl border border-[#20365f] bg-[#071733] p-5">
                        <h2 class="text-lg font-semibold text-white">Gestao do lead</h2>
                        <div class="mt-4 space-y-3">
                            <div>
                                <label class="mb-1 block text-sm text-[#88a8df]">Status</label>
                                <select v-model="form.status" class="w-full rounded-lg border border-[#2e4f8a] bg-[#0a2147] px-3 py-2 text-sm text-white outline-none">
                                    <option v-for="status in props.statusOptions" :key="status.value" :value="status.value">{{ status.label }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm text-[#88a8df]">Responsavel</label>
                                <select v-model="form.assignee_id" class="w-full rounded-lg border border-[#2e4f8a] bg-[#0a2147] px-3 py-2 text-sm text-white outline-none">
                                    <option :value="null">Nao definido</option>
                                    <option v-for="assignee in props.assigneeOptions" :key="assignee.value" :value="assignee.value">{{ assignee.label }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm text-[#88a8df]">Prioridade</label>
                                <select v-model="form.priority" class="w-full rounded-lg border border-[#2e4f8a] bg-[#0a2147] px-3 py-2 text-sm text-white outline-none">
                                    <option v-for="priority in props.priorityOptions" :key="priority.value" :value="priority.value">{{ priority.label }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm text-[#88a8df]">Proximo follow-up</label>
                                <input v-model="form.next_follow_up_at" type="datetime-local" class="w-full rounded-lg border border-[#2e4f8a] bg-[#0a2147] px-3 py-2 text-sm text-white outline-none" />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm text-[#88a8df]">Tags</label>
                                <input
                                    v-model="form.tags"
                                    type="text"
                                    placeholder="vip, whatsapp, quente"
                                    class="w-full rounded-lg border border-[#2e4f8a] bg-[#0a2147] px-3 py-2 text-sm text-white outline-none"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm text-[#88a8df]">Notas internas</label>
                                <textarea
                                    v-model="form.notes"
                                    rows="6"
                                    placeholder="Historico comercial, contexto e proximos passos"
                                    class="w-full rounded-lg border border-[#2e4f8a] bg-[#0a2147] px-3 py-2 text-sm text-white outline-none"
                                />
                            </div>
                            <button class="rounded-lg bg-linear-to-r from-[#1d5fd2] to-[#3f8dff] px-4 py-2 text-sm font-semibold text-white" @click="saveLead">
                                Salvar lead
                            </button>
                        </div>
                    </article>

                    <article class="rounded-xl border border-[#20365f] bg-[#071733] p-5">
                        <h2 class="text-lg font-semibold text-white">Linha do tempo</h2>
                        <div class="mt-4 space-y-3">
                            <div v-for="event in props.lead.timeline" :key="event.id" class="rounded-lg border border-[#1f3b6c] bg-[#091e41] p-3">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-semibold text-white">{{ event.title }}</p>
                                    <span class="text-xs text-[#7fa4de]">{{ formatDate(event.created_at) }}</span>
                                </div>
                                <p class="mt-1 text-xs uppercase tracking-[0.08em] text-[#88a8df]">{{ event.actor_name }} / {{ event.source }}</p>
                                <p v-if="event.description" class="mt-2 text-sm text-[#cfe2ff]">{{ event.description }}</p>
                            </div>
                        </div>
                    </article>
                </div>
            </section>
        </div>
    </div>
</template>
