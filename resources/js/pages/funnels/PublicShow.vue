<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch  } from 'vue';
import type {CSSProperties} from 'vue';
import { sanitizeStoredAssetUrl } from '@/lib/media';

type DisplayRule = {
    id: string;
    source_block_id: string;
    operator: 'filled' | 'empty' | 'equals' | 'not_equals' | 'contains_any' | 'contains_all';
    value: string;
};

type DisplayRuleGroup = {
    id: string;
    mode: 'all' | 'any';
    rules: DisplayRule[];
};

type StageBlock = {
    id: string;
    type: string;
    label: string;
    placeholder: string | null;
    required: boolean;
    options: string[];
    option_items?: Array<{
        id: string;
        label: string;
        points: number;
        value: string;
        destination: string;
        image_url?: string;
        subtitle?: string | null;
        description?: string | null;
        rating?: number | null;
    }>;
    options_intro_type?: 'text' | 'none' | null;
    options_intro_title?: string | null;
    options_intro_description?: string | null;
    options_required_selection?: boolean;
    options_allow_multiple?: boolean;
    options_disable_auto_follow?: boolean;
    options_transparent_image?: boolean;
    label_style?: 'default' | 'muted' | 'hidden' | null;
    text_align?: 'text-left' | 'text-center' | 'text-right' | null;
    width_percent?: number | null;
    align_horizontal?: 'start' | 'center' | 'end' | null;
    align_vertical?: 'start' | 'center' | 'end' | null;
    show_after_seconds?: number | null;
    display_rule_mode?: 'all' | 'any' | null;
    display_rules?: DisplayRule[] | null;
    display_rule_groups?: DisplayRuleGroup[] | null;
    options_style?: 'simple' | 'highlight' | 'relief' | 'contrast' | 'cards' | null;
    options_layout?: 'grid_2' | 'grid_1' | null;
    options_orientation?: 'vertical' | 'horizontal' | null;
    options_image_ratio?: '1:1' | '4:3' | '16:9' | null;
    options_disposition?: 'image_text' | 'text_image' | 'text' | null;
    options_detail?: 'checkout' | 'arrow' | 'points' | 'value' | 'none' | null;
    options_detail_position?: 'start' | 'end' | null;
    options_border_size?: 'small' | 'medium' | 'large' | null;
    options_shadow?: 'none' | 'soft' | 'strong' | null;
    options_spacing?: 'simple' | 'comfortable' | 'compact' | null;
    testimonials_layout?: 'list' | 'slide' | 'grid' | null;
    faq_first_active?: boolean;
    faq_detail?: 'arrow' | 'chevron' | 'plus_minus' | 'none' | null;
    price_title?: string | null;
    price_prefix?: string | null;
    price_value?: string | null;
    price_suffix?: string | null;
    price_badge_text?: string | null;
    price_mode?: 'illustrative' | 'redirect' | null;
    price_layout?: 'horizontal' | 'vertical' | null;
    price_style?: 'theme' | 'light' | 'dark' | null;
    price_link?: string | null;
    carousel_layout?: 'image_text' | 'image_only' | 'text_only' | null;
    carousel_pagination?: boolean;
    carousel_autoplay?: boolean;
    carousel_border_type?: 'none' | 'subtle' | 'strong' | null;
    image_ratio?: 'auto' | '16:9' | '4:3' | '1:1' | null;
    image_fit?: 'cover' | 'contain' | null;
    image_radius?: 'none' | 'small' | 'medium' | 'large' | 'full' | null;
    image_frame?: 'none' | 'subtle' | 'strong' | null;
    phone_mask?: 'br' | 'us' | 'eu' | null;
    number_mask?: 'decimal' | 'real' | 'dollar' | 'euro' | null;
    height_mode?: 'ruler' | 'input' | null;
    weight_mode?: 'ruler' | 'input' | null;
    button_action?: 'next_stage' | 'open_link';
    button_target_stage_order?: string | null;
    button_link?: string | null;
    button_open_new_tab?: boolean;
    loading_start_seconds?: number | null;
    loading_duration_seconds?: number | null;
    loading_navigation_action?: 'next_stage' | 'open_link' | 'none' | null;
    loading_target_stage_order?: string | null;
    loading_link?: string | null;
    loading_show_title?: boolean;
    loading_show_progress?: boolean;
    button_color_style?: 'theme' | 'dark' | 'light' | null;
    button_animated?: boolean;
    button_elevated?: boolean;
    button_sticky_footer?: boolean;
    video_ratio?: '16:9' | '4:3' | '1:1' | null;
    audio_sender?: string | null;
    audio_src?: string | null;
    audio_avatar_url?: string | null;
    audio_model?: 'whatsapp' | null;
    audio_theme?: 'light' | 'dark' | null;
    notification_title?: string | null;
    notification_description?: string | null;
    notification_avatar_url?: string | null;
    notification_position?: 'default' | 'top_left' | 'top_center' | 'top_right' | 'bottom_left' | 'bottom_center' | 'bottom_right' | null;
    notification_duration_seconds?: number | null;
    notification_interval_seconds?: number | null;
    notification_style?: 'white' | 'dark' | 'blue' | null;
    notification_size?: 'compact' | 'default' | 'large' | null;
    notification_variant?: 'social' | 'offer' | 'message' | null;
    notification_variations?: Array<{
        id: string;
        value1: string;
        value2: string;
        value3: string;
        value4?: string;
    }> | null;
    attention_style?: 'red' | 'amber' | 'blue' | null;
    attention_emphasis?: boolean;
    attention_padding?: 'compact' | 'default' | 'comfortable' | null;
    timer_seconds?: number | null;
    timer_text?: string | null;
    timer_style?: 'red' | 'amber' | 'blue' | null;
    level_title?: string | null;
    level_subtitle?: string | null;
    level_percentage?: number | null;
    level_indicator_text?: string | null;
    level_legends?: string | null;
    level_show_meter?: boolean;
    level_show_progress?: boolean;
    level_type?: 'line' | null;
    level_color?: 'theme' | 'blue' | 'green' | 'red' | null;
};

