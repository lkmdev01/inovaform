<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    ChevronDown,
    CircleUserRound,
    ListTree,
    Palette,
    Play,
    Settings,
    Share2,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import FunnelController from '@/actions/App/Http/Controllers/FunnelController';
import profile from '@/routes/profile';

type StageBlock = {
    id?: string;
    type?: string;
    label?: string;
    options?: string[];
    placeholder?: string | null;
    option_items?: Array<{
        id?: string;
        label?: string;
        description?: string;
        image_url?: string;
    }>;
    required?: boolean;
    button_color_style?: string;
    button_animated?: boolean;
    button_elevated?: boolean;
};

type FunnelStage = {
    id: number;
    name: string;
    stage_order: number;
    meta: {
        header?: {
            show_logo?: boolean;
            show_progress?: boolean;
            allow_back?: boolean;
        };
        builder?: {
            title?: string;
            subtitle?: string;
            button_text?: string;
            blocks?: StageBlock[];
        };
    } | null;
};

type Funnel = {
    id: number;
    slug: string;
    name: string;
    is_active: boolean;
    custom_domain?: string | null;
    stages: FunnelStage[];
};

type DesignTokens = {
    colors: { primary: string; onPrimary: string; heading: string; text: string; textMuted: string };
    typography: { family: 'modern' | 'clean' | 'serif' };
    brand: { logoUrl: string; showLogo: boolean };
    surfaces: { page: string; card: string; muted: string };
    borders: { default: string; strong: string; focus: string };
    states: { success: string; warning: string; danger: string; disabledOpacity: number };
    components: { fieldBackground: string; fieldText: string; primaryButtonBackground: string; primaryButtonText: string };
};

type DesignSettings = {
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
    logoUrl?: string;
    faviconUrl?: string;
    seoTitle?: string;
    seoDescription?: string;
    seoImageUrl?: string;
    unavailableTitle?: string;
    unavailableDescription?: string;
    expiresAt?: string | null;
    tokens: DesignTokens;
};

const props = defineProps<{
    funnel: Funnel;
    designSettings: DesignSettings;
    permissions: {
        canEdit: boolean;
        canShare: boolean;
        canManageLeads: boolean;
        role: 'owner' | 'editor' | 'viewer';
    };
}>();

function normalizeDateTimeLocalValue(value: string | null | undefined): string | null {
    const trimmed = (value ?? '').trim();

    if (trimmed === '') {
        return null;
    }

    return trimmed.replace('Z', '').slice(0, 16);
}

const page = usePage<{ flash?: { status?: string } }>();

const defaultDesignSettings: DesignSettings = {
    alignment: 'center',
    width: 'small',
    elementSize: 'default',
    spacing: 'default',
    radius: 'medium',
    showLogo: true,
    showProgress: true,
    allowBack: true,
    accentColor: '#3d8bff',
    pageColor: '#050d22',
    cardColor: '#0b1a3a',
    headingColor: '#f8fbff',
    textColor: '#a8bfeb',
    buttonColor: '#12356f',
    buttonTextColor: '#e8f2ff',
    fontStyle: 'modern',
    logoUrl: '',
    faviconUrl: '',
    seoTitle: '',
    seoDescription: '',
    seoImageUrl: '',
    unavailableTitle: 'Funil indisponivel',
    unavailableDescription: 'Este funil nao esta disponivel no momento.',
    expiresAt: null,
    tokens: {
        colors: { primary: '#3d8bff', onPrimary: '#e8f2ff', heading: '#f8fbff', text: '#a8bfeb', textMuted: '#7894c5' },
        typography: { family: 'modern' },
        brand: { logoUrl: '', showLogo: true },
        surfaces: { page: '#050d22', card: '#0b1a3a', muted: '#102348' },
        borders: { default: '#2f538f', strong: '#3d8bff', focus: '#3d8bff' },
        states: { success: '#22c55e', warning: '#f59e0b', danger: '#f43f5e', disabledOpacity: 0.55 },
        components: { fieldBackground: '#0b274f', fieldText: '#f8fbff', primaryButtonBackground: 'linear-gradient(135deg, #2563eb, #06b6d4)', primaryButtonText: '#e8f2ff' },
    },
};

const settings = ref<DesignSettings>({
    ...defaultDesignSettings,
    ...props.designSettings,
    expiresAt: normalizeDateTimeLocalValue(props.designSettings.expiresAt ?? null),
});
const publishedState = ref(props.funnel.is_active);

const openPanel = ref<'general' | 'header' | 'colors' | 'typography' | 'publication'>('general');

