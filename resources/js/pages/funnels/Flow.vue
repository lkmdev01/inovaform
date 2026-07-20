<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    BookOpen,
    CircleUserRound,
    Eye,
    ListTree,
    Lock,
    Minus,
    Palette,
    Plus,
    Settings,
    Share2,
} from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref } from 'vue';
import FunnelController from '@/actions/App/Http/Controllers/FunnelController';
import { show as showPublicFunnel } from '@/routes/funnels/public';

type StageBlock = {
    id?: string;
    type?: string;
    label?: string;
};

type FunnelStage = {
    id: number;
    name: string;
    stage_order: number;
    meta: {
        builder?: {
            blocks?: StageBlock[];
        };
    } | null;
};

type Funnel = {
    id: number;
    slug: string;
    name: string;
    is_active: boolean;
    stages: FunnelStage[];
};

type StageCard = {
    id: number;
    title: string;
    blockLabels: string[];
    x: number;
    y: number;
};

const props = defineProps<{
    funnel: Funnel;
    permissions: {
        canEdit: boolean;
        canShare: boolean;
        canManageLeads: boolean;
        role: 'owner' | 'editor' | 'viewer';
    };
}>();

const CARD_WIDTH = 220;
const CARD_HEIGHT = 140;
const HORIZONTAL_GAP = 290;

const zoom = ref(1);
const flowCanvasRef = ref<HTMLElement | null>(null);
const draggingStageId = ref<number | null>(null);
const dragOffset = ref({ x: 0, y: 0 });

const orderedStages = computed(() => {
    return props.funnel.stages
        .slice()
        .sort((first, second) => first.stage_order - second.stage_order);
});

const stageCards = ref<StageCard[]>(
    orderedStages.value.map((stage, index) => {
        const blocks = stage.meta?.builder?.blocks ?? [];
        const blockLabels = blocks
            .slice(0, 4)
            .map((block) => block.label ?? block.type ?? 'Campo')
            .filter((value) => value.trim().length > 0);

        return {
            id: stage.id,
            x: index * 290,
            y: index % 2 === 0 ? 140 : 220,
            title: stage.name,
            blockLabels,
        };
    }),
);

const connectorPaths = computed(() => {
    return stageCards.value.slice(0, -1).map((stage, index) => {
        const nextStage = stageCards.value[index + 1];
        const startX = stage.x + CARD_WIDTH;
        const startY = stage.y + CARD_HEIGHT / 2;
        const endX = nextStage.x;
        const endY = nextStage.y + CARD_HEIGHT / 2;
        const curve = Math.max(50, Math.abs(endX - startX) * 0.45);

        return {
            id: `${stage.id}-${nextStage.id}`,
            d: `M ${startX} ${startY} C ${startX + curve} ${startY}, ${endX - curve} ${endY}, ${endX} ${endY}`,
        };
    });
});

function toCanvasCoordinates(event: MouseEvent): { x: number; y: number } {
    if (!flowCanvasRef.value) {
        return { x: 0, y: 0 };
    }

    const rect = flowCanvasRef.value.getBoundingClientRect();

    return {
        x: (event.clientX - rect.left) / zoom.value,
        y: (event.clientY - rect.top) / zoom.value,
    };
}

function autoOrganize(): void {
    zoom.value = 1;
    stageCards.value = stageCards.value.map((stage, index) => ({
        ...stage,
        x: index * HORIZONTAL_GAP,
        y: index % 2 === 0 ? 140 : 220,
    }));
}

function zoomIn(): void {
    zoom.value = Math.min(1.6, Number((zoom.value + 0.1).toFixed(2)));
}

function zoomOut(): void {
    zoom.value = Math.max(0.6, Number((zoom.value - 0.1).toFixed(2)));
}

function resetZoom(): void {
    zoom.value = 1;
}

function startCardDrag(event: MouseEvent, stageId: number): void {
    if (!props.permissions.canEdit) {
        return;
    }

    const stage = stageCards.value.find((card) => card.id === stageId);

    if (!stage) {
        return;
    }

    draggingStageId.value = stageId;
    const point = toCanvasCoordinates(event);
    dragOffset.value = {
        x: point.x - stage.x,
        y: point.y - stage.y,
    };

    window.addEventListener('mousemove', onCardDrag);
    window.addEventListener('mouseup', stopCardDrag);
}

