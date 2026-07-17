<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import FunnelController from '@/actions/App/Http/Controllers/FunnelController';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

type FunnelStageItem = {
    id: number;
    name: string;
    stage_order: number;
    conversion_rate: string | null;
    expected_volume: number | null;
};

type FunnelItem = {
    id: number;
    name: string;
    description: string | null;
    target_leads: number | null;
    is_active: boolean;
    stages: FunnelStageItem[];
};

type StageDraft = {
    id: string;
    name: string;
    conversion_rate: string;
    expected_volume: string;
};

const props = defineProps<{
    funnels: FunnelItem[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Funnels',
        href: FunnelController.index(),
    },
];

function createClientId(): string {
    if (
        typeof globalThis !== 'undefined' &&
        globalThis.crypto &&
        typeof globalThis.crypto.randomUUID === 'function'
    ) {
        return globalThis.crypto.randomUUID();
    }

    return `id-${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;
}

const stages = ref<StageDraft[]>([
    {
        id: createClientId(),
        name: 'Visitantes',
        conversion_rate: '100',
        expected_volume: '5000',
    },
    {
        id: createClientId(),
        name: 'Leads',
        conversion_rate: '35',
        expected_volume: '1750',
    },
    {
        id: createClientId(),
        name: 'Clientes',
        conversion_rate: '18',
        expected_volume: '315',
    },
]);

const form = useForm({
    name: '',
    description: '',
    target_leads: '',
    is_active: true,
    stages: [] as Array<{
        name: string;
        conversion_rate: number | null;
        expected_volume: number | null;
    }>,
});

function addStage(): void {
    stages.value.push({
        id: createClientId(),
        name: `Etapa ${stages.value.length + 1}`,
        conversion_rate: '',
        expected_volume: '',
    });
}

function removeStage(index: number): void {
    if (stages.value.length <= 2) {
        return;
    }

    stages.value.splice(index, 1);
}

function onStageDragStart(event: DragEvent, index: number): void {
    event.dataTransfer?.setData('stage-index', String(index));
}

function onStageDrop(event: DragEvent, targetIndex: number): void {
    event.preventDefault();

    const sourceIndexText = event.dataTransfer?.getData('stage-index');

    if (!sourceIndexText) {
        return;
    }

    const sourceIndex = Number(sourceIndexText);

    if (
        Number.isNaN(sourceIndex) ||
        sourceIndex < 0 ||
        sourceIndex >= stages.value.length ||
        sourceIndex === targetIndex
    ) {
        return;
    }

    const [stage] = stages.value.splice(sourceIndex, 1);
    stages.value.splice(targetIndex, 0, stage);
}

function saveFunnel(): void {
    form.stages = stages.value.map((stage) => ({
        name: stage.name,
        conversion_rate: stage.conversion_rate.length > 0 ? Number(stage.conversion_rate) : null,
        expected_volume: stage.expected_volume.length > 0 ? Number(stage.expected_volume) : null,
    }));

    form.submit(FunnelController.store(), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('name', 'description', 'target_leads');
            form.is_active = true;
            stages.value = [
                {
                    id: createClientId(),
                    name: 'Visitantes',
                    conversion_rate: '100',
                    expected_volume: '5000',
                },
                {
                    id: createClientId(),
                    name: 'Leads',
                    conversion_rate: '35',
                    expected_volume: '1750',
                },
                {
                    id: createClientId(),
                    name: 'Clientes',
                    conversion_rate: '18',
                    expected_volume: '315',
                },
            ];
        },
    });
}

const estimatedAverageConversion = computed(() => {
    if (stages.value.length < 2) {
        return 0;
    }

    return stages.value
        .slice(1)
        .map((stage) => Number(stage.conversion_rate || '0'))
        .reduce((carry, value) => carry * (value / 100), 1) * 100;
});

function totalConversionFromStages(stagesList: FunnelStageItem[]): string {
    if (stagesList.length < 2) {
        return '0.00';
    }

    const total = stagesList
        .slice(1)
        .map((stage) => Number(stage.conversion_rate ?? '0'))
        .reduce((carry, value) => carry * (value / 100), 1) * 100;

    return total.toFixed(2);
}
</script>

<template>
    <Head title="Funnels" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="grid gap-4 p-4 lg:grid-cols-[1fr_360px]">
            <section class="rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-sm font-semibold">Modelo de funil</h2>
                    <button class="text-xs text-blue-500" type="button" @click="addStage">
                        Adicionar etapa
                    </button>
                </div>

                <div class="space-y-3">
                    <div
                        v-for="(stage, index) in stages"
                        :key="stage.id"
                        class="rounded-lg border border-border bg-background p-4"
                        draggable="true"
                        @dragstart="onStageDragStart($event, index)"
                        @dragover.prevent
                        @drop="onStageDrop($event, index)"
                    >
                        <div class="mb-3 flex items-center justify-between">
                            <p class="text-xs text-muted-foreground">Etapa {{ index + 1 }}</p>
                            <button
                                class="rounded-md border border-border px-2 py-1 text-xs text-muted-foreground"
                                type="button"
                                @click="removeStage(index)"
                            >
                                Remover
                            </button>
                        </div>

                        <div class="grid gap-3 md:grid-cols-3">
                            <div class="space-y-2 md:col-span-2">
                                <Label>Nome da etapa</Label>
                                <Input v-model="stage.name" />
                            </div>
                            <div class="space-y-2">
                                <Label>Conv. %</Label>
                                <Input v-model="stage.conversion_rate" type="number" min="0" max="100" step="0.01" />
                            </div>
                        </div>

                        <div class="mt-3 space-y-2">
                            <Label>Volume esperado</Label>
                            <Input v-model="stage.expected_volume" type="number" min="0" />
                        </div>
                    </div>
                </div>
            </section>

            <aside class="rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border">
                <h2 class="text-sm font-semibold">Salvar funil</h2>
                <div class="mt-4 space-y-3">
                    <div class="space-y-2">
                        <Label for="funnel-name">Nome</Label>
                        <Input id="funnel-name" v-model="form.name" placeholder="Funil webinar Q2" />
                        <p v-if="form.errors.name" class="text-xs text-red-500">{{ form.errors.name }}</p>
                    </div>
                    <div class="space-y-2">
                        <Label for="funnel-description">Descricao</Label>
                        <textarea
                            id="funnel-description"
                            v-model="form.description"
                            class="min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="target-leads">Meta de leads</Label>
                        <Input id="target-leads" v-model="form.target_leads" type="number" min="1" />
                    </div>
                    <label class="flex items-center gap-2 text-xs text-muted-foreground">
                        <input v-model="form.is_active" type="checkbox" />
                        Funil ativo
                    </label>
                    <p class="rounded-md bg-blue-500/10 px-3 py-2 text-xs text-blue-500">
                        Conversao total estimada: {{ estimatedAverageConversion.toFixed(2) }}%
                    </p>
                    <p v-if="form.errors.stages" class="text-xs text-red-500">{{ form.errors.stages }}</p>
                    <Button class="w-full" :disabled="form.processing" @click="saveFunnel">
                        {{ form.processing ? 'Salvando...' : 'Salvar funil' }}
                    </Button>
                </div>
            </aside>
        </div>

        <div class="px-4 pb-6">
            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border">
                <h2 class="text-sm font-semibold">Funis criados</h2>
                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    <article
                        v-for="funnel in props.funnels"
                        :key="funnel.id"
                        class="rounded-lg border border-border bg-background p-4"
                    >
                        <div class="flex items-center justify-between">
                            <p class="font-medium">{{ funnel.name }}</p>
                            <span
                                class="rounded-full px-2 py-1 text-xs"
                                :class="funnel.is_active ? 'bg-emerald-500/10 text-emerald-500' : 'bg-zinc-500/10 text-zinc-500'"
                            >
                                {{ funnel.is_active ? 'Ativo' : 'Inativo' }}
                            </span>
                        </div>
                        <p v-if="funnel.description" class="mt-1 text-xs text-muted-foreground">
                            {{ funnel.description }}
                        </p>
                        <p class="mt-2 text-xs text-blue-500">
                            Conversao total: {{ totalConversionFromStages(funnel.stages) }}%
                        </p>
                        <ul class="mt-3 space-y-1 text-xs text-muted-foreground">
                            <li v-for="stage in funnel.stages" :key="stage.id">
                                {{ stage.stage_order }}. {{ stage.name }} - {{ stage.conversion_rate ?? '0' }}%
                            </li>
                        </ul>
                    </article>
                    <p v-if="props.funnels.length === 0" class="text-sm text-muted-foreground">
                        Nenhum funil salvo ainda.
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