const orderedStages = computed(() => {
    return props.funnel.stages.slice().sort((first, second) => first.stage_order - second.stage_order);
});

const previewStages = computed(() => {
    if (orderedStages.value.length > 0) {
        return orderedStages.value;
    }

    return [
        {
            id: 0,
            name: 'Etapa 1',
            stage_order: 1,
            meta: null,
        },
    ] satisfies FunnelStage[];
});

const previewWrapperClass = computed(() => {
    return settings.value.width === 'small'
        ? 'max-w-[480px]'
        : settings.value.width === 'medium'
          ? 'max-w-[620px]'
          : 'max-w-[760px]';
});

const previewCardClass = computed(() => {
    return settings.value.width === 'small'
        ? 'w-[360px]'
        : settings.value.width === 'medium'
          ? 'w-[420px]'
          : 'w-[500px]';
});

const previewCardRadius = computed(() => {
    return settings.value.radius === 'small' ? '20px' : settings.value.radius === 'medium' ? '28px' : '36px';
});

const controlRadius = computed(() => {
    return settings.value.radius === 'small' ? '12px' : settings.value.radius === 'medium' ? '16px' : '22px';
});

const actionsAlignClass = computed(() => {
    return settings.value.alignment === 'left' ? 'items-start' : 'items-center';
});

const optionClass = computed(() => {
    if (settings.value.elementSize === 'compact') {
        return 'px-3 py-2 text-sm';
    }

    if (settings.value.elementSize === 'large') {
        return 'px-5 py-4 text-lg';
    }

    return 'px-4 py-3 text-base';
});

const contentGapClass = computed(() => {
    if (settings.value.spacing === 'compact') {
        return 'space-y-4';
    }

    if (settings.value.spacing === 'large') {
        return 'space-y-8';
    }

    return 'space-y-6';
});

const buttonClass = computed(() => {
    if (settings.value.elementSize === 'compact') {
        return 'py-2.5 text-xs';
    }

    if (settings.value.elementSize === 'large') {
        return 'py-4 text-base';
    }

    return 'py-3 text-sm';
});

const fontClass = computed(() => {
    if (settings.value.fontStyle === 'clean') {
        return 'font-sans tracking-tight';
    }

    if (settings.value.fontStyle === 'serif') {
        return 'font-serif';
    }

    return 'font-["Sora"] tracking-tight';
});

const previewThemeStyle = computed(() => ({
    '--funnel-primary': settings.value.tokens.colors.primary,
    '--funnel-on-primary': settings.value.tokens.colors.onPrimary,
    '--funnel-heading': settings.value.tokens.colors.heading,
    '--funnel-text': settings.value.tokens.colors.text,
    '--funnel-text-muted': settings.value.tokens.colors.textMuted,
    '--funnel-page': settings.value.tokens.surfaces.page,
    '--funnel-surface': settings.value.tokens.surfaces.card,
    '--funnel-surface-muted': settings.value.tokens.surfaces.muted,
    '--funnel-border': settings.value.tokens.borders.default,
    '--funnel-border-strong': settings.value.tokens.borders.strong,
    '--funnel-focus': settings.value.tokens.borders.focus,
    '--funnel-success': settings.value.tokens.states.success,
    '--funnel-warning': settings.value.tokens.states.warning,
    '--funnel-danger': settings.value.tokens.states.danger,
    '--funnel-field-bg': settings.value.tokens.components.fieldBackground,
    '--funnel-field-text': settings.value.tokens.components.fieldText,
    '--funnel-button-bg': settings.value.tokens.components.primaryButtonBackground,
    '--funnel-button-text': settings.value.tokens.components.primaryButtonText,
    backgroundColor: settings.value.tokens.surfaces.page,
}));

const panelSectionClass = 'border-b border-[#1f3258] bg-[#071433]/80 p-5';
const fieldClass =
    'w-full rounded-xl border border-[#2d4f89] bg-[#0a1e45] px-4 py-3 text-[#dceaff] outline-none transition placeholder:text-[#6f8fca] focus:border-[#4f8fff]';
const saveForm = useForm({
    custom_domain: props.funnel.custom_domain ?? '',
    design_settings: settings.value as DesignSettings,
    is_active: props.funnel.is_active,
});

const flashStatus = computed(() => page.props.flash?.status ?? '');