function onCardDrag(event: MouseEvent): void {
    if (draggingStageId.value === null) {
        return;
    }

    const stage = stageCards.value.find(
        (card) => card.id === draggingStageId.value,
    );

    if (!stage) {
        return;
    }

    const point = toCanvasCoordinates(event);
    stage.x = Math.max(0, point.x - dragOffset.value.x);
    stage.y = Math.max(0, point.y - dragOffset.value.y);
}

function stopCardDrag(): void {
    draggingStageId.value = null;
    window.removeEventListener('mousemove', onCardDrag);
    window.removeEventListener('mouseup', stopCardDrag);
}

onBeforeUnmount(() => {
    window.removeEventListener('mousemove', onCardDrag);
    window.removeEventListener('mouseup', stopCardDrag);
});
</script>

<template>
    <Head :title="`${props.funnel.name} - Fluxo`" />

    <div class="flex h-dvh flex-col bg-[#050d22] text-[#d8e7ff]">
        <header
            class="shrink-0 border-b border-[#1e3157] bg-[#071430] px-2 py-2 sm:px-4 sm:py-3"
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
                    <div
                        v-if="props.permissions.role === 'viewer'"
                        class="rounded-full border border-[#4e6eaa] bg-[#163463] px-2.5 py-1 text-xs text-[#d4e5ff]"
                    >
                        Somente leitura
                    </div>
                </div>

                <nav
                    class="order-3 flex w-full items-center gap-1.5 overflow-x-auto rounded-lg border border-[#253f70] bg-[#081a39] p-1.5 text-sm xl:order-none xl:w-auto xl:overflow-visible"
                >
                    <Link
                        :href="FunnelController.builder(props.funnel.id).url"
                        class="shrink-0 rounded-md px-3.5 py-1.5 text-[#9ebbf0] hover:bg-[#0f274f]"
                    >
                        <span class="inline-flex items-center gap-1"
                            ><BookOpen class="size-4" /> Construtor</span
                        >
                    </Link>
                    <button
                        class="shrink-0 rounded-md bg-[#1e4e9e] px-3.5 py-1.5 font-medium text-white"
                    >
                        <span class="inline-flex items-center gap-1"
                            ><ListTree class="size-4" /> Fluxo</span
                        >
                    </button>
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
                        data-testid="leads-nav-link"
                        class="shrink-0 rounded-md px-3.5 py-1.5 text-[#9ebbf0] hover:bg-[#0f274f]"
                    >
                        <span class="inline-flex items-center gap-1"
                            ><CircleUserRound class="size-4" /> Leads</span
                        >
                    </Link>
                    <button
                        v-else
                        type="button"
                        disabled
                        data-testid="leads-nav-restricted"
                        title="Leads disponíveis apenas para proprietários e editores"
                        class="cursor-not-allowed rounded-md px-3.5 py-1.5 text-[#607da8] opacity-70"
                    >
                        <span class="inline-flex items-center gap-1"
                            ><CircleUserRound class="size-4" /> Leads</span
                        >
                        <span class="sr-only"
                            >Acesso disponível apenas para proprietários e
                            editores</span
                        >
                    </button>
                </nav>

                <div class="flex shrink-0 items-center gap-1.5">
                    <Link
                        :href="FunnelController.settings(props.funnel.id).url"
                        aria-label="Configurações do funil"
                        title="Configurações do funil"
                        data-testid="funnel-settings-button"
                        class="rounded-md border border-[#2a487c] bg-[#081b39] p-1.5 text-[#b6cdff]"
                    >
                        <Settings class="size-4" />
                    </Link>
                    <button
                        :disabled="!props.permissions.canShare"
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
                        :disabled="!props.permissions.canEdit"
                        class="rounded-md border border-[#3860a7] bg-[#0a2c61] px-3 py-1.5 text-sm font-medium text-white disabled:opacity-50 sm:px-4"
                    >
                        Salvar
                    </button>
                    <button
                        :disabled="!props.permissions.canEdit"
                        class="rounded-md bg-gradient-to-r from-[#1d5fd2] to-[#3f8dff] px-3 py-1.5 text-sm font-semibold text-white disabled:opacity-50 sm:px-4"
                    >
                        Publicar
                    </button>
                </div>
            </div>
        </header>

        <main
            class="relative min-h-0 flex-1 overflow-hidden bg-[radial-gradient(circle_at_30%_10%,#123267_0%,#0a1a3d_35%,#07122b_100%)]"
        >
            <div
                class="absolute inset-0 [background-image:radial-gradient(#88a8df_0.7px,transparent_0.7px)] [background-size:16px_16px] opacity-25"
            />

            <div class="absolute top-5 left-5 z-10 flex items-center gap-2">
                <button
                    @click="autoOrganize"
                    class="rounded-lg border border-[#2b4f8d] bg-[#0b2248] px-4 py-2 text-sm text-[#d6e6ff]"
                >
                    Auto-organizar
                </button>
                <div
                    class="rounded-lg border border-[#2b4f8d] bg-[#0b2248] px-3 py-2 text-xs text-[#9ec0fa]"
                >
                    Zoom {{ Math.round(zoom * 100) }}%
                </div>
            </div>

            <div
                class="absolute bottom-5 left-5 z-10 flex flex-col gap-1 rounded-lg border border-[#2b4f8d] bg-[#0b2248] p-1"
            >
                <button
                    @click="zoomIn"
                    class="rounded-md border border-[#2b4f8d] bg-[#102f62] p-1.5 text-[#dbe9ff]"
                >
                    <Plus class="size-4" />
                </button>
                <button
                    @click="zoomOut"
                    class="rounded-md border border-[#2b4f8d] bg-[#102f62] p-1.5 text-[#dbe9ff]"
                >
                    <Minus class="size-4" />
                </button>
                <button
                    @click="resetZoom"
                    class="rounded-md border border-[#2b4f8d] bg-[#102f62] p-1.5 text-[#dbe9ff]"
                >
                    <Lock class="size-4" />
                </button>
            </div>

            <div class="relative h-full w-full overflow-auto px-16 pt-16">
                <div
                    ref="flowCanvasRef"
                    class="relative min-h-[560px] min-w-[1200px]"
                    :style="{
                        transform: `scale(${zoom})`,
                        transformOrigin: 'top left',
                    }"
                >
                    <svg
                        class="pointer-events-none absolute inset-0 h-full w-full overflow-visible"
                    >
                        <path
                            v-for="path in connectorPaths"
                            :key="path.id"
                            :d="path.d"
                            fill="none"
                            stroke="url(#flowGradient)"
                            stroke-linecap="round"
                            stroke-width="3"
                        />
                        <defs>
                            <linearGradient
                                id="flowGradient"
                                x1="0%"
                                y1="0%"
                                x2="100%"
                                y2="0%"
                            >
                                <stop offset="0%" stop-color="#4f8fff" />
                                <stop offset="100%" stop-color="#68c1ff" />
                            </linearGradient>
                        </defs>
                    </svg>

                    <template
                        v-for="(stage, index) in stageCards"
                        :key="stage.id"
                    >
                        <div
                            class="absolute w-[220px] rounded-xl border border-[#2f568f] bg-gradient-to-b from-[#0f2e61] to-[#0a2148] p-3 shadow-[0_12px_28px_rgba(0,0,0,0.35)]"
                            :class="[
                                props.permissions.canEdit
                                    ? 'cursor-grab active:cursor-grabbing'
                                    : '',
                                draggingStageId === stage.id
                                    ? 'ring-2 ring-[#5ea2ff]'
                                    : '',
                            ]"
                            :style="{
                                left: `${stage.x}px`,
                                top: `${stage.y}px`,
                            }"
                            @mousedown.left.prevent="
                                startCardDrag($event, stage.id)
                            "
                        >
                            <p class="text-xs text-[#8db3f2]">
                                Etapa {{ index + 1 }}
                            </p>
                            <p class="mt-1 text-sm font-semibold text-white">
                                {{ stage.title }}
                            </p>
                            <div class="mt-2 space-y-1.5">
                                <div
                                    v-for="(
                                        label, labelIndex
                                    ) in stage.blockLabels"
                                    :key="`${stage.id}-${labelIndex}`"
                                    class="rounded-md border border-[#325b95] bg-[#0a2753] px-2 py-1 text-[11px] text-[#cfe1ff]"
                                >
                                    {{ label }}
                                </div>
                                <p
                                    v-if="stage.blockLabels.length === 0"
                                    class="text-[11px] text-[#8db3f2]"
                                >
                                    Sem blocos
                                </p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </main>
    </div>
</template>