type PublicStage = {
    id: number;
    name: string;
    title?: string;
    header: {
        show_logo: boolean;
        show_progress: boolean;
        allow_back: boolean;
    };
    blocks: StageBlock[];
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

type PublicFunnel = {
    id: number;
    slug: string;
    name: string;
    description: string | null;
    submit_url: string;
    public_url: string;
    using_custom_domain: boolean;
    custom_domain: string | null;
    design: {
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
        completion_page?: {
            enabled?: boolean;
            title?: string;
            description?: string;
            image_url?: string;
            primary_button_text?: string;
            primary_button_url?: string;
            primary_button_new_tab?: boolean;
            secondary_button_text?: string;
            secondary_button_url?: string;
            secondary_button_new_tab?: boolean;
            auto_redirect_url?: string;
            auto_redirect_delay_seconds?: number;
        };
    };
    stages: PublicStage[];
};

const props = defineProps<{
    funnel: PublicFunnel;
}>();

const page = usePage<{ flash?: { status?: string; completion_lead?: { name?: string | null; email?: string | null; phone?: string | null } } }>();
const stageIndex = ref(0);
const answers = ref<Record<string, string | string[]>>({});
const heightUnits = ref<Record<string, 'cm' | 'in'>>({});
const weightUnits = ref<Record<string, 'kg' | 'lb'>>({});
const fieldErrors = ref<Record<string, string>>({});
const isSubmitting = ref(false);
const isCompleted = ref(page.props.flash?.status === 'funnel-submitted');
const submittedLeadPreview = ref<{ name: string; email: string; phone: string }>({
    name: '',
    email: '',
    phone: '',
});
const stageElapsedMilliseconds = ref(0);
const stageStartedAt = ref<number | null>(null);
const loadingStartTimes = ref<Record<string, number>>({});
const manualCarouselIndices = ref<Record<string, number>>({});
const openFaqItems = ref<Record<string, boolean>>({});
const audioElements = ref<Record<string, HTMLAudioElement | null>>({});
const audioDurations = ref<Record<string, number>>({});
const audioCurrentTimes = ref<Record<string, number>>({});
const activeAudioKey = ref<string | null>(null);
let timerFrame: number | null = null;
let loadingAutoNavigateTimeouts: Record<string, ReturnType<typeof setTimeout>> = {};
const triggeredLoadingActions = ref<Record<string, boolean>>({});
let completionPageRedirectTimeout: ReturnType<typeof setTimeout> | null = null;
const audioWaveHeights = [7, 10, 13, 16, 14, 12, 10, 8, 7, 9, 12, 15, 13, 10, 8, 7, 8, 10, 13, 16, 14, 12, 10, 8, 7];
const defaultDesign = {
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
    completion_page: {
        enabled: false,
        title: 'Resposta enviada',
        description: 'Obrigado. Recebemos suas respostas e em breve entraremos em contato.',
        image_url: '',
        primary_button_text: 'Voltar ao inicio',
        primary_button_url: '/',
        primary_button_new_tab: false,
        secondary_button_text: '',
        secondary_button_url: '',
        secondary_button_new_tab: false,
        auto_redirect_url: '',
        auto_redirect_delay_seconds: 0,
    },
} as const;

const design = computed(() => ({ ...defaultDesign, ...(props.funnel.design ?? {}) }));
const designTokens = computed<DesignTokens>(() => ({
    ...defaultDesign.tokens,
    ...(design.value.tokens ?? {}),
    colors: { ...defaultDesign.tokens.colors, ...(design.value.tokens?.colors ?? {}) },
    typography: { ...defaultDesign.tokens.typography, ...(design.value.tokens?.typography ?? {}) },
    brand: { ...defaultDesign.tokens.brand, ...(design.value.tokens?.brand ?? {}) },
    surfaces: { ...defaultDesign.tokens.surfaces, ...(design.value.tokens?.surfaces ?? {}) },
    borders: { ...defaultDesign.tokens.borders, ...(design.value.tokens?.borders ?? {}) },
    states: { ...defaultDesign.tokens.states, ...(design.value.tokens?.states ?? {}) },
    components: { ...defaultDesign.tokens.components, ...(design.value.tokens?.components ?? {}) },
}));
const funnelThemeStyle = computed(() => ({
    '--funnel-primary': designTokens.value.colors.primary,
    '--funnel-on-primary': designTokens.value.colors.onPrimary,
    '--funnel-heading': designTokens.value.colors.heading,
    '--funnel-text': designTokens.value.colors.text,
    '--funnel-text-muted': designTokens.value.colors.textMuted,
    '--funnel-page': designTokens.value.surfaces.page,
    '--funnel-surface': designTokens.value.surfaces.card,
    '--funnel-surface-muted': designTokens.value.surfaces.muted,
    '--funnel-border': designTokens.value.borders.default,
    '--funnel-border-strong': designTokens.value.borders.strong,
    '--funnel-focus': designTokens.value.borders.focus,
    '--funnel-success': designTokens.value.states.success,
    '--funnel-warning': designTokens.value.states.warning,
    '--funnel-danger': designTokens.value.states.danger,
    '--funnel-disabled-opacity': String(designTokens.value.states.disabledOpacity),
    '--funnel-field-bg': designTokens.value.components.fieldBackground,
    '--funnel-field-text': designTokens.value.components.fieldText,
    '--funnel-button-bg': designTokens.value.components.primaryButtonBackground,
    '--funnel-button-text': designTokens.value.components.primaryButtonText,
    backgroundColor: designTokens.value.surfaces.page,
    color: designTokens.value.colors.text,
}));
const logoAssetUrl = computed(() => sanitizeStoredAssetUrl(designTokens.value.brand.logoUrl) ?? '');
const faviconAssetUrl = computed(() => sanitizeStoredAssetUrl(design.value.faviconUrl) ?? '');
const seoImageAssetUrl = computed(() => sanitizeStoredAssetUrl(design.value.seoImageUrl) ?? '');
const completionPage = computed(() => ({
    ...defaultDesign.completion_page,
    ...(((design.value.completion_page ?? {}) && typeof design.value.completion_page === 'object') ? design.value.completion_page : {}),
}));
const pageTitle = computed(() => safeTrim(design.value.seoTitle) || props.funnel.name);
const pageDescription = computed(() => safeTrim(design.value.seoDescription) || safeTrim(props.funnel.description));
const currentStage = computed(() => props.funnel.stages[stageIndex.value] ?? null);
const isLastStage = computed(() => stageIndex.value >= props.funnel.stages.length - 1);
const stageElapsedSeconds = computed(() => Math.floor(stageElapsedMilliseconds.value / 1000));
const visibleCurrentStageBlocks = computed(() => currentStage.value ? visibleStageBlocks(currentStage.value) : []);
const currentStageShowLogo = computed(() => currentStage.value?.header.show_logo ?? designTokens.value.brand.showLogo);
const currentStageShowProgress = computed(() => currentStage.value?.header.show_progress ?? design.value.showProgress);
const currentStageAllowBack = computed(() => currentStage.value?.header.allow_back ?? design.value.allowBack);
const containerWidthClass = computed(() => {
    return design.value.width === 'small' ? 'max-w-xl' : design.value.width === 'medium' ? 'max-w-3xl' : 'max-w-4xl';
});
const fieldLabelClass = computed(() => {
    return design.value.elementSize === 'large' ? 'mb-2 block text-base text-[#cfe1ff]' : 'mb-1.5 block text-sm text-[#cfe1ff]';
});
const spacingClass = computed(() => {
    return design.value.spacing === 'compact' ? 'space-y-3' : design.value.spacing === 'large' ? 'space-y-6' : 'space-y-4';
});
const radiusClass = computed(() => {
    return design.value.radius === 'small' ? 'rounded-xl' : design.value.radius === 'large' ? 'rounded-3xl' : 'rounded-2xl';
});
const fontClass = computed(() => {
    return designTokens.value.typography.family === 'serif' ? 'font-serif' : designTokens.value.typography.family === 'clean' ? 'font-sans' : 'font-["Sora"]';
});

function safeTrim(value: unknown): string {
    if (typeof value === 'string') {
        return value.trim();
    }

    if (value === null || value === undefined) {
        return '';
    }

    return String(value).trim();
}

function publicThemeButtonStyle(block: StageBlock): Record<string, string> {
    if (block.button_color_style === 'dark' || block.button_color_style === 'light') {
        return {};
    }

    return {
        background: designTokens.value.components.primaryButtonBackground,
        color: designTokens.value.components.primaryButtonText,
        borderColor: designTokens.value.borders.strong,
    };
}

function findFirstAnswerByType(type: string): string {
    for (const stage of props.funnel.stages) {
        for (const block of stage.blocks) {
            if (block.type !== type) {
                continue;
            }

            const value = answers.value[answerKey(stage.id, block.id)];

            if (typeof value === 'string' && safeTrim(value).length > 0) {
                return safeTrim(value);
            }
        }
    }

    return '';
}

const completionPageTokenValues = computed(() => ({
    nome: safeTrim(submittedLeadPreview.value.name) || safeTrim(page.props.flash?.completion_lead?.name) || findFirstAnswerByType('text'),
    name: safeTrim(submittedLeadPreview.value.name) || safeTrim(page.props.flash?.completion_lead?.name) || findFirstAnswerByType('text'),
    email: safeTrim(submittedLeadPreview.value.email) || safeTrim(page.props.flash?.completion_lead?.email) || findFirstAnswerByType('email'),
    telefone: safeTrim(submittedLeadPreview.value.phone) || safeTrim(page.props.flash?.completion_lead?.phone) || findFirstAnswerByType('phone'),
    phone: safeTrim(submittedLeadPreview.value.phone) || safeTrim(page.props.flash?.completion_lead?.phone) || findFirstAnswerByType('phone'),
}));

function replaceCompletionTokens(template: string): string {
    return template
        .replaceAll('{nome}', completionPageTokenValues.value.nome)
        .replaceAll('{name}', completionPageTokenValues.value.name)
        .replaceAll('{email}', completionPageTokenValues.value.email)
        .replaceAll('{telefone}', completionPageTokenValues.value.telefone)
        .replaceAll('{phone}', completionPageTokenValues.value.phone);
}

const completionPageTitle = computed(() => {
    const configured = replaceCompletionTokens(safeTrim(completionPage.value.title));

    return configured.length > 0 ? configured : 'Resposta enviada';
});

const completionPageDescription = computed(() => {
    const configured = replaceCompletionTokens(safeTrim(completionPage.value.description));

    return configured.length > 0 ? configured : 'Obrigado. Recebemos suas respostas e em breve entraremos em contato.';
});

const completionPageImageUrl = computed(() => sanitizeStoredAssetUrl(safeTrim(completionPage.value.image_url)) ?? '');
const usesCustomCompletionPage = computed(() => completionPage.value.enabled === true);

function shouldRenderCompletionButton(text: string | undefined, url: string | undefined): boolean {
    return safeTrim(text).length > 0 && safeTrim(url).length > 0;
}

function openCompletionPageLink(url: string | undefined, openNewTab = false): void {
    const targetUrl = safeTrim(url);

    if (targetUrl.length === 0) {
        return;
    }

    if (openNewTab) {
        window.open(targetUrl, '_blank', 'noopener,noreferrer');

        return;
    }

    window.location.assign(targetUrl);
}

function shouldShowPublicLabel(block: StageBlock): boolean {
    if ((block.label_style ?? 'default') === 'hidden') {
        return false;
    }

    return safeTrim(block.label).length > 0;
}

function publicLabelClass(block: StageBlock): string {
    if ((block.label_style ?? 'default') === 'muted') {
        return `${fieldLabelClass.value} text-[#89a7d9]`;
    }

    return fieldLabelClass.value;
}

function publicBlockWrapperStyle(block: StageBlock): CSSProperties {
    const width = Math.max(25, Math.min(100, Number(block.width_percent ?? 100)));
    const horizontal = block.align_horizontal ?? 'start';

    return {
        width: `${width}%`,
        marginLeft: horizontal === 'end' ? 'auto' : horizontal === 'center' ? 'auto' : '0',
        marginRight: horizontal === 'start' ? 'auto' : horizontal === 'center' ? 'auto' : '0',
    };
}

function answerValueForRule(blockId: string): string | string[] | undefined {
    for (const [key, value] of Object.entries(answers.value)) {
        if (key.endsWith(`:${blockId}`)) {
            return value;
        }
    }

    return undefined;
}

function evaluateDisplayRule(rule: DisplayRule): boolean {
    const blockId = safeTrim(rule.source_block_id);

    if (blockId.length === 0) {
        return true;
    }

    if (rule.operator === 'filled' || rule.operator === 'empty') {
        const value = answerValueForRule(blockId);
        const filled = isFilled(value);

        return rule.operator === 'filled' ? filled : !filled;
    }

    const expectedValues = safeTrim(rule.value)
        .split('|')
        .map((value) => safeTrim(value))
        .filter((value) => value.length > 0);

    if (expectedValues.length === 0) {
        return true;
    }

    const rawValue = answerValueForRule(blockId);
    const actualValues = Array.isArray(rawValue)
        ? rawValue.map((value) => safeTrim(value)).filter((value) => value.length > 0)
        : [safeTrim(rawValue)].filter((value) => value.length > 0);
    const anyMatch = expectedValues.some((value) => actualValues.includes(value));
    const allMatch = expectedValues.every((value) => actualValues.includes(value));

    if (rule.operator === 'contains_any') {
        return anyMatch;
    }

    if (rule.operator === 'contains_all') {
        return allMatch;
    }

    return rule.operator === 'not_equals' ? !anyMatch : anyMatch;
}

function normalizedDisplayRuleGroups(block: StageBlock): DisplayRuleGroup[] {
    if ((block.display_rule_groups ?? []).length > 0) {
        return (block.display_rule_groups ?? []).filter((group) => (group.rules?.length ?? 0) > 0);
    }

    if ((block.display_rules ?? []).length === 0) {
        return [];
    }

    return [{
        id: `${block.id}-legacy-group`,
        mode: block.display_rule_mode ?? 'all',
        rules: block.display_rules ?? [],
    }];
}

function isBlockVisible(block: StageBlock): boolean {
    const showAfter = Math.max(0, Number(block.show_after_seconds ?? 0));

    if (showAfter > stageElapsedSeconds.value) {
        return false;
    }

    const groups = normalizedDisplayRuleGroups(block);

    if (groups.length === 0) {
        return true;
    }

    return (block.display_rule_mode ?? 'all') === 'any'
        ? groups.some((group) => (group.mode ?? 'all') === 'any'
            ? group.rules.some((rule) => evaluateDisplayRule(rule))
            : group.rules.every((rule) => evaluateDisplayRule(rule)))
        : groups.every((group) => (group.mode ?? 'all') === 'any'
            ? group.rules.some((rule) => evaluateDisplayRule(rule))
            : group.rules.every((rule) => evaluateDisplayRule(rule)));
}

function visibleStageBlocks(stage: PublicStage): StageBlock[] {
    return stage.blocks.filter((block) => isBlockVisible(block));
}

function audioPreviewKey(stageId: number, blockId: string): string {
    return `${stageId}:${blockId}`;
}

function audioSource(block: StageBlock): string | null {
    return sanitizeStoredAssetUrl(block.audio_src);
}

function bindAudioElement(key: string, element: HTMLAudioElement | null): void {
    audioElements.value[key] = element;

    if (element && Number.isFinite(element.duration)) {
        audioDurations.value[key] = element.duration;
    }
}

function pauseAudio(key: string): void {
    audioElements.value[key]?.pause();
}

function pauseActiveAudio(exceptKey?: string): void {
    if (!activeAudioKey.value || activeAudioKey.value === exceptKey) {
        return;
    }

    pauseAudio(activeAudioKey.value);
}

function audioSourceForKey(key: string): string {
    const element = audioElements.value[key];

    return element?.currentSrc || element?.src || '';
}

async function toggleAudioPlayback(key: string): Promise<void> {
    const element = audioElements.value[key];

    if (!element || !audioSourceForKey(key)) {
        return;
    }

    if (!element.paused) {
        element.pause();
        return;
    }

    pauseActiveAudio(key);

    try {
        await element.play();
    } catch {
        activeAudioKey.value = null;
    }
}

function onAudioLoadedMetadata(key: string, event: Event): void {
    const element = event.target as HTMLAudioElement;
    audioDurations.value[key] = Number.isFinite(element.duration) ? element.duration : 0;
}

function onAudioTimeUpdate(key: string, event: Event): void {
    const element = event.target as HTMLAudioElement;
    audioCurrentTimes.value[key] = Number.isFinite(element.currentTime) ? element.currentTime : 0;
}

function onAudioPlay(key: string): void {
    pauseActiveAudio(key);
    activeAudioKey.value = key;
}

function onAudioPause(key: string): void {
    if (activeAudioKey.value === key) {
        activeAudioKey.value = null;
    }
}

function onAudioEnded(key: string): void {
    audioCurrentTimes.value[key] = 0;

    if (activeAudioKey.value === key) {
        activeAudioKey.value = null;
    }
}

function audioProgressRatio(key: string): number {
    const duration = audioDurations.value[key] ?? 0;

    if (duration <= 0) {
        return 0;
    }

    return Math.min(1, Math.max(0, (audioCurrentTimes.value[key] ?? 0) / duration));
}

function formatAudioTime(totalSeconds: number): string {
    if (!Number.isFinite(totalSeconds) || totalSeconds <= 0) {
        return '00:00';
    }

    const minutes = Math.floor(totalSeconds / 60);
    const seconds = Math.floor(totalSeconds % 60);

    return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
}

function displayedAudioCurrentTime(key: string): string {
    return formatAudioTime(audioCurrentTimes.value[key] ?? 0);
}

function displayedAudioDuration(key: string): string {
    return formatAudioTime(audioDurations.value[key] ?? 0);
}

function seekAudio(key: string, event: MouseEvent): void {
    const element = audioElements.value[key];

    if (!element) {
        return;
    }

    const target = event.currentTarget as HTMLElement | null;

    if (!target) {
        return;
    }

    const rect = target.getBoundingClientRect();
    const ratio = Math.min(1, Math.max(0, (event.clientX - rect.left) / rect.width));
    const duration = audioDurations.value[key] ?? element.duration ?? 0;

    if (!Number.isFinite(duration) || duration <= 0) {
        return;
    }

    element.currentTime = duration * ratio;
    audioCurrentTimes.value[key] = element.currentTime;
}

function seekAudioByRatio(key: string, ratio: number): void {
    const element = audioElements.value[key];

    if (!element) {
        return;
    }

    const duration = audioDurations.value[key] ?? element.duration ?? 0;

    if (!Number.isFinite(duration) || duration <= 0) {
        return;
    }

    element.currentTime = duration * Math.min(1, Math.max(0, ratio));
    audioCurrentTimes.value[key] = element.currentTime;
}

function handleAudioKeyboard(key: string, event: KeyboardEvent): void {
    if (event.key === ' ' || event.key === 'Enter') {
        event.preventDefault();
        void toggleAudioPlayback(key);
        return;
    }

    if (event.key === 'ArrowLeft') {
        event.preventDefault();
        seekAudioByRatio(key, audioProgressRatio(key) - 0.05);
        return;
    }

    if (event.key === 'ArrowRight') {
        event.preventDefault();
        seekAudioByRatio(key, audioProgressRatio(key) + 0.05);
    }
}

function escapeHtml(value: string): string {
    return value
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');
}

function normalizeRichTextHtml(value: string | null | undefined): string {
    const raw = safeTrim(value)
        .replace(/&nbsp;/gi, ' ')
        .replace(/<div><br><\/div>/gi, '')
        .replace(/<p><br><\/p>/gi, '')
        .trim();

    const plainText = raw.replace(/<[^>]+>/g, '').trim();

    if (plainText.length === 0) {
        return '';
    }

    return raw;
}

function containsHtmlTag(value: string): boolean {
    return /<[^>]+>/.test(value);
}

function contentTextMarkup(block: StageBlock): string {
    const storedMarkup = normalizeRichTextHtml(block.placeholder);
    if (storedMarkup.length > 0 && containsHtmlTag(storedMarkup)) {
        return storedMarkup;
    }

    const legacyTitle = safeTrim(block.label);
    const legacyDescription = storedMarkup;
    const fragments: string[] = [];

    if (legacyTitle.length > 0) {
        fragments.push(`<h2>${escapeHtml(legacyTitle)}</h2>`);
    }

    if (legacyDescription.length > 0) {
        fragments.push(`<p>${escapeHtml(legacyDescription)}</p>`);
    }

    return fragments.join('');
}

function hasContentTextContent(block: StageBlock): boolean {
    return contentTextMarkup(block).length > 0;
}

function imageAspectClass(block: StageBlock): string {
    return block.image_ratio === '16:9'
        ? 'aspect-video'
        : block.image_ratio === '4:3'
            ? 'aspect-[4/3]'
            : block.image_ratio === '1:1'
                ? 'aspect-square'
                : '';
}

function imageRadiusClass(block: StageBlock): string {
    return block.image_radius === 'none'
        ? 'rounded-none'
        : block.image_radius === 'small'
            ? 'rounded-md'
            : block.image_radius === 'large'
                ? 'rounded-2xl'
                : block.image_radius === 'full'
                    ? 'rounded-[999px]'
                    : 'rounded-xl';
}

function imageFrameClass(block: StageBlock): string {
    return block.image_frame === 'strong'
        ? 'border-[#2f538f] bg-[#0b274f] p-2.5'
        : block.image_frame === 'none'
            ? 'border-transparent bg-transparent p-0'
            : 'border-[#2f538f] bg-[#0b274f] p-2';
}

function imageFitClass(block: StageBlock): string {
    return block.image_fit === 'contain' ? 'object-contain' : 'object-cover';
}

function answerKey(stageId: number, blockId: string): string {
    return `${stageId}:${blockId}`;
}

function getAnswer(stageId: number, blockId: string): string | string[] | undefined {
    return answers.value[answerKey(stageId, blockId)];
}

function setTextAnswer(stageId: number, blockId: string, value: string): void {
    answers.value[answerKey(stageId, blockId)] = value;
}

function heightUnitKey(stageId: number, blockId: string): string {
    return `height-unit:${stageId}:${blockId}`;
}

function getHeightUnit(stageId: number, blockId: string): 'cm' | 'in' {
    return heightUnits.value[heightUnitKey(stageId, blockId)] ?? 'cm';
}

function setHeightUnit(stageId: number, blockId: string, unit: 'cm' | 'in'): void {
    heightUnits.value[heightUnitKey(stageId, blockId)] = unit;
}

function getHeightValueInCm(stageId: number, blockId: string): number {
    const value = getAnswer(stageId, blockId);
    const parsed = Number.parseFloat(typeof value === 'string' ? value : '');

    if (!Number.isNaN(parsed) && parsed > 0) {
        return Math.round(parsed);
    }

    return 170;
}

function getHeightDisplayValue(stageId: number, blockId: string): number {
    const baseCm = getHeightValueInCm(stageId, blockId);
    const unit = getHeightUnit(stageId, blockId);

    if (unit === 'in') {
        return Math.round(baseCm / 2.54);
    }

    return baseCm;
}

function setHeightFromRuler(stageId: number, blockId: string, value: number): void {
    const unit = getHeightUnit(stageId, blockId);
    const normalizedCm = unit === 'in' ? Math.round(value * 2.54) : Math.round(value);
    setTextAnswer(stageId, blockId, String(normalizedCm));
}

function weightUnitKey(stageId: number, blockId: string): string {
    return `weight-unit:${stageId}:${blockId}`;
}

function getWeightUnit(stageId: number, blockId: string): 'kg' | 'lb' {
    return weightUnits.value[weightUnitKey(stageId, blockId)] ?? 'kg';
}

function setWeightUnit(stageId: number, blockId: string, unit: 'kg' | 'lb'): void {
    weightUnits.value[weightUnitKey(stageId, blockId)] = unit;
}

function getWeightValueInKg(stageId: number, blockId: string): number {
    const value = getAnswer(stageId, blockId);
    const parsed = Number.parseFloat(typeof value === 'string' ? value : '');

    if (!Number.isNaN(parsed) && parsed > 0) {
        return Math.round(parsed);
    }

    return 70;
}

function getWeightDisplayValue(stageId: number, blockId: string): number {
    const baseKg = getWeightValueInKg(stageId, blockId);
    const unit = getWeightUnit(stageId, blockId);

    if (unit === 'lb') {
        return Math.round(baseKg * 2.20462);
    }

    return baseKg;
}

function setWeightFromRuler(stageId: number, blockId: string, value: number): void {
    const unit = getWeightUnit(stageId, blockId);
    const normalizedKg = unit === 'lb' ? Math.round(value / 2.20462) : Math.round(value);
    setTextAnswer(stageId, blockId, String(normalizedKg));
}

function maskPhone(value: string, mask: StageBlock['phone_mask']): string {
    const digits = value.replace(/\D/g, '').slice(0, 11);

    if (mask === 'us') {
        const usDigits = digits.slice(0, 10);

        if (usDigits.length <= 3) {
            return usDigits;
        }

        if (usDigits.length <= 6) {
            return `(${usDigits.slice(0, 3)}) ${usDigits.slice(3)}`;
        }

        return `(${usDigits.slice(0, 3)}) ${usDigits.slice(3, 6)}-${usDigits.slice(6)}`;
    }

    if (mask === 'eu') {
        const euDigits = digits.slice(0, 12);

        if (euDigits.length <= 2) {
            return euDigits.length > 0 ? `+${euDigits}` : '';
        }

        if (euDigits.length <= 5) {
            return `+${euDigits.slice(0, 2)} ${euDigits.slice(2)}`;
        }

        if (euDigits.length <= 8) {
            return `+${euDigits.slice(0, 2)} ${euDigits.slice(2, 5)} ${euDigits.slice(5)}`;
        }

        return `+${euDigits.slice(0, 2)} ${euDigits.slice(2, 5)} ${euDigits.slice(5, 8)} ${euDigits.slice(8)}`;
    }

    if (digits.length <= 2) {
        return digits;
    }

    if (digits.length <= 6) {
        return `(${digits.slice(0, 2)}) ${digits.slice(2)}`;
    }

    if (digits.length <= 10) {
        return `(${digits.slice(0, 2)}) ${digits.slice(2, 6)}-${digits.slice(6)}`;
    }

    return `(${digits.slice(0, 2)}) ${digits.slice(2, 7)}-${digits.slice(7)}`;
}

function maskMeasurement(value: string): string {
    const normalized = value.replace(/[^\d,.]/g, '').replace(',', '.');
    const parts = normalized.split('.');
    const integerPart = (parts[0] ?? '').slice(0, 3);
    const decimalPart = (parts[1] ?? '').slice(0, 2);

    if (decimalPart.length === 0) {
        return integerPart;
    }

    return `${integerPart}.${decimalPart}`;
}

function normalizeNumberValue(value: string): number {
    const digitsOnly = value.replace(/[^\d]/g, '');

    if (digitsOnly === '') {
        return 0;
    }

    return Number.parseInt(digitsOnly, 10) / 100;
}

function maskNumberByType(value: string, mask: StageBlock['number_mask']): string {
    if (mask === 'real') {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(normalizeNumberValue(value));
    }

    if (mask === 'dollar') {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(normalizeNumberValue(value));
    }

    if (mask === 'euro') {
        return new Intl.NumberFormat('de-DE', {
            style: 'currency',
            currency: 'EUR',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(normalizeNumberValue(value));
    }

    const normalized = value.replace(/[^\d.,]/g, '').replace(',', '.');
    const [integerPartRaw, decimalPartRaw = ''] = normalized.split('.');
    const integerPart = integerPartRaw.slice(0, 9);
    const decimalPart = decimalPartRaw.slice(0, 2);

    if (decimalPart.length === 0) {
        return integerPart;
    }

    return `${integerPart}.${decimalPart}`;
}

function maskEmail(value: string): string {
    const sanitized = value.toLowerCase().replace(/\s+/g, '');
    const atIndex = sanitized.indexOf('@');

    if (atIndex < 0) {
        return sanitized.replace(/[^a-z0-9._%+-]/g, '');
    }

    const local = sanitized.slice(0, atIndex).replace(/[^a-z0-9._%+-]/g, '');
    const domain = sanitized.slice(atIndex + 1).replace(/@/g, '').replace(/[^a-z0-9.-]/g, '');

    return `${local}@${domain}`;
}

function isValidEmail(value: string): boolean {
    const normalized = value.trim().toLowerCase();

    return /^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$/i.test(normalized);
}

function setMaskedTextAnswer(stageId: number, block: StageBlock, value: string): void {
    if (block.type === 'email') {
        setTextAnswer(stageId, block.id, maskEmail(value));

        return;
    }

    if (block.type === 'phone') {
        setTextAnswer(stageId, block.id, maskPhone(value, block.phone_mask ?? 'br'));

        return;
    }

    if (block.type === 'height' || block.type === 'weight') {
        setTextAnswer(stageId, block.id, maskMeasurement(value));

        return;
    }

    if (block.type === 'number') {
        setTextAnswer(stageId, block.id, maskNumberByType(value, block.number_mask ?? 'decimal'));

        return;
    }

    setTextAnswer(stageId, block.id, value);
}

function handleBlockButtonClick(block: StageBlock): void {
    const targetUrl = safeTrim(block.button_link);

    if (block.button_action === 'open_link' && targetUrl.length > 0) {
        const target = block.button_open_new_tab ? '_blank' : '_self';
        window.open(targetUrl, target, block.button_open_new_tab ? 'noopener,noreferrer' : undefined);

        return;
    }

    if (block.button_action === 'open_link') {
        return;
    }

    navigateToStageTarget(block.button_target_stage_order);
}

function handlePriceClick(block: StageBlock): void {
    if ((block.price_mode ?? 'illustrative') !== 'redirect') {
        return;
    }

    const targetUrl = (block.price_link ?? '').trim();
    if (targetUrl.length === 0) {
        return;
    }

    window.open(targetUrl, '_blank', 'noopener,noreferrer');
}

function handleLoadingNavigation(block: StageBlock): void {
    if (block.loading_navigation_action === 'none') {
        return;
    }

    if (block.loading_navigation_action === 'open_link') {
        const targetUrl = (block.loading_link ?? '').trim();
        if (targetUrl.length > 0) {
            window.open(targetUrl, '_blank', 'noopener,noreferrer');
        }

        return;
    }

    navigateToStageTarget(block.loading_target_stage_order);
}

function fieldInputType(type: string): string {
    if (type === 'email') {
        return 'email';
    }

    if (type === 'date') {
        return 'date';
    }

    return 'text';
}

function fieldInputMode(type: string): 'tel' | 'decimal' | undefined {
    if (type === 'phone') {
        return 'tel';
    }

    if (type === 'height' || type === 'weight' || type === 'number') {
        return 'decimal';
    }

    return undefined;
}

function getVideoAspectClass(block: StageBlock): string {
    if (block.video_ratio === '4:3') {
        return 'aspect-[4/3]';
    }

    if (block.video_ratio === '1:1') {
        return 'aspect-square';
    }

    return 'aspect-video';
}

function toEmbedVideoUrl(url: string | null): string | null {
    const raw = (url ?? '').trim();

    if (raw.length === 0) {
        return null;
    }

    try {
        const parsed = new URL(raw);

        if (parsed.hostname.includes('youtube.com')) {
            const videoId = parsed.searchParams.get('v');

            if (videoId) {
                return `https://www.youtube.com/embed/${videoId}`;
            }

            if (parsed.pathname.startsWith('/shorts/')) {
                const shortsId = parsed.pathname.split('/').filter((segment) => segment.length > 0)[1];

                if (shortsId) {
                    return `https://www.youtube.com/embed/${shortsId}`;
                }
            }

            if (parsed.pathname.startsWith('/embed/')) {
                return raw;
            }

            return null;
        }

        if (parsed.hostname.includes('youtu.be')) {
            const videoId = parsed.pathname.replace('/', '').trim();

            if (videoId) {
                return `https://www.youtube.com/embed/${videoId}`;
            }
        }

        if (parsed.hostname.includes('vimeo.com')) {
            const segments = parsed.pathname.split('/').filter((segment) => segment.length > 0);
            const videoId = segments[segments.length - 1];

            if (videoId && /^\d+$/.test(videoId)) {
                return `https://player.vimeo.com/video/${videoId}`;
            }
        }

        return raw;
    } catch {
        return null;
    }
}

function setSingleChoice(stageId: number, blockId: string, value: string): void {
    answers.value[answerKey(stageId, blockId)] = value;
}

function normalizeStageTarget(target: string | null | undefined): string {
    return safeTrim(target).toLowerCase();
}

function resolveStageTargetIndex(target: string | null | undefined): number | null {
    const normalizedTarget = normalizeStageTarget(target);

    if (normalizedTarget.length === 0 || normalizedTarget === 'next' || normalizedTarget === 'next_stage' || normalizedTarget === 'proxima etapa' || normalizedTarget === 'próxima etapa') {
        return isLastStage.value ? null : stageIndex.value + 1;
    }

    if (/^\d+$/.test(normalizedTarget)) {
        const targetOrder = Number(normalizedTarget);
        const matchedStageIndex = props.funnel.stages.findIndex((_, index) => index + 1 === targetOrder);

        return matchedStageIndex >= 0 ? matchedStageIndex : null;
    }

    const stageOrderMatch = normalizedTarget.match(/(?:etapa|stage)\s*(\d+)/i);

    if (stageOrderMatch) {
        const targetOrder = Number(stageOrderMatch[1]);
        const matchedStageIndex = props.funnel.stages.findIndex((_, index) => index + 1 === targetOrder);

        return matchedStageIndex >= 0 ? matchedStageIndex : null;
    }

    const matchedByTitleIndex = props.funnel.stages.findIndex((stage) => normalizeStageTarget(stage.title) === normalizedTarget);

    if (matchedByTitleIndex >= 0) {
        return matchedByTitleIndex;
    }

    const matchedByNameIndex = props.funnel.stages.findIndex((stage) => normalizeStageTarget(stage.name) === normalizedTarget);

    return matchedByNameIndex >= 0 ? matchedByNameIndex : null;
}

function stageHasAllRequiredAnswers(stage: PublicStage): boolean {
    for (const block of visibleStageBlocks(stage)) {
        const isRequired = isOptionsComponentType(block.type) ? (block.options_required_selection ?? block.required) : block.required;

        if (!isRequired || !isAnswerableBlock(block.type)) {
            continue;
        }

        const value = getAnswer(stage.id, block.id);

        if (!isFilled(value)) {
            return false;
        }

        if (block.type === 'email' && typeof value === 'string' && !isValidEmail(value)) {
            return false;
        }
    }

    return true;
}

function navigateToStageTarget(target: string | null | undefined): void {
    const targetIndex = resolveStageTargetIndex(target);

    if (targetIndex !== null && targetIndex !== stageIndex.value) {
        stageIndex.value = targetIndex;
        fieldErrors.value = {};

        return;
    }

    if (isLastStage.value) {
        submitFunnel();

        return;
    }

    goNext();
}

function handleOptionSelection(stageId: number, block: StageBlock, optionLabel: string, destination?: string): void {
    const stage = props.funnel.stages.find((entry) => entry.id === stageId);

    if (!stage) {
        return;
    }

    if (block.options_allow_multiple) {
        toggleMultipleChoice(stageId, block.id, optionLabel);

        return;
    }

    setSingleChoice(stageId, block.id, optionLabel);

    if (block.options_disable_auto_follow) {
        return;
    }

    if (!stageHasAllRequiredAnswers(stage)) {
        return;
    }

    navigateToStageTarget(destination);
}

function optionsLabels(block: StageBlock): string[] {
    if (Array.isArray(block.option_items) && block.option_items.length > 0) {
        return block.option_items.map((item) => item.label);
    }

    return block.options ?? [];
}

function hasOptionsIntroContent(block: StageBlock): boolean {
    if ((block.options_intro_type ?? 'none') === 'none') {
        return false;
    }

    return safeTrim(block.options_intro_title).length > 0 || safeTrim(block.options_intro_description).length > 0;
}

function normalizeDetailValue(value: StageBlock['options_detail']): 'checkout' | 'arrow' | 'points' | 'value' | 'none' {
    if (value === 'none') {
        return 'none';
    }

    if (value === 'checkout') {
        return 'checkout';
    }

    if (value === 'arrow') {
        return 'arrow';
    }

    if (value === 'points') {
        return 'points';
    }

    if (value === 'value') {
        return 'value';
    }

    return 'checkout';
}

function optionsDisplayItems(block: StageBlock): Array<{ label: string; points: number; value: string; destination: string; image_url: string }> {
    if (Array.isArray(block.option_items) && block.option_items.length > 0) {
        return block.option_items.map((item, index) => ({
            label: item.label,
            points: Number.isFinite(Number(item.points)) ? Number(item.points) : index + 1,
            value: (item.value ?? '').trim() || String.fromCharCode(65 + (index % 26)),
            destination: safeTrim(item.destination) || 'next_stage',
            image_url: safeTrim(item.image_url),
        }));
    }

    return optionsLabels(block).map((label, index) => ({
        label,
        points: index + 1,
        value: String.fromCharCode(65 + (index % 26)),
        destination: 'next_stage',
        image_url: '',
    }));
}

function optionsShouldRenderImage(block: StageBlock, item: { image_url: string }): boolean {
    return (block.options_disposition ?? 'image_text') !== 'text' && item.image_url.length > 0;
}

function optionsMediaOrderClass(block: StageBlock): string {
    return (block.options_disposition ?? 'image_text') === 'text_image' ? 'order-2' : 'order-1';
}

function optionsLabelOrderClass(block: StageBlock): string {
    return (block.options_disposition ?? 'image_text') === 'text_image' ? 'order-1' : 'order-2';
}

function optionsBodyClass(block: StageBlock): string {
    return block.options_layout === 'grid_1' ? 'flex items-center gap-3' : 'flex items-center gap-2';
}

function optionsImageRatioClass(block: StageBlock): string {
    if (block.options_image_ratio === '16:9') {
        return 'aspect-video';
    }

    if (block.options_image_ratio === '4:3') {
        return 'aspect-[4/3]';
    }

    return 'aspect-square';
}

function optionsImageWrapClass(block: StageBlock): string {
    return [
        block.options_layout === 'grid_1' ? 'w-16' : 'w-14',
        optionsImageRatioClass(block),
        block.options_transparent_image ? 'bg-transparent' : 'bg-[#f4f7fb]',
        'overflow-hidden rounded-lg border border-[#d7dde8]',
    ].join(' ');
}

function optionsDetailWrapClass(block: StageBlock): string {
    return block.options_detail_position === 'end' ? 'order-3 ml-auto' : 'order-1';
}

function optionsListClass(block: StageBlock): string {
    if (block.options_layout === 'grid_2') {
        return 'grid grid-cols-1 gap-2 sm:grid-cols-2';
    }

    if (block.options_orientation === 'horizontal') {
        return 'flex flex-wrap gap-2';
    }

    return 'space-y-2';
}

function optionsItemWidthClass(block: StageBlock): string {
    if (block.options_layout === 'grid_2') {
        return 'w-full';
    }

    if (block.options_orientation === 'horizontal') {
        return 'w-full sm:min-w-[12rem] sm:flex-1';
    }

    return 'w-full';
}

function optionsCardRadiusClass(block: StageBlock): string {
    if (block.options_border_size === 'small') {
        return 'rounded-lg';
    }

    if (block.options_border_size === 'large') {
        return 'rounded-2xl';
    }

    return 'rounded-xl';
}

function optionsCardSpacingClass(block: StageBlock): string {
    if (block.options_spacing === 'compact') {
        return 'px-2.5 py-2';
    }

    if (block.options_spacing === 'comfortable') {
        return 'px-4 py-3.5';
    }

    return 'px-3 py-2.5';
}

function optionsCardShadowClass(block: StageBlock): string {
    if (block.options_shadow === 'soft') {
        return 'shadow-[0_8px_20px_rgba(9,24,56,0.24)]';
    }

    if (block.options_shadow === 'strong') {
        return 'shadow-[0_14px_30px_rgba(9,24,56,0.35)]';
    }

    return '';
}

function normalizeOptionsStyle(style?: StageBlock['options_style']): 'simple' | 'highlight' | 'relief' | 'contrast' {
    if (style === 'highlight' || style === 'relief' || style === 'contrast') {
        return style;
    }

    if (style === 'cards') {
        return 'highlight';
    }

    return 'simple';
}

function optionsCardToneClass(block: StageBlock, active: boolean): string {
    if (active) {
        return 'border-[#4f8fff] bg-[#12386d]';
    }

    const style = normalizeOptionsStyle(block.options_style);

    if (style === 'highlight') {
        return 'border-[#4a7bc4] bg-[#102f61]';
    }

    if (style === 'contrast') {
        return 'border-[#5e8bcf] bg-[#153a72]';
    }

    if (style === 'relief') {
        return 'border-[#3f6fb2] bg-[#0f2f5f]';
    }

    return 'border-[#2f538f] bg-[#0b274f]';
}

function optionsCardMinWidthClass(block: StageBlock): string {
    return block.options_layout === 'grid_2' ? '' : 'min-w-0';
}

function optionsDetailLabel(block: StageBlock, item: { points: number; value: string }, index: number): string {
    const detail = normalizeDetailValue(block.options_detail);

    if (detail === 'checkout' || detail === 'none') {
        return '';
    }

    if (detail === 'points') {
        return String(Math.round(item.points || index + 1));
    }

    if (detail === 'value') {
        return item.value || String.fromCharCode(65 + (index % 26));
    }

    return '>';
}

function optionsDetailBadgeClass(block: StageBlock): string {
    const detail = normalizeDetailValue(block.options_detail);

    if (detail === 'none') {
        return 'hidden';
    }

    if (detail === 'checkout') {
        return 'h-6 w-6 rounded-full border border-[#8fa9d9]/75 bg-transparent';
    }

    if (detail === 'arrow') {
        return 'h-9 w-9 rounded-full';
    }

    return 'h-7 min-w-7 rounded-md px-2';
}

function optionsDetailTextClass(block: StageBlock): string {
    if (normalizeDetailValue(block.options_detail) === 'arrow') {
        return 'text-3xl font-bold leading-none';
    }

    return '';
}

function optionTextAlignClass(block: StageBlock): string {
    if (block.text_align === 'text-right') {
        return 'text-right';
    }

    if (block.text_align === 'text-center') {
        return 'text-center';
    }

    return 'text-left';
}

function contentTextAlignClass(block: StageBlock): string {
    if (block.text_align === 'text-right') {
        return 'text-right';
    }

    if (block.text_align === 'text-center') {
        return 'text-center';
    }

    return 'text-left';
}

function testimonialItems(block: StageBlock): Array<{ id: string; label: string; subtitle: string; description: string; rating: number }> {
    const baseItems = Array.isArray(block.option_items) && block.option_items.length > 0
        ? block.option_items
        : (block.options ?? []).map((value, index) => {
            const segments = String(value).split('|');
            return {
                id: `${block.id}-testimonial-${index}`,
                label: (segments[0] ?? '').trim(),
                subtitle: (segments[1] ?? '').trim(),
                rating: Number((segments[2] ?? '5').trim()),
                description: segments.slice(3).join('|').trim(),
                points: Number((segments[2] ?? '5').trim()),
                value: (segments[1] ?? '').trim(),
                destination: segments.slice(3).join('|').trim(),
            };
        });

    return baseItems.map((item) => {
        const handle = (item.subtitle ?? item.value ?? '').trim();
        const description = (item.description ?? item.destination ?? '').trim();
        const parsedRating = Number(item.rating ?? item.points ?? 5);
        return {
            id: item.id,
            label: item.label?.trim() ?? '',
            subtitle: handle,
            description,
            rating: Math.max(1, Math.min(5, Math.round(Number.isFinite(parsedRating) ? parsedRating : 5))),
        };
    });
}

function ratingStars(rating: number): number[] {
    const safeRating = Math.max(1, Math.min(5, Math.round(Number.isFinite(rating) ? rating : 5)));

    return Array.from({ length: safeRating }, (_, index) => index);
}

function testimonialListClass(block: StageBlock): string {
    if (block.testimonials_layout === 'grid') {
        return 'grid gap-3 sm:grid-cols-2';
    }

    if (block.testimonials_layout === 'slide') {
        return 'flex gap-3 overflow-x-auto pb-1';
    }

    return 'space-y-3';
}

function carouselItems(block: StageBlock): Array<{ id: string; image: string; description: string }> {
    if (!Array.isArray(block.option_items)) {
        return [];
    }

    return block.option_items
        .map((item, index) => ({
            id: item.id ?? `${block.id}-carousel-${index}`,
            image: safeTrim(item.value) || safeTrim(item.image_url),
            description: safeTrim(item.description),
        }))
        .filter((item) => item.image.length > 0 || item.description.length > 0);
}

function carouselShowsImage(block: StageBlock): boolean {
    return (block.carousel_layout ?? 'image_text') !== 'text_only';
}

function carouselShowsDescription(block: StageBlock): boolean {
    return (block.carousel_layout ?? 'image_text') !== 'image_only';
}

function carouselKey(stageId: number, blockId: string): string {
    return `${stageId}:${blockId}`;
}

function currentCarouselIndex(stageId: number, block: StageBlock): number {
    const items = carouselItems(block);

    if (items.length === 0) {
        return 0;
    }

    if (block.carousel_autoplay) {
        return Math.floor(stageElapsedMilliseconds.value / 3000) % items.length;
    }

    return Math.max(0, Math.min(items.length - 1, manualCarouselIndices.value[carouselKey(stageId, block.id)] ?? 0));
}

function currentCarouselItem(stageId: number, block: StageBlock): { id: string; image: string; description: string } | null {
    const items = carouselItems(block);

    if (items.length === 0) {
        return null;
    }

    return items[currentCarouselIndex(stageId, block)] ?? items[0] ?? null;
}

function setCarouselIndex(stageId: number, block: StageBlock, index: number): void {
    const items = carouselItems(block);

    if (items.length === 0) {
        return;
    }

    manualCarouselIndices.value[carouselKey(stageId, block.id)] = Math.max(0, Math.min(items.length - 1, index));
}

function faqItems(block: StageBlock): Array<{ id: string; label: string; description: string }> {
    if (Array.isArray(block.option_items) && block.option_items.length > 0) {
        return block.option_items.map((item, index) => ({
            id: item.id ?? `${block.id}-faq-${index}`,
            label: item.label?.trim() ?? '',
            description: (item.description ?? item.destination ?? '').trim(),
        }));
    }

    return (block.options ?? []).map((label, index) => ({
        id: `${block.id}-faq-${index}`,
        label: String(label).trim(),
        description: '',
    }));
}

function metricItems(block: StageBlock): Array<{ id: string; label: string; value: string; description: string }> {
    if (Array.isArray(block.option_items) && block.option_items.length > 0) {
        return block.option_items
            .map((item, index) => ({
                id: item.id ?? `${block.id}-metric-${index}`,
                label: item.label?.trim() ?? '',
                value: (item.value ?? '').trim(),
                description: (item.description ?? item.destination ?? '').trim(),
            }))
            .filter((item) => item.label.length > 0 || item.value.length > 0 || item.description.length > 0);
    }

    return (block.options ?? [])
        .map((label, index) => ({
            id: `${block.id}-metric-${index}`,
            label: String(label).trim(),
            value: '',
            description: '',
        }))
        .filter((item) => item.label.length > 0 || item.value.length > 0 || item.description.length > 0);
}

function argumentItems(block: StageBlock): string[] {
    return (block.options ?? [])
        .map((item) => safeTrim(item))
        .filter((item) => item.length > 0);
}

function beforeAfterItems(block: StageBlock): Array<{ label: string; value: string }> {
    return [
        { label: 'Antes', value: safeTrim(block.options?.[0]) },
        { label: 'Depois', value: safeTrim(block.options?.[1]) },
    ].filter((item) => item.value.length > 0);
}

function faqItemKey(stageId: number, blockId: string, index: number): string {
    return `${stageId}:${blockId}:faq:${index}`;
}

function isFaqItemOpen(stageId: number, block: StageBlock, index: number): boolean {
    return openFaqItems.value[faqItemKey(stageId, block.id, index)] ?? ((block.faq_first_active ?? true) && index === 0);
}

function toggleFaqItem(stageId: number, block: StageBlock, index: number): void {
    const key = faqItemKey(stageId, block.id, index);
    openFaqItems.value[key] = !isFaqItemOpen(stageId, block, index);
}

function faqDetailLabel(block: StageBlock, index: number, isOpen = false): string {
    const detail = block.faq_detail ?? 'arrow';

    if (detail === 'none') {
        return '';
    }

    if (detail === 'chevron') {
        return '>';
    }

    if (detail === 'plus_minus') {
        return isOpen ? '-' : '+';
    }

    return isOpen ? '-' : '+';
}

function attentionPaddingClass(block: StageBlock): string {
    if (block.attention_padding === 'compact') {
        return 'px-3.5 py-2.5';
    }

    if (block.attention_padding === 'comfortable') {
        return 'px-5 py-4';
    }

    return 'px-4 py-3';
}

function attentionToneClass(block: StageBlock): string {
    if (block.attention_style === 'amber') {
        return 'border-[#f0dfb1] bg-[#fff4da] text-[#9f6500]';
    }

    if (block.attention_style === 'blue') {
        return 'border-[#c3d8ff] bg-[#eaf2ff] text-[#1f4ea5]';
    }

    return 'border-[#f0c9c9] bg-[#f8dfdf] text-[#c62828]';
}

function attentionHighlightClass(block: StageBlock): string {
    return block.attention_emphasis ? 'ring-1 ring-[#ef9a9a]/70 shadow-[0_0_0_2px_rgba(198,40,40,0.15)]' : '';
}

function notificationToneClass(block: StageBlock): string {
    if (block.notification_style === 'dark') {
        return 'border-[#2f568f] bg-[#0b234d] text-white';
    }

    if (block.notification_style === 'blue') {
        return 'border-[#9fc2ff] bg-[#eaf2ff] text-[#0b2d67]';
    }

    return 'border-[#e7eaf0] bg-white text-[#101828]';
}

function notificationPositionClass(block: StageBlock): string {
    if (block.notification_position === 'top_left') {
        return 'fixed left-4 top-4 z-30 w-[calc(100vw-2rem)]';
    }

    if (block.notification_position === 'top_center') {
        return 'fixed left-1/2 top-4 z-30 w-[calc(100vw-2rem)] -translate-x-1/2';
    }

    if (block.notification_position === 'top_right') {
        return 'fixed right-4 top-4 z-30 w-[calc(100vw-2rem)]';
    }

    if (block.notification_position === 'bottom_left') {
        return 'fixed bottom-4 left-4 z-30 w-[calc(100vw-2rem)]';
    }

    if (block.notification_position === 'bottom_center') {
        return 'fixed bottom-4 left-1/2 z-30 w-[calc(100vw-2rem)] -translate-x-1/2';
    }

    if (block.notification_position === 'bottom_right') {
        return 'fixed bottom-4 right-4 z-30 w-[calc(100vw-2rem)]';
    }

    return '';
}

function notificationSizeClass(block: StageBlock): string {
    if (block.notification_size === 'compact') {
        return 'max-w-[18rem]';
    }

    if (block.notification_size === 'large') {
        return 'max-w-[26rem]';
    }

    return 'max-w-[22rem]';
}

function notificationCardPaddingClass(block: StageBlock): string {
    if (block.notification_size === 'compact') {
        return 'px-3 py-2.5';
    }

    if (block.notification_size === 'large') {
        return 'px-4 py-3.5';
    }

    return 'px-3.5 py-3';
}

function notificationTitleClass(block: StageBlock): string {
    if (block.notification_size === 'compact') {
        return 'text-base';
    }

    if (block.notification_size === 'large') {
        return 'text-xl';
    }

    return 'text-lg';
}

function notificationDescriptionClass(block: StageBlock): string {
    if (block.notification_size === 'compact') {
        return 'text-sm';
    }

    if (block.notification_size === 'large') {
        return 'text-[1.02rem]';
    }

    return 'text-base';
}

function activeNotificationVariation(block: StageBlock): { value1: string; value2: string; value3: string; value4: string } {
    const variations = block.notification_variations ?? [];

    if (variations.length === 0) {
        return { value1: '', value2: '', value3: '', value4: '' };
    }

    const cycleMilliseconds = notificationMotionMetrics(block).cycleMilliseconds;
    const currentIndex = Math.floor(stageElapsedMilliseconds.value / Math.max(1, cycleMilliseconds)) % variations.length;
    const activeVariation = variations[currentIndex] ?? variations[0];

    return {
        value1: activeVariation?.value1 ?? '',
        value2: activeVariation?.value2 ?? '',
        value3: activeVariation?.value3 ?? '',
        value4: activeVariation?.value4 ?? '',
    };
}

function notificationTitleText(block: StageBlock): string {
    return safeTrim(block.notification_title) || safeTrim(block.label);
}

function notificationDescriptionText(block: StageBlock): string {
    return safeTrim(block.notification_description) || safeTrim(block.placeholder);
}

function notificationAvatarUrl(block: StageBlock, variation: { value1: string; value2: string; value3: string; value4: string }): string | null {
    const resolvedUrl = replaceNotificationTokens(safeTrim(block.notification_avatar_url), variation);

    return sanitizeStoredAssetUrl(resolvedUrl);
}

function notificationTimeBadge(block: StageBlock): string {
    const intervalSeconds = Math.max(1, Number(block.notification_interval_seconds ?? 2));

    if (intervalSeconds <= 3) {
        return 'Agora mesmo';
    }

    if (intervalSeconds <= 8) {
        return 'Ha instantes';
    }

    return 'Recente';
}

function notificationAccentClass(block: StageBlock): string {
    if (block.notification_variant === 'offer') {
        return 'text-amber-400';
    }

    if (block.notification_variant === 'message') {
        return 'text-sky-400';
    }

    return 'text-emerald-400';
}

function notificationAvatarShellClass(block: StageBlock): string {
    if (block.notification_variant === 'offer') {
        return 'border-amber-300/25 bg-amber-400/10';
    }

    if (block.notification_variant === 'message') {
        return 'border-sky-300/25 bg-sky-400/10';
    }

    return 'border-emerald-300/25 bg-emerald-400/10';
}

function notificationMotionMetrics(block: StageBlock): {
    cycleMilliseconds: number;
    visibleMilliseconds: number;
    millisecondsIntoCycle: number;
    transitionMilliseconds: number;
    progress: number;
} {
    const visibleMilliseconds = Math.max(1, Number(block.notification_duration_seconds ?? 5)) * 1000;
    const configuredCycleMilliseconds = Math.max(1, Number(block.notification_interval_seconds ?? 2)) * 1000;
    const pauseMilliseconds = Math.max(650, Math.round(visibleMilliseconds * 0.15));
    const cycleMilliseconds = Math.max(visibleMilliseconds + pauseMilliseconds, configuredCycleMilliseconds);
    const millisecondsIntoCycle = cycleMilliseconds > 0 ? stageElapsedMilliseconds.value % cycleMilliseconds : 0;
    const transitionMilliseconds = Math.min(520, Math.max(220, Math.round(visibleMilliseconds * 0.18)));

    if (millisecondsIntoCycle >= visibleMilliseconds) {
        return {
            cycleMilliseconds,
            visibleMilliseconds,
            millisecondsIntoCycle,
            transitionMilliseconds,
            progress: 0,
        };
    }

    if (millisecondsIntoCycle < transitionMilliseconds) {
        return {
            cycleMilliseconds,
            visibleMilliseconds,
            millisecondsIntoCycle,
            transitionMilliseconds,
            progress: Math.max(0, Math.min(1, millisecondsIntoCycle / transitionMilliseconds)),
        };
    }

    const millisecondsUntilExit = visibleMilliseconds - millisecondsIntoCycle;

    if (millisecondsUntilExit < transitionMilliseconds) {
        return {
            cycleMilliseconds,
            visibleMilliseconds,
            millisecondsIntoCycle,
            transitionMilliseconds,
            progress: Math.max(0, Math.min(1, millisecondsUntilExit / transitionMilliseconds)),
        };
    }

    return {
        cycleMilliseconds,
        visibleMilliseconds,
        millisecondsIntoCycle,
        transitionMilliseconds,
        progress: 1,
    };
}

function notificationShellStyle(block: StageBlock): CSSProperties {
    const metrics = notificationMotionMetrics(block);
    const easedProgress = 1 - Math.pow(1 - metrics.progress, 3);
    const position = block.notification_position ?? 'default';
    const offsetDistance = Math.round((1 - easedProgress) * 18);
    const translateX = position.includes('left') ? -offsetDistance : position.includes('right') ? offsetDistance : 0;
    const translateY = position.includes('bottom') ? offsetDistance : -Math.round((1 - easedProgress) * 10);
    const style: CSSProperties = {
        opacity: easedProgress,
        transform: `translate3d(${translateX}px, ${translateY}px, 0) scale(${(0.97 + (easedProgress * 0.03)).toFixed(3)})`,
        filter: `blur(${((1 - easedProgress) * 0.8).toFixed(2)}px)`,
        pointerEvents: easedProgress > 0.08 ? 'auto' : 'none',
        willChange: 'transform, opacity, filter',
    };

    if (position === 'default') {
        style.overflow = 'hidden';
        style.maxHeight = `${Math.round(notificationExpandedHeight(block) * easedProgress)}px`;
        style.marginTop = `${Math.round(10 * easedProgress)}px`;
    }

    return style;
}

function notificationExpandedHeight(block: StageBlock): number {
    if (block.notification_size === 'compact') {
        return 240;
    }

    if (block.notification_size === 'large') {
        return 420;
    }

    return 320;
}

function notificationProgressWidth(block: StageBlock): string {
    const metrics = notificationMotionMetrics(block);

    if (metrics.progress <= 0) {
        return '0%';
    }

    const elapsedWithinDuration = Math.min(metrics.millisecondsIntoCycle, metrics.visibleMilliseconds);
    const remainingRatio = 1 - (elapsedWithinDuration / Math.max(1, metrics.visibleMilliseconds));

    return `${Math.max(0, Math.min(100, Math.round(remainingRatio * 100)))}%`;
}

function replaceNotificationTokens(template: string, values: { value1: string; value2: string; value3: string; value4: string }): string {
    return template
        .replaceAll('@1', values.value1 || '')
        .replaceAll('@2', values.value2 || '')
        .replaceAll('@3', values.value3 || '')
        .replaceAll('@4', values.value4 || '');
}

function parseLegacyTimerSeconds(value: string | null): number {
    const raw = (value ?? '').trim();

    if (raw.length === 0) {
        return 20;
    }

    if (/^\d+$/.test(raw)) {
        return Math.max(0, Number(raw));
    }

    const parts = raw.split(':');
    if (parts.length === 2 && /^\d+$/.test(parts[0]) && /^\d+$/.test(parts[1])) {
        return Math.max(0, Number(parts[0]) * 60 + Number(parts[1]));
    }

    return 20;
}

function formatTimerValue(seconds: number): string {
    const safe = Math.max(0, Math.floor(seconds));
    const minutes = Math.floor(safe / 60);
    const remainingSeconds = safe % 60;

    return `${String(minutes).padStart(2, '0')}:${String(remainingSeconds).padStart(2, '0')}`;
}

function loadingKey(stageId: number, blockId: string): string {
    return `${stageId}:${blockId}:loading`;
}

function initializeStageTimers(): void {
    stageStartedAt.value = Date.now();
    stageElapsedMilliseconds.value = 0;
    loadingStartTimes.value = {};
    triggeredLoadingActions.value = {};
}

function startTimerTicker(): void {
    if (timerFrame !== null) {
        cancelAnimationFrame(timerFrame);
    }

    stageElapsedMilliseconds.value = 0;

    const tick = (): void => {
        stageElapsedMilliseconds.value = Math.max(0, Date.now() - (stageStartedAt.value ?? Date.now()));
        timerFrame = requestAnimationFrame(tick);
    };

    timerFrame = requestAnimationFrame(tick);
}

function timerDisplayText(block: StageBlock): string {
    const template = safeTrim(block.timer_text) || safeTrim(block.placeholder) || (safeTrim(block.label) ? `${safeTrim(block.label)} [time]` : '');
    const current = Math.max(
        0,
        Math.max(0, Number(block.timer_seconds ?? parseLegacyTimerSeconds(block.placeholder))) - Math.floor(stageElapsedMilliseconds.value / 1000),
    );

    if (template.length === 0) {
        return formatTimerValue(current);
    }

    return template.replaceAll('[time]', formatTimerValue(current));
}

function loadingProgress(block: StageBlock): number {
    const start = Math.max(0, Math.min(100, Number(block.loading_start_seconds ?? 0)));
    const duration = Math.max(1, Number(block.loading_duration_seconds ?? 5));
    const stage = currentStage.value;

    if (!stage) {
        return start;
    }

    const startedAt = loadingStartTimes.value[loadingKey(stage.id, block.id)] ?? Date.now();
    const elapsed = Math.max(0, (Date.now() - startedAt) / 1000);
    const progress = start + ((elapsed / duration) * (100 - start));

    return Math.max(start, Math.min(100, Math.round(progress)));
}

function syncVisibleLoadingBlocks(stage: PublicStage | null): void {
    if (!stage) {
        Object.values(loadingAutoNavigateTimeouts).forEach((timeout) => {
            clearTimeout(timeout);
        });
        loadingAutoNavigateTimeouts = {};
        loadingStartTimes.value = {};

        return;
    }

    const nextLoadingStartTimes: Record<string, number> = {};
    const visibleLoadingBlocks = visibleStageBlocks(stage).filter((block) => block.type === 'loading');
    const visibleLoadingKeys = visibleLoadingBlocks.map((block) => loadingKey(stage.id, block.id));

    Object.entries(loadingAutoNavigateTimeouts).forEach(([key, timeout]) => {
        if (visibleLoadingKeys.includes(key)) {
            return;
        }

        clearTimeout(timeout);
        delete loadingAutoNavigateTimeouts[key];
    });

    visibleLoadingBlocks.forEach((block) => {
        const key = loadingKey(stage.id, block.id);
        const startedAt = loadingStartTimes.value[key] ?? Date.now();
        nextLoadingStartTimes[key] = startedAt;

        if (block.loading_navigation_action === 'none' || triggeredLoadingActions.value[key] === true) {
            if (loadingAutoNavigateTimeouts[key]) {
                clearTimeout(loadingAutoNavigateTimeouts[key]);
                delete loadingAutoNavigateTimeouts[key];
            }

            return;
        }

        if (loadingAutoNavigateTimeouts[key]) {
            return;
        }

        const duration = Math.max(1, Number(block.loading_duration_seconds ?? 5));
        const elapsedMs = Math.max(0, Date.now() - startedAt);
        const remainingMs = Math.max(0, duration * 1000 - elapsedMs);

        loadingAutoNavigateTimeouts[key] = setTimeout(() => {
            delete loadingAutoNavigateTimeouts[key];

            const activeStage = currentStage.value;
            if (!activeStage || activeStage.id !== stage.id) {
                return;
            }

            if (!isBlockVisible(block) || triggeredLoadingActions.value[key] === true) {
                return;
            }

            triggeredLoadingActions.value[key] = true;
            handleLoadingNavigation(block);
        }, remainingMs);
    });

    loadingStartTimes.value = nextLoadingStartTimes;
}

function levelProgress(block: StageBlock): number {
    return Math.max(0, Math.min(100, Math.round(Number(block.level_percentage ?? 0))));
}

function levelBarColorClass(block: StageBlock): string {
    if (block.level_color === 'blue') {
        return 'bg-[#2f6fff]';
    }

    if (block.level_color === 'green') {
        return 'bg-[#15a36a]';
    }

    if (block.level_color === 'red') {
        return 'bg-[#d94a4a]';
    }

    return 'bg-[#02081a]';
}

function levelLegends(block: StageBlock): string[] {
    return (block.level_legends ?? '')
        .split(',')
        .map((item) => item.trim())
        .filter((item) => item.length > 0);
}

function spacerHeight(block: StageBlock): number {
    const rawValue = Number(block.placeholder ?? 28);

    if (!Number.isFinite(rawValue)) {
        return 28;
    }

    return Math.max(8, Math.min(240, Math.round(rawValue)));
}

function toggleMultipleChoice(stageId: number, blockId: string, value: string): void {
    const key = answerKey(stageId, blockId);
    const current = answers.value[key];
    const currentValues = Array.isArray(current) ? [...current] : [];
    const index = currentValues.indexOf(value);

    if (index >= 0) {
        currentValues.splice(index, 1);
    } else {
        currentValues.push(value);
    }

    answers.value[key] = currentValues;
}

function isFilled(value: string | string[] | undefined): boolean {
    if (Array.isArray(value)) {
        return value.length > 0;
    }

    return typeof value === 'string' && value.trim() !== '';
}

function buildSubmittedLeadPreview(): { name: string; email: string; phone: string } {
    let name = '';
    let email = '';
    let phone = '';

    for (const stage of props.funnel.stages) {
        for (const block of visibleStageBlocks(stage)) {
            const value = getAnswer(stage.id, block.id);

            if (typeof value !== 'string' || safeTrim(value).length === 0) {
                continue;
            }

            if (email === '' && block.type === 'email') {
                email = safeTrim(value);
            }

            if (phone === '' && block.type === 'phone') {
                phone = safeTrim(value);
            }

            if (
                name === ''
                && block.type === 'text'
                && safeTrim(block.label).toLowerCase().includes('nome')
            ) {
                name = safeTrim(value);
            }
        }
    }

    if (name === '') {
        name = findFirstAnswerByType('text');
    }

    return { name, email, phone };
}

function isOptionsComponentType(type: string): boolean {
    return type === 'options' || type === 'multiple_choice' || type === 'single_choice' || type === 'yes_no';
}

function isAnswerableBlock(type: string): boolean {
    return ['text', 'email', 'phone', 'number', 'textarea', 'video_response', 'date', 'height', 'address', 'weight', 'options', 'multiple_choice', 'single_choice', 'yes_no'].includes(type);
}

function validateStage(stage: PublicStage): boolean {
    const errors: Record<string, string> = {};
    const stageAnswerKeys = stage.blocks.map((block) => answerKey(stage.id, block.id));

    for (const block of visibleStageBlocks(stage)) {
        const isRequired = isOptionsComponentType(block.type) ? (block.options_required_selection ?? block.required) : block.required;

        if (!isRequired || !isAnswerableBlock(block.type)) {
            continue;
        }

        const value = getAnswer(stage.id, block.id);

        if (!isFilled(value)) {
            errors[answerKey(stage.id, block.id)] = 'Campo obrigatorio';
            continue;
        }

        if (block.type === 'email' && typeof value === 'string' && !isValidEmail(value)) {
            errors[answerKey(stage.id, block.id)] = 'E-mail invalido';
        }
    }

    fieldErrors.value = Object.fromEntries(
        Object.entries({
            ...Object.fromEntries(
                Object.entries(fieldErrors.value).filter(([key]) => !stageAnswerKeys.includes(key))
            ),
            ...errors,
        }).filter(([, value]) => safeTrim(value).length > 0)
    );

    return Object.keys(errors).length === 0;
}

function goBack(): void {
    if (stageIndex.value > 0) {
        stageIndex.value -= 1;
    }
}

function goNext(): void {
    if (!currentStage.value) {
        return;
    }

    if (!validateStage(currentStage.value)) {
        return;
    }

    if (!isLastStage.value) {
        stageIndex.value += 1;
    }
}

function submitFunnel(): void {
    if (!currentStage.value || !validateStage(currentStage.value)) {
        return;
    }

    const payload = {
        answers: props.funnel.stages.map((stage) => ({
            stage_id: stage.id,
            blocks: visibleStageBlocks(stage)
                .filter((block) => isAnswerableBlock(block.type))
                .map((block) => ({
                    block_id: block.id,
                    value: getAnswer(stage.id, block.id) ?? null,
                })),
        })),
    };

    submittedLeadPreview.value = buildSubmittedLeadPreview();
    isSubmitting.value = true;

    router.post(props.funnel.submit_url, payload, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            isCompleted.value = true;
            isSubmitting.value = false;
        },
        onError: () => {
            isSubmitting.value = false;
        },
    });
}

watch(currentStage, (stage) => {
    fieldErrors.value = {};

    if (timerFrame !== null) {
        cancelAnimationFrame(timerFrame);
        timerFrame = null;
    }

    if (!stage) {
        return;
    }

    for (const block of stage.blocks) {
        const key = answerKey(stage.id, block.id);

        if (block.type === 'height' && (block.height_mode ?? 'ruler') === 'ruler') {
            if (typeof answers.value[key] !== 'string' || (answers.value[key] as string).trim() === '') {
                answers.value[key] = '170';
            }
        }

        if (block.type === 'weight' && (block.weight_mode ?? 'ruler') === 'ruler') {
            if (typeof answers.value[key] !== 'string' || (answers.value[key] as string).trim() === '') {
                answers.value[key] = '70';
            }
        }

        if (block.type === 'faq') {
            faqItems(block).forEach((_, index) => {
                openFaqItems.value[faqItemKey(stage.id, block.id, index)] = (block.faq_first_active ?? true) && index === 0;
            });
        }
    }

    initializeStageTimers();
    startTimerTicker();
    syncVisibleLoadingBlocks(stage);
}, { immediate: true });

watch(stageElapsedSeconds, () => {
    syncVisibleLoadingBlocks(currentStage.value);
});

watch(answers, () => {
    syncVisibleLoadingBlocks(currentStage.value);
}, { deep: true });

watch(isCompleted, (completed) => {
    if (completionPageRedirectTimeout) {
        clearTimeout(completionPageRedirectTimeout);
        completionPageRedirectTimeout = null;
    }

    if (!completed || !usesCustomCompletionPage.value) {
        return;
    }

    const redirectUrl = safeTrim(completionPage.value.auto_redirect_url);
    const delaySeconds = Math.max(0, Number(completionPage.value.auto_redirect_delay_seconds ?? 0));

    if (redirectUrl.length === 0 || delaySeconds <= 0) {
        return;
    }

    completionPageRedirectTimeout = setTimeout(() => {
        openCompletionPageLink(redirectUrl, false);
    }, delaySeconds * 1000);
});

onBeforeUnmount(() => {
    if (timerFrame !== null) {
        cancelAnimationFrame(timerFrame);
        timerFrame = null;
    }

    Object.values(loadingAutoNavigateTimeouts).forEach((timeout) => {
        clearTimeout(timeout);
    });

    if (completionPageRedirectTimeout) {
        clearTimeout(completionPageRedirectTimeout);
        completionPageRedirectTimeout = null;
    }

    Object.values(audioElements.value).forEach((element) => {
        element?.pause();
    });
});
</script>

<template>
    <Head :title="pageTitle">
        <meta v-if="pageDescription" name="description" :content="pageDescription" />
        <meta property="og:title" :content="pageTitle" />
        <meta v-if="pageDescription" property="og:description" :content="pageDescription" />
        <meta property="og:type" content="website" />
        <meta property="og:url" :content="props.funnel.public_url" />
        <meta v-if="seoImageAssetUrl" property="og:image" :content="seoImageAssetUrl" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="pageTitle" />
        <meta v-if="pageDescription" name="twitter:description" :content="pageDescription" />
        <meta v-if="seoImageAssetUrl" name="twitter:image" :content="seoImageAssetUrl" />
        <link v-if="faviconAssetUrl" rel="icon" :href="faviconAssetUrl" />
        <link rel="canonical" :href="props.funnel.public_url" />
    </Head>

    <div data-funnel-theme class="min-h-screen px-4 py-10" :class="fontClass" :style="funnelThemeStyle">
        <div v-if="isCompleted" data-testid="public-funnel-completed" class="mx-auto max-w-xl rounded-[2rem] border p-8 text-center shadow-[0_26px_60px_rgba(0,0,0,0.38)]" :style="{ backgroundColor: designTokens.surfaces.card, borderColor: designTokens.borders.default }">
            <img
                v-if="usesCustomCompletionPage && completionPageImageUrl"
                :src="completionPageImageUrl"
                alt="Imagem da conclusao"
                class="mx-auto mb-5 max-h-56 w-full rounded-[1.5rem] object-cover"
            />
            <h1 class="text-3xl font-semibold text-white">
                {{ usesCustomCompletionPage ? completionPageTitle : 'Resposta enviada' }}
            </h1>
            <p class="mt-3 text-[#9ebbf0]">
                {{ usesCustomCompletionPage ? completionPageDescription : 'Obrigado. Recebemos suas respostas e em breve entraremos em contato.' }}
            </p>
            <div
                v-if="usesCustomCompletionPage && (
                    shouldRenderCompletionButton(completionPage.primary_button_text, completionPage.primary_button_url)
                    || shouldRenderCompletionButton(completionPage.secondary_button_text, completionPage.secondary_button_url)
                )"
                class="mt-6 flex flex-col gap-3"
            >
                <button
                    v-if="shouldRenderCompletionButton(completionPage.primary_button_text, completionPage.primary_button_url)"
                    data-testid="public-completion-primary-button"
                    type="button"
                    class="inline-flex w-full items-center justify-center rounded-xl bg-gradient-to-r from-[#1f60d4] to-[#4d87ff] px-4 py-3 text-base font-medium text-white transition hover:brightness-110"
                    @click="openCompletionPageLink(completionPage.primary_button_url, completionPage.primary_button_new_tab ?? false)"
                >
                    {{ completionPage.primary_button_text }}
                </button>
                <button
                    v-if="shouldRenderCompletionButton(completionPage.secondary_button_text, completionPage.secondary_button_url)"
                    data-testid="public-completion-secondary-button"
                    type="button"
                    class="inline-flex w-full items-center justify-center rounded-xl border border-[#2f538f] px-4 py-3 text-base font-medium text-[#d8e7ff] transition hover:bg-[#10264d]"
                    @click="openCompletionPageLink(completionPage.secondary_button_url, completionPage.secondary_button_new_tab ?? false)"
                >
                    {{ completionPage.secondary_button_text }}
                </button>
            </div>
        </div>

        <div
            v-else
            data-testid="public-funnel-card"
            class="mx-auto border p-6 shadow-[0_26px_60px_rgba(0,0,0,0.38)]"
            :class="[containerWidthClass, radiusClass, design.alignment === 'left' ? 'mr-auto ml-0' : 'mx-auto']"
            :style="{ backgroundColor: designTokens.surfaces.card, borderColor: designTokens.borders.default }"
        >
            <div class="mb-6">
                <div v-if="currentStageShowLogo" class="mb-2 flex items-center gap-2 text-xs" :style="{ color: designTokens.colors.textMuted }">
                    <img v-if="logoAssetUrl" :src="logoAssetUrl" alt="Logo do funil" class="h-7 w-auto max-w-28 object-contain" />
                    <span v-else>{{ props.funnel.name }}</span>
                </div>
                <button
                    v-if="currentStageAllowBack"
                    type="button"
                    class="mb-2 text-xs text-[#88a8df] disabled:opacity-40"
                    :disabled="stageIndex === 0"
                    @click="goBack"
                >
                    &larr; Voltar
                </button>
                <div v-if="currentStageShowProgress" class="mt-4 h-2 rounded-full" :style="{ backgroundColor: designTokens.surfaces.muted }">
                    <div class="h-2 rounded-full" :style="{ width: `${((stageIndex + 1) / props.funnel.stages.length) * 100}%`, backgroundColor: designTokens.colors.primary }" />
                </div>
            </div>

            <div v-if="currentStage" :class="spacingClass">
                <div v-for="block in visibleCurrentStageBlocks" :key="block.id" class="w-full" :style="publicBlockWrapperStyle(block)">
                    <template v-if="block.type !== 'button' && block.type !== 'spacer' && block.type !== 'content_text' && block.type !== 'arguments' && block.type !== 'metrics' && block.type !== 'testimonials' && block.type !== 'faq' && block.type !== 'price' && block.type !== 'carousel' && block.type !== 'image' && block.type !== 'video' && block.type !== 'audio' && block.type !== 'attention' && block.type !== 'alert' && block.type !== 'notification' && block.type !== 'timer' && block.type !== 'loading' && block.type !== 'level' && !isOptionsComponentType(block.type)">
                        <label v-if="shouldShowPublicLabel(block)" :class="publicLabelClass(block)">{{ block.label }}</label>
                    </template>

                    <div
                        v-if="block.type === 'height' && (block.height_mode ?? 'ruler') === 'ruler'"
                        class="rounded-xl border border-[#2f538f] px-4 py-4"
                        :style="{ backgroundColor: '#0b274f' }"
                    >
                        <div class="mx-auto mb-4 flex w-fit rounded-full bg-[#153568] p-1 text-sm">
                            <button
                                type="button"
                                class="rounded-full px-4 py-1.5"
                                :class="getHeightUnit(currentStage.id, block.id) === 'cm' ? 'bg-[#071733] text-white' : 'text-[#9dbbeb]'"
                                @click="setHeightUnit(currentStage.id, block.id, 'cm')"
                            >
                                cm
                            </button>
                            <button
                                type="button"
                                class="rounded-full px-4 py-1.5"
                                :class="getHeightUnit(currentStage.id, block.id) === 'in' ? 'bg-[#071733] text-white' : 'text-[#9dbbeb]'"
                                @click="setHeightUnit(currentStage.id, block.id, 'in')"
                            >
                                pol
                            </button>
                        </div>

                        <div class="text-center text-5xl font-semibold text-white">
                            {{ getHeightDisplayValue(currentStage.id, block.id) }}<span class="text-3xl">{{ getHeightUnit(currentStage.id, block.id) }}</span>
                        </div>

                        <div class="mt-4 px-1">
                            <input
                                type="range"
                                :min="getHeightUnit(currentStage.id, block.id) === 'cm' ? 120 : 47"
                                :max="getHeightUnit(currentStage.id, block.id) === 'cm' ? 230 : 91"
                                :value="getHeightDisplayValue(currentStage.id, block.id)"
                                class="w-full accent-[#4f8fff]"
                                @input="setHeightFromRuler(currentStage.id, block.id, Number(($event.target as HTMLInputElement).value))"
                            />
                            <div class="mt-2 flex justify-between text-xs text-[#9dbbeb]">
                                <span>{{ getHeightUnit(currentStage.id, block.id) === 'cm' ? 120 : 47 }}</span>
                                <span>{{ getHeightUnit(currentStage.id, block.id) === 'cm' ? 175 : 69 }}</span>
                                <span>{{ getHeightUnit(currentStage.id, block.id) === 'cm' ? 230 : 91 }}</span>
                            </div>
                        </div>
                    </div>

                    <div
                        v-else-if="block.type === 'weight' && (block.weight_mode ?? 'ruler') === 'ruler'"
                        class="rounded-xl border border-[#2f538f] px-4 py-4"
                        :style="{ backgroundColor: '#0b274f' }"
                    >
                        <div class="mx-auto mb-4 flex w-fit rounded-full bg-[#153568] p-1 text-sm">
                            <button
                                type="button"
                                class="rounded-full px-4 py-1.5"
                                :class="getWeightUnit(currentStage.id, block.id) === 'kg' ? 'bg-[#071733] text-white' : 'text-[#9dbbeb]'"
                                @click="setWeightUnit(currentStage.id, block.id, 'kg')"
                            >
                                kg
                            </button>
                            <button
                                type="button"
                                class="rounded-full px-4 py-1.5"
                                :class="getWeightUnit(currentStage.id, block.id) === 'lb' ? 'bg-[#071733] text-white' : 'text-[#9dbbeb]'"
                                @click="setWeightUnit(currentStage.id, block.id, 'lb')"
                            >
                                lb
                            </button>
                        </div>

                        <div class="text-center text-5xl font-semibold text-white">
                            {{ getWeightDisplayValue(currentStage.id, block.id) }}<span class="text-3xl">{{ getWeightUnit(currentStage.id, block.id) }}</span>
                        </div>

                        <div class="mt-4 px-1">
                            <input
                                type="range"
                                :min="getWeightUnit(currentStage.id, block.id) === 'kg' ? 30 : 66"
                                :max="getWeightUnit(currentStage.id, block.id) === 'kg' ? 180 : 397"
                                :value="getWeightDisplayValue(currentStage.id, block.id)"
                                class="w-full accent-[#4f8fff]"
                                @input="setWeightFromRuler(currentStage.id, block.id, Number(($event.target as HTMLInputElement).value))"
                            />
                            <div class="mt-2 flex justify-between text-xs text-[#9dbbeb]">
                                <span>{{ getWeightUnit(currentStage.id, block.id) === 'kg' ? 30 : 66 }}</span>
                                <span>{{ getWeightUnit(currentStage.id, block.id) === 'kg' ? 80 : 176 }}</span>
                                <span>{{ getWeightUnit(currentStage.id, block.id) === 'kg' ? 180 : 397 }}</span>
                            </div>
                        </div>
                    </div>

                    <input
                        v-else-if="['text', 'email', 'phone', 'number', 'date', 'height', 'address', 'weight'].includes(block.type)"
                        :type="fieldInputType(block.type)"
                        :inputmode="fieldInputMode(block.type)"
                        :value="(getAnswer(currentStage.id, block.id) as string | undefined) ?? ''"
                        :placeholder="block.placeholder ?? ''"
                        :autocomplete="block.type === 'email' ? 'email' : 'off'"
                        :autocapitalize="block.type === 'email' ? 'none' : 'sentences'"
                        :spellcheck="block.type === 'email' ? false : true"
                        class="w-full rounded-xl border px-3 py-2.5 outline-none transition focus:border-(--funnel-focus) disabled:opacity-(--funnel-disabled-opacity)"
                        :style="{ backgroundColor: designTokens.components.fieldBackground, borderColor: designTokens.borders.default, color: designTokens.components.fieldText }"
                        @input="setMaskedTextAnswer(currentStage.id, block, ($event.target as HTMLInputElement).value)"
                    />

                    <textarea
                        v-else-if="block.type === 'textarea' || block.type === 'video_response'"
                        :value="(getAnswer(currentStage.id, block.id) as string | undefined) ?? ''"
                        :placeholder="block.placeholder ?? ''"
                        rows="3"
                        class="w-full rounded-xl border px-3 py-2.5 outline-none transition focus:border-(--funnel-focus) disabled:opacity-(--funnel-disabled-opacity)"
                        :style="{ backgroundColor: designTokens.components.fieldBackground, borderColor: designTokens.borders.default, color: designTokens.components.fieldText }"
                        @input="setTextAnswer(currentStage.id, block.id, ($event.target as HTMLTextAreaElement).value)"
                    />

                    <div
                        v-else-if="block.type === 'content_text' && hasContentTextContent(block)"
                        :data-testid="`public-content-text-${block.id}`"
                        class="px-1 py-1 text-[#dce8ff] [&_h1]:text-3xl [&_h1]:font-bold [&_h1]:leading-tight [&_h2]:text-2xl [&_h2]:font-bold [&_h2]:leading-tight [&_h3]:text-xl [&_h3]:font-semibold [&_h3]:leading-tight [&_p]:mt-2 [&_p]:text-sm [&_p]:leading-relaxed [&_p]:text-[#9cc1ff] [&_ul]:mt-2 [&_ul]:list-disc [&_ul]:space-y-1 [&_ul]:pl-5 [&_a]:text-[#9fc2ff] [&_a]:underline"
                        :class="contentTextAlignClass(block)"
                        v-html="contentTextMarkup(block)"
                    ></div>

                    <div
                        v-else-if="block.type === 'image'"
                        class="border"
                        :class="[imageFrameClass(block), imageRadiusClass(block)]"
                    >
                        <div v-if="imageAspectClass(block)" :class="['w-full overflow-hidden', imageAspectClass(block), imageRadiusClass(block)]">
                            <img
                                v-if="sanitizeStoredAssetUrl(block.placeholder)"
                                :src="sanitizeStoredAssetUrl(block.placeholder) ?? undefined"
                                alt="Imagem do funil"
                                :class="['h-full w-full', imageFitClass(block), imageRadiusClass(block)]"
                            />
                            <div v-else class="flex h-full items-center justify-center border border-dashed border-[#2f538f] text-sm text-[#c8ddff]" :class="imageRadiusClass(block)">
                                Imagem nao configurada
                            </div>
                        </div>
                        <template v-else>
                            <img
                                v-if="sanitizeStoredAssetUrl(block.placeholder)"
                                :src="sanitizeStoredAssetUrl(block.placeholder) ?? undefined"
                                alt="Imagem do funil"
                                :class="['max-h-[22rem] w-full', imageFitClass(block), imageRadiusClass(block)]"
                            />
                            <div v-else class="flex h-40 items-center justify-center border border-dashed border-[#2f538f] text-sm text-[#c8ddff]" :class="imageRadiusClass(block)">
                                Imagem nao configurada
                            </div>
                        </template>
                    </div>

                    <div
                        v-else-if="block.type === 'video'"
                        class="rounded-xl border border-[#2f538f] p-2"
                        :style="{ backgroundColor: '#0b274f' }"
                    >
                        <div v-if="toEmbedVideoUrl(block.placeholder)" :class="['overflow-hidden rounded-lg border border-[#2f538f]', getVideoAspectClass(block)]">
                            <iframe
                                :src="toEmbedVideoUrl(block.placeholder) ?? undefined"
                                title="Video do funil"
                                class="h-full w-full"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                allowfullscreen
                            />
                        </div>
                        <div v-else class="flex h-40 items-center justify-center rounded-lg border border-dashed border-[#2f538f] text-sm text-[#c8ddff]">
                            Video nao configurado
                        </div>
                    </div>

                    <div
                        v-else-if="block.type === 'audio'"
                        class="relative rounded-[26px] border p-3"
                        :class="(block.audio_theme ?? 'light') === 'dark' ? 'border-[#274573] bg-[#0b1f42]' : 'border-[#e6e1d8] bg-[#efebe5]'"
                        :style="(block.audio_theme ?? 'light') === 'dark'
                            ? {}
                            : {
                                backgroundImage: 'radial-gradient(circle at 10px 10px, rgba(255,255,255,0.35) 1px, transparent 1px)',
                                backgroundSize: '18px 18px',
                            }"
                    >
                        <span
                            class="absolute left-[15px] top-[26px] h-3 w-3 rotate-45"
                            :class="(block.audio_theme ?? 'light') === 'dark' ? 'border-l border-t border-[#33598d] bg-[#102a56]' : 'border-l border-t border-[#ece7df] bg-white'"
                        />
                        <div
                            class="relative rounded-[22px] border px-3 py-3 shadow-[0_1px_0_rgba(0,0,0,0.06)]"
                            :class="(block.audio_theme ?? 'light') === 'dark' ? 'border-[#33598d] bg-[#102a56]' : 'border-[#ece7df] bg-white'"
                        >
                            <audio
                                :ref="(element) => bindAudioElement(audioPreviewKey(currentStage.id, block.id), element as HTMLAudioElement | null)"
                                :src="audioSource(block) ?? undefined"
                                preload="metadata"
                                class="hidden"
                                @loadedmetadata="onAudioLoadedMetadata(audioPreviewKey(currentStage.id, block.id), $event)"
                                @timeupdate="onAudioTimeUpdate(audioPreviewKey(currentStage.id, block.id), $event)"
                                @play="onAudioPlay(audioPreviewKey(currentStage.id, block.id))"
                                @pause="onAudioPause(audioPreviewKey(currentStage.id, block.id))"
                                @ended="onAudioEnded(audioPreviewKey(currentStage.id, block.id))"
                            />
                            <div class="flex items-center gap-3">
                                <button
                                    type="button"
                                    :data-testid="`public-audio-toggle-${block.id}`"
                                    class="grid h-9 w-9 shrink-0 place-items-center rounded-full transition disabled:opacity-50"
                                    :disabled="!audioSource(block)"
                                    @click="toggleAudioPlayback(audioPreviewKey(currentStage.id, block.id))"
                                >
                                    <template v-if="activeAudioKey === audioPreviewKey(currentStage.id, block.id)">
                                        <span class="flex items-center gap-1">
                                            <span class="h-4 w-1 rounded-full" :class="(block.audio_theme ?? 'light') === 'dark' ? 'bg-[#b8cbe8]' : 'bg-[#9f9393]'" />
                                            <span class="h-4 w-1 rounded-full" :class="(block.audio_theme ?? 'light') === 'dark' ? 'bg-[#b8cbe8]' : 'bg-[#9f9393]'" />
                                        </span>
                                    </template>
                                    <span v-else class="ml-0.5 inline-block h-0 w-0 border-y-[8px] border-y-transparent border-l-[12px]" :class="(block.audio_theme ?? 'light') === 'dark' ? 'border-l-[#b8cbe8]' : 'border-l-[#9f9393]'" />
                                </button>
                                <div class="min-w-0 flex-1">
                                    <div
                                        :data-testid="`public-audio-seek-${block.id}`"
                                        class="relative cursor-pointer outline-none"
                                        role="slider"
                                        tabindex="0"
                                        :aria-valuemin="0"
                                        :aria-valuemax="100"
                                        :aria-valuenow="Math.round(audioProgressRatio(audioPreviewKey(currentStage.id, block.id)) * 100)"
                                        @click="seekAudio(audioPreviewKey(currentStage.id, block.id), $event)"
                                        @keydown="handleAudioKeyboard(audioPreviewKey(currentStage.id, block.id), $event)"
                                    >
                                        <div class="flex items-center gap-[3px]">
                                            <span class="mr-1 inline-block h-3.5 w-3.5 rounded-full bg-[#58baf1]" />
                                            <span
                                                v-for="(barHeight, barIndex) in audioWaveHeights"
                                                :key="`public-audio-wave-${block.id}-${barIndex}`"
                                                class="inline-block w-[3px] rounded-full transition-colors"
                                                :class="barIndex / audioWaveHeights.length < audioProgressRatio(audioPreviewKey(currentStage.id, block.id))
                                                    ? 'bg-[#58baf1]'
                                                    : (block.audio_theme ?? 'light') === 'dark'
                                                        ? 'bg-[#8ea3c5]/65'
                                                        : 'bg-[#d5d6d8]'"
                                                :style="{ height: `${barHeight}px` }"
                                            />
                                        </div>
                                    </div>
                                    <div class="mt-2 flex items-center justify-between pl-0.5 text-[11px] font-medium leading-none tracking-wide" :class="(block.audio_theme ?? 'light') === 'dark' ? 'text-[#bfd4f4]' : 'text-[#7a8ea9]'">
                                        <p :data-testid="`public-audio-current-${block.id}`">{{ displayedAudioCurrentTime(audioPreviewKey(currentStage.id, block.id)) }}</p>
                                        <p :data-testid="`public-audio-duration-${block.id}`">{{ displayedAudioDuration(audioPreviewKey(currentStage.id, block.id)) }}</p>
                                    </div>
                                </div>
                                <div class="relative h-10 w-10 shrink-0 overflow-hidden rounded-full border" :class="(block.audio_theme ?? 'light') === 'dark' ? 'border-[#4f6f9f] bg-[#cad3df]' : 'border-[#d8dde3] bg-[#d2d9e2]'">
                                    <img
                                        v-if="sanitizeStoredAssetUrl(block.audio_avatar_url)"
                                        :src="sanitizeStoredAssetUrl(block.audio_avatar_url) ?? undefined"
                                        alt="Avatar do audio"
                                        class="h-full w-full object-cover"
                                    />
                                    <template v-else>
                                        <div class="absolute left-1/2 top-[6px] h-[10px] w-[10px] -translate-x-1/2 rounded-full bg-[#edf1f6]" />
                                        <div class="absolute left-1/2 top-[17px] h-[14px] w-[22px] -translate-x-1/2 rounded-[999px_999px_8px_8px] bg-[#edf1f6]" />
                                    </template>
                                    <div class="absolute bottom-[1px] left-[1px] grid h-[15px] w-[15px] place-items-center rounded-full bg-white shadow-sm">
                                        <span class="block h-[7px] w-[4px] rounded-[999px] border-2 border-b-0 border-[#39b6f3]" />
                                        <span class="absolute bottom-[4px] h-[3px] w-[2px] bg-[#39b6f3]" />
                                        <span class="absolute bottom-[2px] h-[2px] w-[6px] rounded bg-[#39b6f3]" />
                                    </div>
                                </div>
                            </div>
                            <p v-if="safeTrim(block.audio_sender).length" class="pt-2 text-[9px] uppercase tracking-[0.08em]" :class="(block.audio_theme ?? 'light') === 'dark' ? 'text-[#9fb8dd]' : 'text-[#9ca7b7]'">
                                {{ block.audio_sender }}
                            </p>
                            <p
                                v-if="!audioSource(block)"
                                class="pt-2 text-[10px]"
                                :class="(block.audio_theme ?? 'light') === 'dark' ? 'text-[#86a2cf]' : 'text-[#8793a5]'"
                            >
                                Audio nao configurado
                            </p>
                        </div>
                    </div>

                    <div
                        v-else-if="block.type === 'attention' || block.type === 'alert'"
                        class="rounded-[20px] border text-center text-base leading-normal md:text-lg"
                        :class="[attentionToneClass(block), attentionPaddingClass(block), attentionHighlightClass(block)]"
                    >
                        {{ block.placeholder }}
                    </div>

                    <div
                        v-else-if="block.type === 'notification'"
                        :data-testid="`public-notification-${block.id}`"
                        class="w-full"
                        :class="[notificationPositionClass(block), notificationSizeClass(block)]"
                        :style="notificationShellStyle(block)"
                    >
                        <div class="rounded-[22px] border shadow-[0_16px_38px_rgba(9,18,36,0.22)] backdrop-blur-sm" :class="[notificationToneClass(block), notificationCardPaddingClass(block)]">
                            <div class="flex items-center justify-between gap-3 text-[10px] uppercase tracking-[0.22em] opacity-65">
                                <span class="inline-flex items-center gap-2" />
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="inline-block h-1.5 w-1.5 rounded-full" :class="notificationAccentClass(block)" />
                                    {{ notificationTimeBadge(block) }}
                                </span>
                            </div>
                            <div class="mt-2 flex items-start gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full border" :class="notificationAvatarShellClass(block)">
                                    <img
                                        v-if="notificationAvatarUrl(block, activeNotificationVariation(block))"
                                        :src="notificationAvatarUrl(block, activeNotificationVariation(block)) ?? undefined"
                                        alt="Avatar da notificacao"
                                        class="h-full w-full object-cover"
                                    />
                                    <span v-else class="inline-block h-3 w-3 rounded-full" :class="notificationAccentClass(block)" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p v-if="notificationTitleText(block).length" class="font-semibold leading-tight" :class="notificationTitleClass(block)">
                                        {{ replaceNotificationTokens(notificationTitleText(block), activeNotificationVariation(block)) }}
                                    </p>
                                    <p v-if="notificationDescriptionText(block).length" class="leading-snug opacity-80" :class="[notificationDescriptionClass(block), notificationTitleText(block).length ? 'mt-1.5' : 'mt-0.5']">
                                        {{ replaceNotificationTokens(notificationDescriptionText(block), activeNotificationVariation(block)) }}
                                    </p>
                                </div>
                            </div>
                            <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-black/10">
                                <div class="h-full rounded-full bg-black/20 transition-[width] duration-300" :style="{ width: notificationProgressWidth(block) }" />
                            </div>
                        </div>
                    </div>

                    <div
                        v-else-if="block.type === 'timer'"
                        class="rounded-2xl border px-3 py-2 text-center text-base leading-tight"
                        :class="block.timer_style === 'amber'
                            ? 'border-[#f0dfb1] bg-[#fff4da] text-[#9f6500]'
                            : block.timer_style === 'blue'
                                ? 'border-[#c3d8ff] bg-[#eaf2ff] text-[#1f4ea5]'
                                : 'border-[#f0c9c9] bg-[#f8dfdf] text-[#c62828]'"
                    >
                        {{ timerDisplayText(block) }}
                    </div>

                    <div
                        v-else-if="block.type === 'loading'"
                        class="rounded-xl border border-[#e7eaf0] bg-white px-2.5 py-2 text-[#0b1020]"
                    >
                        <div v-if="block.loading_show_title !== false || block.loading_show_progress !== false" class="flex items-center justify-between">
                            <p v-if="block.loading_show_title !== false && block.label?.trim().length" class="text-sm font-semibold">{{ block.label }}</p>
                            <p v-if="block.loading_show_progress !== false" class="text-sm font-semibold text-[#7a7f8a]">{{ loadingProgress(block) }}%</p>
                        </div>
                        <div v-if="block.loading_show_progress !== false" class="mt-1 h-2.5 overflow-hidden rounded-full bg-[#e3e6ed]">
                            <div class="h-full rounded-full bg-[#030a1b] transition-all duration-300" :style="{ width: `${loadingProgress(block)}%` }" />
                        </div>
                        <p v-if="block.placeholder?.trim().length" class="mt-2 text-center text-[1.15rem] leading-snug text-[#576a84]">
                            {{ block.placeholder }}
                        </p>
                    </div>

                    <div
                        v-else-if="block.type === 'level'"
                        :data-testid="`public-level-${block.id}`"
                        class="rounded-xl border border-[#e7eaf0] bg-white px-3 py-2.5 text-[#0b1020]"
                    >
                        <div class="flex items-end justify-between gap-2">
                            <div class="min-w-0">
                                <p v-if="block.level_title?.trim().length" class="text-lg font-semibold">{{ block.level_title }}</p>
                                <p v-if="block.level_subtitle?.trim().length" :data-testid="`public-level-subtitle-${block.id}`" class="text-[1.35rem] leading-tight text-[#576a84]">{{ block.level_subtitle }}</p>
                            </div>
                            <p v-if="block.level_show_progress !== false" class="text-[1.35rem] font-semibold text-[#7a7f8a]">{{ levelProgress(block) }}%</p>
                        </div>
                        <div class="mt-1.5 h-2.5 overflow-hidden rounded-full bg-[#e3e6ed]">
                            <div class="relative h-full rounded-full transition-all duration-300" :class="levelBarColorClass(block)" :style="{ width: `${levelProgress(block)}%` }">
                                <span
                                    v-if="block.level_show_meter !== false"
                                    class="absolute right-0 top-1/2 h-4 w-4 translate-x-1/2 -translate-y-1/2 rounded-full border-[4px] border-[#d8dce3] bg-[#f8f9fb]"
                                />
                            </div>
                        </div>
                        <p v-if="block.level_indicator_text?.trim().length" class="mt-2 text-center text-sm text-[#576a84]">{{ block.level_indicator_text }}</p>
                        <p v-if="levelLegends(block).length" class="mt-1.5 text-center text-xs text-[#7a7f8a]">{{ levelLegends(block).join(' | ') }}</p>
                    </div>

                    <div v-else-if="block.type === 'testimonials'" :class="testimonialListClass(block)">
                        <article
                            v-for="item in testimonialItems(block)"
                            :key="`${block.id}-testimonial-${item.id}`"
                            class="border border-[#d7dde8] bg-white text-[#1f2a3d]"
                            :class="[
                                optionsCardRadiusClass(block),
                                'px-2.5 py-2',
                                optionsCardShadowClass(block),
                                block.testimonials_layout === 'slide' ? 'min-w-[220px]' : '',
                            ]"
                        >
                            <div class="flex items-center gap-1 text-amber-500"><span v-for="starIndex in ratingStars(item.rating)" :key="`${item.id}-star-${starIndex}`">&#9733;</span></div>
                            <p v-if="item.label.length" class="mt-0.5 text-base font-semibold text-[#08152b]">{{ item.label }}</p>
                            <p v-if="item.subtitle.length" class="text-sm text-[#5f6f85]">{{ item.subtitle }}</p>
                            <p v-if="item.description.length" class="mt-1.5 text-base leading-6 text-[#54637a]">{{ item.description }}</p>
                        </article>
                    </div>

                    <div v-else-if="block.type === 'faq'" class="space-y-1">
                        <article
                            v-for="(item, faqIndex) in faqItems(block)"
                            :key="`${block.id}-faq-${item.id}`"
                            class="border-b border-[#d7dde8] py-1.5"
                        >
                            <button :data-testid="`faq-toggle-${block.id}-${faqIndex}`" type="button" class="flex w-full items-center justify-between gap-3 text-left" @click="toggleFaqItem(currentStage.id, block, faqIndex)">
                                <p v-if="item.label.length" class="text-sm font-semibold text-[#08152b]">{{ item.label }}</p>
                                <span v-if="(block.faq_detail ?? 'arrow') !== 'none'" class="text-xs font-semibold text-[#8fb45c]">{{ faqDetailLabel(block, faqIndex, isFaqItemOpen(currentStage.id, block, faqIndex)) }}</span>
                            </button>
                            <p v-if="isFaqItemOpen(currentStage.id, block, faqIndex) && item.description.length" class="mt-1 text-sm leading-6 text-[#54637a]">{{ item.description }}</p>
                        </article>
                    </div>

                    <div v-else-if="block.type === 'metrics' && metricItems(block).length" :data-testid="`public-metrics-${block.id}`" class="grid gap-2 sm:grid-cols-3">
                        <article
                            v-for="item in metricItems(block)"
                            :key="`${block.id}-metric-${item.id}`"
                            :data-testid="`public-metric-${block.id}-${item.id}`"
                            class="rounded-2xl border border-[#d7dde8] bg-white px-3 py-3 text-[#08152b]"
                        >
                            <p v-if="item.value.length" class="text-[1.55rem] font-semibold leading-none">{{ item.value }}</p>
                            <p v-if="item.label.length" class="text-sm font-semibold" :class="item.value.length ? 'mt-2' : ''">{{ item.label }}</p>
                            <p v-if="item.description.length" class="text-xs leading-5 text-[#607089]" :class="item.value.length || item.label.length ? 'mt-1' : ''">{{ item.description }}</p>
                        </article>
                    </div>

                    <div v-else-if="block.type === 'before_after'" class="grid gap-2 sm:grid-cols-2">
                        <article
                            v-for="item in beforeAfterItems(block)"
                            :key="`${block.id}-before-after-${item.label}`"
                            class="rounded-xl border border-[#d7dde8] bg-white px-3 py-2.5 text-[#08152b]"
                        >
                            <p class="text-xs uppercase tracking-wide text-[#6f8bb8]">{{ item.label }}</p>
                            <p class="mt-1 text-sm leading-6 text-[#54637a]">{{ item.value }}</p>
                        </article>
                    </div>

                    <ul v-else-if="block.type === 'arguments'" class="space-y-2">
                        <li
                            v-for="(option, optionIndex) in argumentItems(block)"
                            :key="`${block.id}-argument-${optionIndex}`"
                            class="flex items-center gap-2 rounded-xl border border-[#d7dde8] bg-white px-3 py-2 text-[#08152b]"
                        >
                            <span class="text-[#4f8fff]">*</span>
                            <span class="text-sm leading-6 text-[#1f2a3d]">{{ option }}</span>
                        </li>
                    </ul>

                    <button
                        v-else-if="block.type === 'price'"
                        type="button"
                        class="w-full rounded-xl border px-2.5 py-1.5 text-left transition"
                        :class="block.price_style === 'dark'
                            ? 'border-[#355d9f] bg-[#0b274f] text-white'
                            : block.price_style === 'light'
                                ? 'border-[#d5deed] bg-[#f7f9fe] text-[#0d1a31]'
                                : 'border-[#6faa2a] bg-[#eef8e5] text-[#0d1a31]'"
                        :disabled="(block.price_mode ?? 'illustrative') === 'redirect' && !safeTrim(block.price_link).length"
                        :title="(block.price_mode ?? 'illustrative') === 'redirect' ? 'Abrir link do plano' : undefined"
                        :style="{ cursor: (block.price_mode ?? 'illustrative') === 'redirect' ? 'pointer' : 'default' }"
                        @click="handlePriceClick(block)"
                    >
                        <div :class="(block.price_layout ?? 'horizontal') === 'vertical' ? 'space-y-2' : 'flex items-center justify-between gap-3'">
                            <p v-if="block.price_title?.trim().length" class="text-lg font-semibold">{{ block.price_title }}</p>
                            <div class="rounded-lg px-2 py-1" :class="block.price_style === 'dark' ? 'bg-[#13386f]' : 'bg-[#e9edf3]'">
                                <p v-if="block.price_prefix?.trim().length" class="text-xs text-[#5f6875]">{{ block.price_prefix }}</p>
                                <p v-if="block.price_value?.trim().length" class="text-xl font-semibold" :class="block.price_style === 'dark' ? 'text-white' : 'text-[#0a1224]'">{{ block.price_value }}</p>
                                <p v-if="block.price_suffix?.trim().length" class="text-sm text-[#5f6875]">{{ block.price_suffix }}</p>
                            </div>
                        </div>
                        <p v-if="block.price_badge_text?.trim().length" class="mt-1 text-xs font-medium text-[#4f8a19]">{{ block.price_badge_text }}</p>
                    </button>

                    <div
                        v-else-if="block.type === 'carousel' && carouselItems(block).length"
                        :data-testid="`public-carousel-${block.id}`"
                        class="rounded-xl border p-2"
                        :class="block.carousel_border_type === 'strong'
                            ? 'border-[#d7dde8] bg-white'
                            : block.carousel_border_type === 'subtle'
                                ? 'border-[#e7ecf5] bg-[#fbfcff]'
                                : 'border-transparent bg-transparent'"
                    >
                        <div v-if="carouselShowsImage(block) && safeTrim(currentCarouselItem(currentStage.id, block)?.image).length" :data-testid="`public-carousel-media-${block.id}`" class="rounded-2xl bg-[#bfd3b2] p-1">
                            <div class="aspect-[4/3] w-full rounded-2xl bg-[#bfd3b2]">
                                <img
                                    v-if="safeTrim(currentCarouselItem(currentStage.id, block)?.image).length"
                                    :src="sanitizeStoredAssetUrl(currentCarouselItem(currentStage.id, block)?.image) ?? undefined"
                                    alt="Imagem do slide"
                                    class="h-full w-full rounded-2xl object-cover"
                                />
                            </div>
                        </div>
                        <p v-if="carouselShowsDescription(block) && safeTrim(currentCarouselItem(currentStage.id, block)?.description).length" class="mt-2 text-center text-lg text-[#6b7689]">
                            {{ currentCarouselItem(currentStage.id, block)?.description }}
                        </p>
                        <div v-if="block.carousel_pagination !== false && carouselItems(block).length > 1" class="mt-2 flex items-center justify-center gap-2">
                            <button
                                v-for="(item, itemIndex) in carouselItems(block)"
                                :key="`${block.id}-carousel-dot-${item.id}`"
                                :data-testid="`carousel-dot-${block.id}-${itemIndex}`"
                                type="button"
                                class="rounded-full transition"
                                :class="itemIndex === currentCarouselIndex(currentStage.id, block) ? 'h-2.5 w-2.5 bg-[#6faa2a]' : 'h-1.5 w-1.5 bg-[#b4c39e]'"
                                @click="setCarouselIndex(currentStage.id, block, itemIndex)"
                            />
                        </div>
                    </div>


                    <div v-else-if="isOptionsComponentType(block.type)" :class="optionsListClass(block)">
                        <div
                            v-if="hasOptionsIntroContent(block)"
                            :data-testid="`public-options-intro-${block.id}`"
                            class="mb-2 border sm:col-span-2"
                            :class="[optionsCardRadiusClass(block), optionsCardSpacingClass(block), optionsCardToneClass(block, false), optionsCardShadowClass(block)]"
                        >
                            <p v-if="(block.options_intro_title ?? '').trim().length" class="text-center text-xl font-semibold text-white">{{ block.options_intro_title }}</p>
                            <p v-if="(block.options_intro_description ?? '').trim().length" class="text-center text-sm text-[#c8ddff]" :class="(block.options_intro_title ?? '').trim().length ? 'mt-2' : ''">{{ block.options_intro_description }}</p>
                        </div>
                        <button
                            v-for="(optionItem, optionIndex) in optionsDisplayItems(block)"
                            :key="`${optionItem.label}-${optionIndex}`"
                            type="button"
                            class="border"
                            :class="[
                                optionsItemWidthClass(block),
                                optionsCardRadiusClass(block),
                                optionsCardSpacingClass(block),
                                optionsCardShadowClass(block),
                                optionsCardMinWidthClass(block),
                                optionsCardToneClass(
                                    block,
                                    Array.isArray(getAnswer(currentStage.id, block.id))
                                        ? (getAnswer(currentStage.id, block.id) as string[]).includes(optionItem.label)
                                        : getAnswer(currentStage.id, block.id) === optionItem.label,
                                ),
                            ]"
                            @click="handleOptionSelection(currentStage.id, block, optionItem.label, optionItem.destination)"
                        >
                            <div :class="optionsBodyClass(block)">
                                <span
                                    v-if="normalizeDetailValue(block.options_detail) !== 'none'"
                                    :data-testid="`option-detail-${block.id}-${optionIndex}`"
                                    class="inline-flex shrink-0 items-center justify-center text-xs font-semibold text-[#1b2333]"
                                    :class="[
                                        optionsDetailWrapClass(block),
                                        optionsDetailBadgeClass(block),
                                        normalizeDetailValue(block.options_detail) !== 'checkout' ? 'bg-[#d2d8e4]' : '',
                                        optionsDetailTextClass(block),
                                    ]"
                                >
                                    {{ optionsDetailLabel(block, optionItem, optionIndex) }}
                                </span>
                                <div
                                    v-if="optionsShouldRenderImage(block, optionItem)"
                                    :data-testid="`option-image-${block.id}-${optionIndex}`"
                                    class="shrink-0"
                                    :class="optionsMediaOrderClass(block)"
                                >
                                    <div :class="optionsImageWrapClass(block)">
                                        <img
                                            :src="sanitizeStoredAssetUrl(optionItem.image_url) ?? undefined"
                                            alt="Imagem da opcao"
                                            class="h-full w-full object-cover"
                                        />
                                    </div>
                                </div>
                                <span
                                    class="block min-w-0 flex-1 text-white"
                                    :class="[optionTextAlignClass(block), optionsLabelOrderClass(block), block.options_layout === 'grid_1' ? 'text-[1.02rem] leading-7' : 'leading-6']"
                                >
                                    {{ optionItem.label }}
                                </span>
                            </div>
                        </button>
                    </div>

                    <button
                        v-else-if="block.type === 'button'"
                        :data-testid="`public-block-button-${block.id}`"
                        type="button"
                        class="inline-flex w-full items-center justify-center rounded-xl border border-transparent py-3 text-lg font-medium transition disabled:opacity-(--funnel-disabled-opacity)"
                        :class="[
                            block.button_action === 'open_link' && !block.button_link ? 'opacity-90' : '',
                            block.button_color_style === 'dark' ? 'bg-[#06183a] text-white' : '',
                            block.button_color_style === 'light' ? 'bg-[#d8e7ff] text-[#082252]' : '',
                            block.button_animated ? 'hover:translate-y-[-1px] hover:brightness-110' : '',
                            block.button_elevated ? 'shadow-[0_10px_24px_rgba(20,86,193,0.45)]' : '',
                            block.button_sticky_footer ? 'sticky bottom-3 z-[1]' : '',
                        ]"
                        :style="publicThemeButtonStyle(block)"
                        @click="handleBlockButtonClick(block)"
                    >
                        {{ block.label }}
                    </button>

                    <div
                        v-else-if="block.type === 'spacer'"
                        :data-testid="`public-spacer-${block.id}`"
                        class="w-full rounded-lg border border-dashed border-[#2f538f]/70 bg-[#0b274f]/40"
                        :style="{ height: `${spacerHeight(block)}px` }"
                    />

                    <p v-if="fieldErrors[answerKey(currentStage.id, block.id)]" class="mt-1 text-xs" :style="{ color: designTokens.states.danger }">
                        {{ fieldErrors[answerKey(currentStage.id, block.id)] }}
                    </p>
                </div>
            </div>

        </div>
    </div>
</template>