function hexToRgba(hex: string, alpha: number): string {
    const clean = hex.replace('#', '');

    if (clean.length !== 6) {
        return `rgba(61, 139, 255, ${alpha})`;
    }

    const r = Number.parseInt(clean.slice(0, 2), 16);
    const g = Number.parseInt(clean.slice(2, 4), 16);
    const b = Number.parseInt(clean.slice(4, 6), 16);

    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

function isOptionsComponentType(type?: string): boolean {
    return ['options', 'multiple_choice', 'single_choice', 'yes_no'].includes(type ?? '');
}

function stagePreviewBlocks(stage: FunnelStage): StageBlock[] {
    const builder = stage.meta?.builder ?? {};
    const mergedBlocks = [...(builder.blocks ?? [])];
    const title = (builder.title ?? '').trim();
    const subtitle = (builder.subtitle ?? '').trim();
    const buttonText = (builder.button_text ?? '').trim();

    if (title !== '' || subtitle !== '') {
        const markup: string[] = [];

        if (title !== '') {
            markup.push(`<h1>${title}</h1>`);
        }

        if (subtitle !== '') {
            markup.push(`<p>${subtitle}</p>`);
        }

        mergedBlocks.unshift({
            id: `legacy-copy-${stage.id}`,
            type: 'content_text',
            label: '',
            placeholder: markup.join(''),
            required: false,
        });
    }

    if (buttonText !== '') {
        mergedBlocks.push({
            id: `legacy-button-${stage.id}`,
            type: 'button',
            label: buttonText,
            required: false,
            button_color_style: 'theme',
            button_animated: false,
            button_elevated: false,
        });
    }

    return mergedBlocks.slice(0, 6);
}

function stageInputPlaceholder(block: StageBlock): string {
    if ((block.placeholder ?? '').trim() !== '') {
        return block.placeholder ?? '';
    }

    if (block.type === 'email') {
        return 'Digite seu e-mail...';
    }

    if (block.type === 'phone') {
        return 'Digite seu telefone...';
    }

    if (block.type === 'textarea') {
        return 'Digite aqui...';
    }

    return 'Digite aqui...';
}

function stageOptionItems(block: StageBlock): Array<{ id: string; label: string }> {
    if (Array.isArray(block.option_items) && block.option_items.length > 0) {
        return block.option_items.map((item, index) => ({
            id: item.id ?? `${block.id ?? 'block'}-${index}`,
            label: item.label?.trim() || `Opcao ${index + 1}`,
        }));
    }

    if (Array.isArray(block.options) && block.options.length > 0) {
        return block.options.map((option, index) => ({
            id: `${block.id ?? 'block'}-${index}`,
            label: option.trim() || `Opcao ${index + 1}`,
        }));
    }

    if (block.type === 'yes_no') {
        return [
            { id: `${block.id ?? 'block'}-yes`, label: '✅ Sim' },
            { id: `${block.id ?? 'block'}-no`, label: '🚫 Nao' },
        ];
    }

    return [];
}

function stageBlockMarkup(block: StageBlock): string {
    return (block.placeholder ?? '').trim();
}

function stageButtonClass(block: StageBlock): string {
    if (block.button_color_style === 'dark') {
        return 'bg-[#12356f] text-white';
    }

    return 'bg-linear-to-r from-[#1f60d4] to-[#4d87ff] text-white';
}

function stageProgress(index: number): number {
    const total = Math.max(previewStages.value.length, 1);

    return Math.round(((index + 1) / total) * 100);
}

function saveDesign(isPublishing = false): void {
    if (!props.permissions.canEdit) {
        return;
    }

    settings.value.tokens.colors.primary = settings.value.accentColor;
    settings.value.tokens.colors.onPrimary = settings.value.buttonTextColor;
    settings.value.tokens.colors.heading = settings.value.headingColor;
    settings.value.tokens.colors.text = settings.value.textColor;
    settings.value.tokens.typography.family = settings.value.fontStyle;
    settings.value.tokens.brand.logoUrl = settings.value.logoUrl ?? '';
    settings.value.tokens.brand.showLogo = settings.value.showLogo;
    settings.value.tokens.surfaces.page = settings.value.pageColor;
    settings.value.tokens.surfaces.card = settings.value.cardColor;
    settings.value.tokens.components.primaryButtonBackground = settings.value.buttonColor;
    settings.value.tokens.components.primaryButtonText = settings.value.buttonTextColor;

    saveForm.custom_domain = (saveForm.custom_domain ?? '').trim().toLowerCase();
    saveForm.design_settings = { ...settings.value };
    saveForm.is_active = isPublishing ? true : publishedState.value;

    saveForm.submit(FunnelController.updateDesign(props.funnel.id), {
        preserveScroll: true,
        preserveState: true,
        replace: true,
        onSuccess: () => {
            if (isPublishing) {
                publishedState.value = true;
            }
        },
    });
}

const previewLogoUrl = computed(() => {
    const value = (settings.value.logoUrl ?? '').trim();

    return value !== '' ? value : '';
});
</script>

<template>
    <Head :title="`${props.funnel.name} - Design`" />

    <div class="h-screen overflow-hidden bg-[#050d22] text-[#d8e7ff]">
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
                    <button class="rounded-md bg-[#1e4e9e] px-3.5 py-1.5 font-medium text-white">
                        <span class="inline-flex items-center gap-1"><Palette class="size-4" /> Design</span>
                    </button>
                    <Link :href="FunnelController.leads(props.funnel.id).url" class="rounded-md px-3.5 py-1.5 text-[#9ebbf0] hover:bg-[#0f274f]">
                        <span class="inline-flex items-center gap-1"><CircleUserRound class="size-4" /> Leads</span>
                    </Link>
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
                        @click="saveDesign(false)"
                    >
                        Salvar
                    </button>
                    <button
                        :disabled="!props.permissions.canEdit || saveForm.processing"
                        class="rounded-md bg-linear-to-r from-[#1d5fd2] to-[#3f8dff] px-4 py-1.5 text-sm font-semibold text-white disabled:opacity-50"
                        @click="saveDesign(true)"
                    >
                        Publicar
                    </button>
                    <span v-if="saveForm.processing" class="text-xs text-[#9ebbf0]">Salvando...</span>
                    <span v-else-if="flashStatus === 'design-saved'" class="text-xs text-emerald-300">Design salvo</span>
                    <span v-else-if="flashStatus === 'funnel-published'" class="text-xs text-emerald-300">Funil publicado</span>
                </div>
            </div>
        </header>

        <main class="grid h-[calc(100vh-69px)] grid-cols-[1fr_470px] overflow-hidden">
            <section class="overflow-y-auto border-r border-[#1f3258] bg-linear-to-b from-[#06112a] via-[#071530] to-[#050d20]">
                <div
                    class="mx-auto flex min-h-full w-full flex-col gap-12 px-6 py-12"
                    :class="[previewWrapperClass, settings.alignment === 'center' ? 'items-center' : 'items-start']"
                    :style="previewThemeStyle"
                >
                    <article
                        v-for="(stage, stageIndex) in previewStages"
                        :key="stage.id"
                        class="w-full border shadow-[0_24px_55px_rgba(0,0,0,0.42)]"
                        :class="[previewCardClass, fontClass]"
                        :style="{
                            borderRadius: previewCardRadius,
                            borderColor: settings.tokens.borders.default,
                            backgroundColor: settings.tokens.surfaces.card,
                        }"
                    >
                        <div class="px-4 py-4">
                            <div class="mb-3 flex items-center justify-between text-xs text-[#95b4ea]">
                        <div v-if="settings.showLogo" class="flex items-center gap-2">
                            <img v-if="previewLogoUrl" :src="previewLogoUrl" alt="Logo do funil" class="h-7 w-auto max-w-28 object-contain" />
                            <template v-else>
                                <span class="inline-flex h-6 w-6 items-center justify-center rounded-md border border-[#2f4f8c] bg-[#081b3c] text-[11px] font-semibold text-white">IN</span>
                                <span>Inovaform</span>
                            </template>
                        </div>
                                <span
                                    class="rounded-full border px-2 py-1"
                                    :style="{
                                        borderColor: hexToRgba(settings.accentColor, 0.45),
                                        backgroundColor: hexToRgba(settings.accentColor, 0.18),
                                        borderRadius: controlRadius,
                                    }"
                                >
                                    Etapa {{ stageIndex + 1 }}
                                </span>
                            </div>
                            <div v-if="settings.allowBack" class="mb-3 text-sm text-[#8fb1eb]">&#8592; Voltar</div>
                            <div v-if="settings.showProgress" class="h-2 overflow-hidden rounded-full bg-[#16366f]">
                                <div class="h-full rounded-full transition-all" :style="{ width: `${stageProgress(stageIndex)}%`, backgroundColor: settings.accentColor }" />
                            </div>
                        </div>

                        <div class="px-6 pb-7 pt-4" :class="contentGapClass">
                            <div class="space-y-3" :class="actionsAlignClass">
                                <template v-for="block in stagePreviewBlocks(stage)" :key="block.id ?? `${stage.id}-${block.type}`">
                                    <div
                                        v-if="['text', 'email', 'phone', 'number', 'date', 'height', 'weight', 'address'].includes(block.type ?? '')"
                                        class="w-full"
                                    >
                                        <p v-if="block.label" class="mb-1.5 text-sm" :style="{ color: settings.textColor }">{{ block.label }}</p>
                                        <div
                                            class="w-full border px-4 text-left transition"
                                            :class="optionClass"
                                            :style="{
                                                color: settings.textColor,
                                                backgroundColor: hexToRgba(settings.accentColor, 0.08),
                                                borderColor: hexToRgba(settings.accentColor, 0.28),
                                                borderRadius: controlRadius,
                                            }"
                                        >
                                            {{ stageInputPlaceholder(block) }}
                                        </div>
                                    </div>

                                    <div
                                        v-else-if="block.type === 'textarea'"
                                        class="w-full"
                                    >
                                        <p v-if="block.label" class="mb-1.5 text-sm" :style="{ color: settings.textColor }">{{ block.label }}</p>
                                        <div
                                            class="w-full border px-4 text-left transition"
                                            :class="optionClass"
                                            :style="{
                                                minHeight: settings.elementSize === 'compact' ? '88px' : settings.elementSize === 'large' ? '130px' : '108px',
                                                color: settings.textColor,
                                                backgroundColor: hexToRgba(settings.accentColor, 0.08),
                                                borderColor: hexToRgba(settings.accentColor, 0.28),
                                                borderRadius: controlRadius,
                                            }"
                                        >
                                            {{ stageInputPlaceholder(block) }}
                                        </div>
                                    </div>

                                    <div v-else-if="isOptionsComponentType(block.type)" class="w-full space-y-3">
                                        <div
                                            v-for="item in stageOptionItems(block)"
                                            :key="item.id"
                                            class="w-full border text-left transition"
                                            :class="optionClass"
                                            :style="{
                                                color: settings.headingColor,
                                                backgroundColor: hexToRgba(settings.accentColor, 0.1),
                                                borderColor: hexToRgba(settings.accentColor, 0.35),
                                                borderRadius: controlRadius,
                                            }"
                                        >
                                            {{ item.label }}
                                        </div>
                                    </div>

                                    <div
                                        v-else-if="block.type === 'content_text' && stageBlockMarkup(block) !== ''"
                                        class="w-full text-left [&_h1]:text-3xl [&_h1]:font-bold [&_h2]:text-2xl [&_h2]:font-bold [&_h3]:text-xl [&_h3]:font-semibold [&_p]:mt-2 [&_p]:leading-relaxed"
                                        :style="{ color: settings.textColor }"
                                        v-html="stageBlockMarkup(block)"
                                    />

                                    <div
                                        v-else-if="block.type === 'image' || block.type === 'video' || block.type === 'audio'"
                                        class="flex w-full items-center justify-center border text-sm"
                                        :class="optionClass"
                                        :style="{
                                            minHeight: settings.elementSize === 'compact' ? '120px' : settings.elementSize === 'large' ? '200px' : '160px',
                                            color: settings.textColor,
                                            backgroundColor: hexToRgba(settings.accentColor, 0.08),
                                            borderColor: hexToRgba(settings.accentColor, 0.28),
                                            borderRadius: controlRadius,
                                        }"
                                    >
                                        {{ block.type === 'image' ? 'Preview de imagem' : block.type === 'video' ? 'Preview de video' : 'Preview de audio' }}
                                    </div>

                                    <div
                                        v-else-if="['attention', 'alert', 'notification', 'timer', 'loading', 'level', 'price'].includes(block.type ?? '')"
                                        class="w-full border text-left transition"
                                        :class="optionClass"
                                        :style="{
                                            color: settings.headingColor,
                                            backgroundColor: hexToRgba(settings.accentColor, 0.1),
                                            borderColor: hexToRgba(settings.accentColor, 0.35),
                                            borderRadius: controlRadius,
                                        }"
                                    >
                                        {{ block.label || stageInputPlaceholder(block) }}
                                    </div>

                                    <button
                                        v-else-if="block.type === 'button'"
                                        class="w-full px-4 font-semibold transition"
                                        :class="[buttonClass, stageButtonClass(block), block.button_animated ? 'animate-pulse' : '', block.button_elevated ? 'shadow-[0_20px_35px_rgba(23,74,178,0.32)]' : '']"
                                        :style="{ borderRadius: controlRadius }"
                                    >
                                        {{ block.label || 'Continuar' }}
                                    </button>
                                </template>
                            </div>
                        </div>
                    </article>
                </div>
            </section>

            <aside class="h-full overflow-hidden bg-[#06122e]">
                <section :class="panelSectionClass">
                    <button class="flex w-full items-center justify-between text-left text-base font-semibold text-[#e3eeff]" @click="openPanel = 'general'">
                        <span>GERAL</span>
                        <ChevronDown class="size-4 transition" :class="openPanel === 'general' ? 'rotate-180' : ''" />
                    </button>
                    <div v-show="openPanel === 'general'" class="mt-4 space-y-4">
                        <div>
                            <label class="mb-1 block text-sm text-[#88a8df]">Alinhamento</label>
                            <select v-model="settings.alignment" :disabled="!props.permissions.canEdit" :class="fieldClass">
                                <option value="center">Centralizado</option>
                                <option value="left">Esquerda</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm text-[#88a8df]">Largura principal</label>
                            <select v-model="settings.width" :disabled="!props.permissions.canEdit" :class="fieldClass">
                                <option value="small">Pequeno</option>
                                <option value="medium">Medio</option>
                                <option value="large">Grande</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm text-[#88a8df]">Tamanho dos elementos</label>
                            <select v-model="settings.elementSize" :disabled="!props.permissions.canEdit" :class="fieldClass">
                                <option value="compact">Compacto</option>
                                <option value="default">Padrao</option>
                                <option value="large">Grande</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm text-[#88a8df]">Espacamento</label>
                            <select v-model="settings.spacing" :disabled="!props.permissions.canEdit" :class="fieldClass">
                                <option value="compact">Compacto</option>
                                <option value="default">Padrao</option>
                                <option value="large">Amplo</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm text-[#88a8df]">Bordas/Cantos</label>
                            <select v-model="settings.radius" :disabled="!props.permissions.canEdit" :class="fieldClass">
                                <option value="small">Suave</option>
                                <option value="medium">Medio</option>
                                <option value="large">Arredondado</option>
                            </select>
                        </div>
                    </div>
                </section>

                <section :class="panelSectionClass">
                    <button class="flex w-full items-center justify-between text-left text-base font-semibold text-[#e3eeff]" @click="openPanel = openPanel === 'header' ? 'general' : 'header'">
                        <span>HEADER</span>
                        <ChevronDown class="size-4 transition" :class="openPanel === 'header' ? 'rotate-180' : ''" />
                    </button>
                    <div v-show="openPanel === 'header'" class="mt-4 space-y-3">
                        <label class="flex items-center justify-between rounded-xl border border-[#244579] bg-[#0a1e45] px-4 py-3 text-sm text-[#d7e7ff]">
                            Mostrar logo
                            <input v-model="settings.showLogo" :disabled="!props.permissions.canEdit" type="checkbox" class="h-4 w-4 accent-[#3d8bff]" />
                        </label>
                        <label class="flex items-center justify-between rounded-xl border border-[#244579] bg-[#0a1e45] px-4 py-3 text-sm text-[#d7e7ff]">
                            Mostrar progresso
                            <input v-model="settings.showProgress" :disabled="!props.permissions.canEdit" type="checkbox" class="h-4 w-4 accent-[#3d8bff]" />
                        </label>
                        <label class="flex items-center justify-between rounded-xl border border-[#244579] bg-[#0a1e45] px-4 py-3 text-sm text-[#d7e7ff]">
                            Permitir voltar
                            <input v-model="settings.allowBack" :disabled="!props.permissions.canEdit" type="checkbox" class="h-4 w-4 accent-[#3d8bff]" />
                        </label>
                    </div>
                </section>

                <section :class="panelSectionClass">
                    <button class="flex w-full items-center justify-between text-left text-base font-semibold text-[#e3eeff]" @click="openPanel = openPanel === 'publication' ? 'general' : 'publication'">
                        <span>PUBLICACAO</span>
                        <ChevronDown class="size-4 transition" :class="openPanel === 'publication' ? 'rotate-180' : ''" />
                    </button>
                    <div v-show="openPanel === 'publication'" class="mt-4 space-y-4">
                        <div>
                            <label class="mb-1 block text-sm text-[#88a8df]">Dominio personalizado</label>
                            <input v-model="saveForm.custom_domain" :disabled="!props.permissions.canEdit" :class="fieldClass" placeholder="quiz.seudominio.com" />
                            <p class="mt-1 text-xs text-[#6f8fca]">Aponte o dominio para a aplicacao e publique o funil.</p>
                            <p v-if="saveForm.errors.custom_domain" class="mt-1 text-xs text-rose-300">{{ saveForm.errors.custom_domain }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm text-[#88a8df]">Logo do funil</label>
                            <input v-model="settings.logoUrl" :disabled="!props.permissions.canEdit" :class="fieldClass" placeholder="https://..." />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm text-[#88a8df]">Favicon do funil</label>
                            <input v-model="settings.faviconUrl" :disabled="!props.permissions.canEdit" :class="fieldClass" placeholder="https://..." />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm text-[#88a8df]">SEO title</label>
                            <input v-model="settings.seoTitle" :disabled="!props.permissions.canEdit" :class="fieldClass" placeholder="Titulo para mecanismos de busca" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm text-[#88a8df]">SEO description</label>
                            <textarea v-model="settings.seoDescription" :disabled="!props.permissions.canEdit" :class="fieldClass" rows="3" placeholder="Descricao curta para compartilhamento e busca" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm text-[#88a8df]">SEO image</label>
                            <input v-model="settings.seoImageUrl" :disabled="!props.permissions.canEdit" :class="fieldClass" placeholder="https://..." />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm text-[#88a8df]">Expira em</label>
                            <input v-model="settings.expiresAt" :disabled="!props.permissions.canEdit" :class="fieldClass" type="datetime-local" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm text-[#88a8df]">Titulo da pagina indisponivel</label>
                            <input v-model="settings.unavailableTitle" :disabled="!props.permissions.canEdit" :class="fieldClass" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm text-[#88a8df]">Descricao da pagina indisponivel</label>
                            <textarea v-model="settings.unavailableDescription" :disabled="!props.permissions.canEdit" :class="fieldClass" rows="3" />
                        </div>
                    </div>
                </section>

                <section :class="panelSectionClass">
                    <button class="flex w-full items-center justify-between text-left text-base font-semibold text-[#e3eeff]" @click="openPanel = openPanel === 'colors' ? 'general' : 'colors'">
                        <span>CORES</span>
                        <ChevronDown class="size-4 transition" :class="openPanel === 'colors' ? 'rotate-180' : ''" />
                    </button>
                    <div v-show="openPanel === 'colors'" class="mt-4 grid grid-cols-2 gap-3">
                        <label class="rounded-xl border border-[#244579] bg-[#0a1e45] p-3 text-xs text-[#88a8df]">
                            Destaque
                            <input v-model="settings.accentColor" :disabled="!props.permissions.canEdit" type="color" class="mt-2 h-9 w-full rounded border-0 bg-transparent p-0" />
                        </label>
                        <label class="rounded-xl border border-[#244579] bg-[#0a1e45] p-3 text-xs text-[#88a8df]">
                            Fundo
                            <input v-model="settings.pageColor" :disabled="!props.permissions.canEdit" type="color" class="mt-2 h-9 w-full rounded border-0 bg-transparent p-0" />
                        </label>
                        <label class="rounded-xl border border-[#244579] bg-[#0a1e45] p-3 text-xs text-[#88a8df]">
                            Card
                            <input v-model="settings.cardColor" :disabled="!props.permissions.canEdit" type="color" class="mt-2 h-9 w-full rounded border-0 bg-transparent p-0" />
                        </label>
                        <label class="rounded-xl border border-[#244579] bg-[#0a1e45] p-3 text-xs text-[#88a8df]">
                            Titulo
                            <input v-model="settings.headingColor" :disabled="!props.permissions.canEdit" type="color" class="mt-2 h-9 w-full rounded border-0 bg-transparent p-0" />
                        </label>
                        <label class="rounded-xl border border-[#244579] bg-[#0a1e45] p-3 text-xs text-[#88a8df]">
                            Texto
                            <input v-model="settings.textColor" :disabled="!props.permissions.canEdit" type="color" class="mt-2 h-9 w-full rounded border-0 bg-transparent p-0" />
                        </label>
                        <label class="rounded-xl border border-[#244579] bg-[#0a1e45] p-3 text-xs text-[#88a8df]">
                            Botao
                            <input v-model="settings.buttonColor" :disabled="!props.permissions.canEdit" type="color" class="mt-2 h-9 w-full rounded border-0 bg-transparent p-0" />
                        </label>
                        <label class="rounded-xl border border-[#244579] bg-[#0a1e45] p-3 text-xs text-[#88a8df]">
                            Texto botao
                            <input v-model="settings.buttonTextColor" :disabled="!props.permissions.canEdit" type="color" class="mt-2 h-9 w-full rounded border-0 bg-transparent p-0" />
                        </label>
                        <label class="rounded-xl border border-[#244579] bg-[#0a1e45] p-3 text-xs text-[#88a8df]">
                            Superficie suave
                            <input v-model="settings.tokens.surfaces.muted" :disabled="!props.permissions.canEdit" type="color" class="mt-2 h-9 w-full rounded border-0 bg-transparent p-0" />
                        </label>
                        <label class="rounded-xl border border-[#244579] bg-[#0a1e45] p-3 text-xs text-[#88a8df]">
                            Texto suave
                            <input v-model="settings.tokens.colors.textMuted" :disabled="!props.permissions.canEdit" type="color" class="mt-2 h-9 w-full rounded border-0 bg-transparent p-0" />
                        </label>
                        <label class="rounded-xl border border-[#244579] bg-[#0a1e45] p-3 text-xs text-[#88a8df]">
                            Borda
                            <input v-model="settings.tokens.borders.default" :disabled="!props.permissions.canEdit" type="color" class="mt-2 h-9 w-full rounded border-0 bg-transparent p-0" />
                        </label>
                        <label class="rounded-xl border border-[#244579] bg-[#0a1e45] p-3 text-xs text-[#88a8df]">
                            Foco
                            <input v-model="settings.tokens.borders.focus" :disabled="!props.permissions.canEdit" type="color" class="mt-2 h-9 w-full rounded border-0 bg-transparent p-0" />
                        </label>
                        <label class="rounded-xl border border-[#244579] bg-[#0a1e45] p-3 text-xs text-[#88a8df]">
                            Sucesso
                            <input v-model="settings.tokens.states.success" :disabled="!props.permissions.canEdit" type="color" class="mt-2 h-9 w-full rounded border-0 bg-transparent p-0" />
                        </label>
                        <label class="rounded-xl border border-[#244579] bg-[#0a1e45] p-3 text-xs text-[#88a8df]">
                            Alerta
                            <input v-model="settings.tokens.states.warning" :disabled="!props.permissions.canEdit" type="color" class="mt-2 h-9 w-full rounded border-0 bg-transparent p-0" />
                        </label>
                        <label class="rounded-xl border border-[#244579] bg-[#0a1e45] p-3 text-xs text-[#88a8df]">
                            Erro
                            <input v-model="settings.tokens.states.danger" :disabled="!props.permissions.canEdit" type="color" class="mt-2 h-9 w-full rounded border-0 bg-transparent p-0" />
                        </label>
                        <label class="rounded-xl border border-[#244579] bg-[#0a1e45] p-3 text-xs text-[#88a8df]">
                            Campo
                            <input v-model="settings.tokens.components.fieldBackground" :disabled="!props.permissions.canEdit" type="color" class="mt-2 h-9 w-full rounded border-0 bg-transparent p-0" />
                        </label>
                    </div>
                </section>

                <section :class="panelSectionClass">
                    <button class="flex w-full items-center justify-between text-left text-base font-semibold text-[#e3eeff]" @click="openPanel = openPanel === 'typography' ? 'general' : 'typography'">
                        <span>TIPOGRAFIA</span>
                        <ChevronDown class="size-4 transition" :class="openPanel === 'typography' ? 'rotate-180' : ''" />
                    </button>
                    <div v-show="openPanel === 'typography'" class="mt-4 space-y-3">
                        <label class="flex items-center gap-3 rounded-xl border border-[#244579] bg-[#0a1e45] px-4 py-3 text-sm text-[#d7e7ff]">
                            <input v-model="settings.fontStyle" :disabled="!props.permissions.canEdit" type="radio" value="modern" class="accent-[#3d8bff]" />
                            Sora (Moderno)
                        </label>
                        <label class="flex items-center gap-3 rounded-xl border border-[#244579] bg-[#0a1e45] px-4 py-3 text-sm text-[#d7e7ff]">
                            <input v-model="settings.fontStyle" :disabled="!props.permissions.canEdit" type="radio" value="clean" class="accent-[#3d8bff]" />
                            Sans limpo
                        </label>
                        <label class="flex items-center gap-3 rounded-xl border border-[#244579] bg-[#0a1e45] px-4 py-3 text-sm text-[#d7e7ff]">
                            <input v-model="settings.fontStyle" :disabled="!props.permissions.canEdit" type="radio" value="serif" class="accent-[#3d8bff]" />
                            Serif classico
                        </label>
                    </div>
                </section>
            </aside>
        </main>
    </div>
</template>

