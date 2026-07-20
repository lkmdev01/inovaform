<script setup lang="ts">
import type { FormDataConvertible } from '@inertiajs/core';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowUp,
    Blend,
    BookOpen,
    Check,
    CircleUserRound,
    Eye,
    FormInput,
    ListTree,
    LoaderCircle,
    MoreVertical,
    Palette,
    Plus,
    Redo2,
    Save,
    Settings,
    Share2,
    Sparkles,
    Trash2,
    Undo2,
} from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import type { ComponentPublicInstance } from 'vue';
import FunnelController from '@/actions/App/Http/Controllers/FunnelController';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import { sanitizeStoredAssetUrl } from '@/lib/media';
import { show as showPublicFunnel } from '@/routes/funnels/public';

type StageHeaderSettings = {
    show_logo: boolean;
    show_progress: boolean;
    allow_back: boolean;
};

type StageBlockType =
    | 'text'
    | 'email'
    | 'phone'
    | 'button'
    | 'number'
    | 'textarea'
    | 'date'
    | 'height'
    | 'address'
    | 'weight'
    | 'options'
    | 'multiple_choice'
    | 'single_choice'
    | 'yes_no'
    | 'video_response'
    | 'content_text'
    | 'image'
    | 'video'
    | 'audio'
    | 'attention'
    | 'alert'
    | 'notification'
    | 'timer'
    | 'loading'
    | 'level'
    | 'arguments'
    | 'testimonials'
    | 'faq'
    | 'price'
    | 'before_after'
    | 'carousel'
    | 'metrics'
    | 'spacer';

type OptionItem = {
    id: string;
    label: string;
    points: number;
    value: string;
    destination: string;
    image_url?: string;
    subtitle?: string;
    description?: string;
    rating?: number;
};

type NotificationVariation = {
    id: string;
    value1: string;
    value2: string;
    value3: string;
    value4: string;
};

type DisplayRule = {
    id: string;
    source_block_id: string;
    operator:
        | 'filled'
        | 'empty'
        | 'equals'
        | 'not_equals'
        | 'contains_any'
        | 'contains_all';
    value: string;
};

type DisplayRuleGroup = {
    id: string;
    mode: 'all' | 'any';
    rules: DisplayRule[];
};

type StageBlock = {
    id: string;
    type: StageBlockType;
    label: string;
    placeholder?: string;
    variable_name?: string | null;
    required: boolean;
    options?: string[];
    option_items?: OptionItem[];
    options_intro_type?: 'text' | 'none';
    options_intro_title?: string;
    options_intro_description?: string;
    options_required_selection?: boolean;
    options_allow_multiple?: boolean;
    options_disable_auto_follow?: boolean;
    options_style?: 'simple' | 'highlight' | 'relief' | 'contrast' | 'cards';
    options_transparent_image?: boolean;
    options_layout?: 'grid_2' | 'grid_1';
    options_orientation?: 'vertical' | 'horizontal';
    options_image_ratio?: '1:1' | '4:3' | '16:9';
    options_disposition?: 'image_text' | 'text_image' | 'text';
    options_detail?: 'checkout' | 'arrow' | 'points' | 'value' | 'none';
    options_detail_position?: 'start' | 'end';
    options_border_size?: 'small' | 'medium' | 'large';
    options_shadow?: 'none' | 'soft' | 'strong';
    options_spacing?: 'simple' | 'comfortable' | 'compact';
    testimonials_layout?: 'list' | 'slide' | 'grid';
    faq_first_active?: boolean;
    faq_detail?: 'arrow' | 'chevron' | 'plus_minus' | 'none';
    price_title?: string;
    price_prefix?: string;
    price_value?: string;
    price_suffix?: string;
    price_badge_text?: string;
    price_mode?: 'illustrative' | 'redirect';
    price_layout?: 'horizontal' | 'vertical';
    price_style?: 'theme' | 'light' | 'dark';
    price_link?: string;
    carousel_layout?: 'image_text' | 'image_only' | 'text_only';
    carousel_pagination?: boolean;
    carousel_autoplay?: boolean;
    carousel_autoplay_seconds?: number;
    carousel_border_type?: 'none' | 'subtle' | 'strong';
    image_ratio?: 'auto' | '16:9' | '4:3' | '1:1';
    image_fit?: 'cover' | 'contain';
    image_radius?: 'none' | 'small' | 'medium' | 'large' | 'full';
    image_frame?: 'none' | 'subtle' | 'strong';
    video_ratio?: '16:9' | '4:3' | '1:1';
    audio_sender?: string;
    audio_src?: string;
    audio_avatar_url?: string;
    audio_model?: 'whatsapp';
    audio_theme?: 'light' | 'dark';
    attention_style?: 'red' | 'amber' | 'blue';
    attention_emphasis?: boolean;
    attention_padding?: 'compact' | 'default' | 'comfortable';
    notification_title?: string;
    notification_description?: string;
    notification_avatar_url?: string;
    notification_position?:
        | 'default'
        | 'top_left'
        | 'top_center'
        | 'top_right'
        | 'bottom_left'
        | 'bottom_center'
        | 'bottom_right';
    notification_duration_seconds?: number;
    notification_interval_seconds?: number;
    notification_style?: 'white' | 'dark' | 'blue';
    notification_size?: 'compact' | 'default' | 'large';
    notification_variant?: 'social' | 'offer' | 'message';
    notification_variations?: NotificationVariation[];
    timer_seconds?: number;
    timer_text?: string;
    timer_style?: 'red' | 'amber' | 'blue';
    loading_start_seconds?: number;
    loading_duration_seconds?: number;
    loading_navigation_action?: 'next_stage' | 'open_link' | 'none';
    loading_target_stage_order?: string | null;
    loading_link?: string;
    loading_show_title?: boolean;
    loading_show_progress?: boolean;
    level_title?: string;
    level_subtitle?: string;
    level_percentage?: number;
    level_indicator_text?: string;
    level_legends?: string;
    level_show_meter?: boolean;
    level_show_progress?: boolean;
    level_type?: 'line';
    level_color?: 'theme' | 'blue' | 'green' | 'red';
    phone_mask?: 'br' | 'us' | 'eu';
    number_mask?: 'decimal' | 'real' | 'dollar' | 'euro';
    height_mode?: 'ruler' | 'input';
    weight_mode?: 'ruler' | 'input';
    button_action?: 'next_stage' | 'open_link';
    button_target_stage_order?: string | null;
    button_link?: string;
    button_open_new_tab?: boolean;
    button_color_style?: 'theme' | 'dark' | 'light';
    button_animated?: boolean;
    button_elevated?: boolean;
    button_sticky_footer?: boolean;
    label_style?: 'default' | 'muted' | 'hidden';
    text_align?: 'text-left' | 'text-center' | 'text-right';
    width_percent?: number;
    align_horizontal?: 'start' | 'center' | 'end';
    align_vertical?: 'start' | 'center' | 'end';
    show_after_seconds?: number | null;
    display_rule_mode?: 'all' | 'any';
    display_rules?: DisplayRule[];
    display_rule_groups?: DisplayRuleGroup[];
};

type FunnelStage = {
    id: number;
    name: string;
    stage_order: number;
    conversion_rate: string | null;
    expected_volume: number | null;
    meta: {
        header?: StageHeaderSettings;
        builder?: {
            title?: string;
            subtitle?: string;
            button_text?: string;
            stage_button_action?: 'next_stage' | 'open_link';
            stage_button_target_stage_order?: string | null;
            stage_button_link?: string;
            stage_button_open_new_tab?: boolean;
            stage_button_color_style?: 'theme' | 'dark' | 'light';
            stage_button_animated?: boolean;
            stage_button_elevated?: boolean;
            stage_button_sticky_footer?: boolean;
            blocks?: StageBlock[];
        };
    } | null;
};

type Funnel = {
    id: number;
    slug: string;
    name: string;
    description: string | null;
    target_leads: number | null;
    is_active: boolean;
    design_settings?: {
        aiGeneration?: {
            objective_summary?: string;
            rationale?: string;
            stage_plan?: Array<{
                name?: string;
                purpose?: string;
            }>;
            quality_score?: number;
            quality_notes?: string[];
            correction_applied?: boolean;
        };
        importMedia?: {
            status?:
                'queued' | 'processing' | 'completed' | 'partial' | 'failed';
            total?: number;
            imported?: number;
            failed?: number;
        };
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
    } | null;
    stages: FunnelStage[];
};

type StageDraft = {
    id: number | null;
    clientId: string;
    name: string;
    conversion_rate: string;
    expected_volume: string;
    header: StageHeaderSettings;
    title: string;
    subtitle: string;
    buttonText: string;
    stageButtonAction: 'next_stage' | 'open_link';
    stageButtonTargetStageOrder: string | null;
    stageButtonLink: string;
    stageButtonOpenNewTab: boolean;
    stageButtonColorStyle: 'theme' | 'dark' | 'light';
    stageButtonAnimated: boolean;
    stageButtonElevated: boolean;
    stageButtonStickyFooter: boolean;
    blocks: StageBlock[];
};

type BuilderStateSnapshot = {
    funnelName: string;
    selectedStageKey: string;
    stages: StageDraft[];
};

const props = defineProps<{
    funnel: Funnel;
    permissions: {
        canEdit: boolean;
        canManageLeads: boolean;
    };
}>();

const page = usePage<{ flash?: { status?: string } }>();

const defaultStageTitle = '';
const defaultStageSubtitle = '';
const defaultButtonText = '';
const defaultChoiceOptions = ['Opcao 1', 'Opcao 2', 'Opcao 3'];
const phoneMaskOptions: Array<{ value: 'br' | 'us' | 'eu'; label: string }> = [
    { value: 'br', label: '(99) 99999-9999 (Brasil)' },
    { value: 'us', label: '(999) 999-9999 (Estados Unidos)' },
    { value: 'eu', label: '+00 000 000 0000 (Europa)' },
];
const numberMaskOptions: Array<{
    value: 'decimal' | 'real' | 'dollar' | 'euro';
    label: string;
}> = [
    { value: 'decimal', label: 'Decimal' },
    { value: 'real', label: 'Real (R$)' },
    { value: 'dollar', label: 'Dolar ($)' },
    { value: 'euro', label: 'Euro (€)' },
];
const videoRatioOptions: Array<{
    value: '16:9' | '4:3' | '1:1';
    label: string;
}> = [
    { value: '16:9', label: '16:9 (Padrao)' },
    { value: '4:3', label: '4:3' },
    { value: '1:1', label: '1:1' },
];
const audioModelOptions: Array<{ value: 'whatsapp'; label: string }> = [
    { value: 'whatsapp', label: 'Whatsapp' },
];
const audioThemeOptions: Array<{ value: 'light' | 'dark'; label: string }> = [
    { value: 'light', label: 'Claro' },
    { value: 'dark', label: 'Escuro' },
];
const attentionStyleOptions: Array<{
    value: 'red' | 'amber' | 'blue';
    label: string;
}> = [
    { value: 'red', label: 'Vermelho' },
    { value: 'amber', label: 'Amarelo' },
    { value: 'blue', label: 'Azul' },
];
const attentionPaddingOptions: Array<{
    value: 'compact' | 'default' | 'comfortable';
    label: string;
}> = [
    { value: 'compact', label: 'Compacta' },
    { value: 'default', label: 'Padrao' },
    { value: 'comfortable', label: 'Confortavel' },
];

const notificationStyleOptions: Array<{
    value: 'white' | 'dark' | 'blue';
    label: string;
}> = [
    { value: 'white', label: 'Branco' },
    { value: 'dark', label: 'Escuro' },
    { value: 'blue', label: 'Azul' },
];
const timerStyleOptions: Array<{
    value: 'red' | 'amber' | 'blue';
    label: string;
}> = [
    { value: 'red', label: 'Vermelho' },
    { value: 'amber', label: 'Amarelo' },
    { value: 'blue', label: 'Azul' },
];
const levelColorOptions: Array<{
    value: 'theme' | 'blue' | 'green' | 'red';
    label: string;
}> = [
    { value: 'theme', label: 'Tema' },
    { value: 'blue', label: 'Azul' },
    { value: 'green', label: 'Verde' },
    { value: 'red', label: 'Vermelho' },
];

const blockCatalog: Array<{
    type: StageBlockType;
    label: string;
    category: 'form' | 'quiz' | 'media' | 'argument' | 'personalization';
}> = [
    { type: 'text', label: 'Campo', category: 'form' },
    { type: 'email', label: 'E-mail', category: 'form' },
    { type: 'phone', label: 'Telefone', category: 'form' },
    { type: 'button', label: 'Botao', category: 'form' },
    { type: 'number', label: 'Numero', category: 'form' },
    { type: 'textarea', label: 'Texto longo', category: 'form' },
    { type: 'date', label: 'Data', category: 'form' },
    { type: 'height', label: 'Altura', category: 'form' },
    { type: 'address', label: 'Endereco', category: 'form' },
    { type: 'weight', label: 'Peso', category: 'form' },
    { type: 'options', label: 'Opcoes', category: 'quiz' },
    { type: 'multiple_choice', label: 'Multipla Escolha', category: 'quiz' },
    { type: 'single_choice', label: 'Escolha Unica', category: 'quiz' },
    { type: 'yes_no', label: 'Sim/Nao', category: 'quiz' },
    { type: 'video_response', label: 'Video Resposta', category: 'quiz' },
    { type: 'content_text', label: 'Texto', category: 'media' },
    { type: 'image', label: 'Imagem', category: 'media' },
    { type: 'video', label: 'Video', category: 'media' },
    { type: 'audio', label: 'Audio', category: 'media' },
    { type: 'attention', label: 'Alerta', category: 'media' },
    { type: 'notification', label: 'Notificacao', category: 'media' },
    { type: 'timer', label: 'Temporizador', category: 'media' },
    { type: 'loading', label: 'Carregamento', category: 'media' },
    { type: 'level', label: 'Nivel', category: 'media' },
    { type: 'arguments', label: 'Argumentos', category: 'argument' },
    { type: 'testimonials', label: 'Depoimentos', category: 'argument' },
    { type: 'faq', label: 'FAQ', category: 'argument' },
    { type: 'price', label: 'Preco', category: 'argument' },
    { type: 'before_after', label: 'Antes/Depois', category: 'argument' },
    { type: 'carousel', label: 'Carrossel', category: 'argument' },
    { type: 'metrics', label: 'Metricas', category: 'argument' },
    { type: 'spacer', label: 'Espaco', category: 'personalization' },
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

function normalizeLegacyBlockType(type: StageBlockType): StageBlockType {
    if (type === 'alert') {
        return 'attention';
    }

    return type;
}

function createOptionItems(labels: string[]): OptionItem[] {
    return labels.map((label, index) => ({
        id: createClientId(),
        label,
        points: index === 0 ? 1 : 0,
        value: String.fromCharCode(65 + (index % 26)),
        destination: 'next_stage',
        image_url: '',
    }));
}

function createBlankOptionItems(count = 1): OptionItem[] {
    return Array.from({ length: count }, (_, index) => ({
        id: createClientId(),
        label: '',
        points: 0,
        value: String.fromCharCode(65 + (index % 26)),
        destination: 'next_stage',
        image_url: '',
    }));
}

function normalizeCarouselItems(
    sourceItems: OptionItem[] | undefined,
    sourceOptions: string[] | undefined,
): OptionItem[] {
    if (Array.isArray(sourceItems) && sourceItems.length > 0) {
        return sourceItems.map((item) => {
            const imageUrl = safeTrim(item.value) || safeTrim(item.image_url);

            return {
                id: item.id ?? createClientId(),
                label: item.label ?? '',
                value: imageUrl,
                image_url: imageUrl,
                description:
                    item.description ?? item.destination ?? item.label ?? '',
                points: 0,
                destination: item.description ?? item.destination ?? '',
            };
        });
    }

    if (Array.isArray(sourceOptions) && sourceOptions.length > 0) {
        return sourceOptions.map((option) => ({
            id: createClientId(),
            label: '',
            value: '',
            image_url: '',
            description: String(option).trim(),
            points: 0,
            destination: '',
        }));
    }

    return [];
}

function normalizeMetricItems(
    sourceItems: OptionItem[] | undefined,
    sourceOptions: string[] | undefined,
): OptionItem[] {
    if (Array.isArray(sourceItems) && sourceItems.length > 0) {
        return sourceItems.map((item) => ({
            id: item.id ?? createClientId(),
            label: item.label ?? '',
            value: item.value ?? '',
            description: item.description ?? item.destination ?? '',
            points: Number.isFinite(Number(item.points))
                ? Number(item.points)
                : 0,
            destination: item.destination ?? item.description ?? '',
        }));
    }

    if (Array.isArray(sourceOptions) && sourceOptions.length > 0) {
        return sourceOptions.map((option) => ({
            id: createClientId(),
            label: String(option).trim(),
            value: '',
            description: '',
            points: 0,
            destination: '',
        }));
    }

    return [];
}

function normalizeFaqItems(
    sourceItems: OptionItem[] | undefined,
    sourceOptions: string[] | undefined,
): OptionItem[] {
    if (Array.isArray(sourceItems) && sourceItems.length > 0) {
        return sourceItems.map((item) => ({
            id: item.id ?? createClientId(),
            label: item.label ?? '',
            description: item.description ?? item.destination ?? '',
            points: 0,
            value: '',
            destination: item.description ?? item.destination ?? '',
        }));
    }

    if (Array.isArray(sourceOptions) && sourceOptions.length > 0) {
        return sourceOptions.map((rawOption) => ({
            id: createClientId(),
            label: String(rawOption).trim(),
            description: '',
            points: 0,
            value: '',
            destination: '',
        }));
    }

    return [];
}

function normalizeTestimonialItems(
    sourceItems: OptionItem[] | undefined,
    sourceOptions: string[] | undefined,
): OptionItem[] {
    if (Array.isArray(sourceItems) && sourceItems.length > 0) {
        return sourceItems.map((item) => {
            const handle = item.subtitle ?? item.value ?? '';
            const description = item.description ?? item.destination ?? '';
            const parsedRating = Number(item.rating ?? item.points ?? 5);

            return {
                id: item.id ?? createClientId(),
                label: item.label ?? '',
                subtitle: handle,
                description,
                rating: Math.max(
                    1,
                    Math.min(
                        5,
                        Math.round(
                            Number.isFinite(parsedRating) ? parsedRating : 5,
                        ),
                    ),
                ),
                points: Math.max(
                    1,
                    Math.min(
                        5,
                        Math.round(
                            Number.isFinite(parsedRating) ? parsedRating : 5,
                        ),
                    ),
                ),
                value: handle,
                destination: description,
            };
        });
    }

    if (Array.isArray(sourceOptions) && sourceOptions.length > 0) {
        return sourceOptions.map((rawOption) => {
            const segments = String(rawOption).split('|');
            const name = (segments[0] ?? '').trim();
            const handle = (segments[1] ?? '').trim();
            const rating = Number((segments[2] ?? '5').trim());
            const description = (segments.slice(3).join('|') ?? '').trim();

            return {
                id: createClientId(),
                label: name,
                subtitle: handle,
                description,
                rating: Math.max(
                    1,
                    Math.min(
                        5,
                        Math.round(Number.isFinite(rating) ? rating : 5),
                    ),
                ),
                points: Math.max(
                    1,
                    Math.min(
                        5,
                        Math.round(Number.isFinite(rating) ? rating : 5),
                    ),
                ),
                value: handle,
                destination: description,
            };
        });
    }

    return [];
}

function createNotificationVariations(
    withSampleValues = false,
): NotificationVariation[] {
    return [
        {
            id: createClientId(),
            value1: withSampleValues ? 'Joao' : '',
            value2: withSampleValues ? 'Instagram' : '',
            value3: withSampleValues ? '3' : '',
            value4: withSampleValues
                ? 'https://cdn.example.com/avatar-joao.png'
                : '',
        },
    ];
}

function defaultNotificationTitle(): string {
    return '@1';
}

function defaultNotificationDescription(): string {
    return 'Comprou agora. Origem: @2. Restam @3 vagas.';
}

function defaultNotificationAvatarUrl(): string {
    return '@4';
}

function replaceNotificationTokens(
    template: string,
    variation?: NotificationVariation,
): string {
    const activeVariation = variation ?? createNotificationVariations()[0];

    return template
        .replaceAll('@1', activeVariation.value1 || '')
        .replaceAll('@2', activeVariation.value2 || '')
        .replaceAll('@3', activeVariation.value3 || '')
        .replaceAll('@4', activeVariation.value4 || '');
}

function notificationTitleText(block: StageBlock): string {
    return safeTrim(block.notification_title) || safeTrim(block.label);
}

function notificationDescriptionText(block: StageBlock): string {
    return (
        safeTrim(block.notification_description) || safeTrim(block.placeholder)
    );
}

function notificationFieldContainsToken(value: string | undefined): boolean {
    return /@(?:1|2|3|4)/.test(safeTrim(value));
}

function notificationUsesVariationTokens(block: StageBlock): boolean {
    return (
        notificationFieldContainsToken(block.notification_title) ||
        notificationFieldContainsToken(block.notification_description) ||
        notificationFieldContainsToken(block.notification_avatar_url)
    );
}

function notificationHasFilledVariations(block: StageBlock): boolean {
    return (block.notification_variations ?? []).some((variation) => {
        return (
            safeTrim(variation.value1) !== '' ||
            safeTrim(variation.value2) !== '' ||
            safeTrim(variation.value3) !== '' ||
            safeTrim(variation.value4) !== ''
        );
    });
}

function insertNotificationToken(
    field: 'title' | 'description',
    token: '@1' | '@2' | '@3' | '@4',
): void {
    if (
        !props.permissions.canEdit ||
        !selectedBlock.value ||
        selectedBlock.value.type !== 'notification'
    ) {
        return;
    }

    if (field === 'title') {
        selectedBlock.value.notification_title = `${selectedBlock.value.notification_title ?? ''}${token}`;

        return;
    }

    selectedBlock.value.notification_description = `${selectedBlock.value.notification_description ?? ''}${token}`;
}

function insertNotificationAvatarToken(token: '@1' | '@2' | '@3' | '@4'): void {
    if (
        !props.permissions.canEdit ||
        !selectedBlock.value ||
        selectedBlock.value.type !== 'notification'
    ) {
        return;
    }

    selectedBlock.value.notification_avatar_url = `${selectedBlock.value.notification_avatar_url ?? ''}${token}`;
}

function notificationAvatarUrl(
    block: StageBlock,
    variation?: NotificationVariation,
): string | null {
    const resolvedUrl = replaceNotificationTokens(
        safeTrim(block.notification_avatar_url),
        variation,
    );

    return sanitizeStoredAssetUrl(resolvedUrl);
}

function notificationTimeBadge(block: StageBlock): string {
    const intervalSeconds = Math.max(
        1,
        Number(block.notification_interval_seconds ?? 2),
    );

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

function formatTimerValue(seconds: number): string {
    const safe = Math.max(0, Math.floor(seconds));
    const minutes = Math.floor(safe / 60);
    const remainingSeconds = safe % 60;

    return `${String(minutes).padStart(2, '0')}:${String(remainingSeconds).padStart(2, '0')}`;
}

function parseLegacyTimerSeconds(value: string | undefined): number {
    const raw = (value ?? '').trim();

    if (raw.length === 0) {
        return 20;
    }

    if (/^\d+$/.test(raw)) {
        return Math.max(0, Number(raw));
    }

    const parts = raw.split(':');
    if (
        parts.length === 2 &&
        /^\d+$/.test(parts[0]) &&
        /^\d+$/.test(parts[1])
    ) {
        return Math.max(0, Number(parts[0]) * 60 + Number(parts[1]));
    }

    return 20;
}

function timerDisplayText(block: StageBlock): string {
    const template =
        safeTrim(block.timer_text) ||
        safeTrim(block.placeholder) ||
        (safeTrim(block.label) ? `${safeTrim(block.label)} [time]` : '');
    const seconds = Number(
        block.timer_seconds ?? parseLegacyTimerSeconds(block.placeholder),
    );
    const timeToken = formatTimerValue(seconds);

    if (template.length === 0) {
        return timeToken;
    }

    return template.replaceAll('[time]', timeToken);
}

function loadingPreviewProgress(block: StageBlock): number {
    return Math.max(
        0,
        Math.min(100, Math.round(Number(block.loading_start_seconds ?? 0))),
    );
}

function notificationHasFloatingPosition(block: StageBlock): boolean {
    return (block.notification_position ?? 'default') !== 'default';
}

function builderNotificationPreviewAlignmentClass(block: StageBlock): string {
    if (block.notification_position === 'top_center') {
        return 'items-start justify-center';
    }

    if (block.notification_position === 'top_right') {
        return 'items-start justify-end';
    }

    if (block.notification_position === 'bottom_left') {
        return 'items-end justify-start';
    }

    if (block.notification_position === 'bottom_center') {
        return 'items-end justify-center';
    }

    if (block.notification_position === 'bottom_right') {
        return 'items-end justify-end';
    }

    return 'items-start justify-start';
}

function builderNotificationPreviewShellClass(block: StageBlock): string {
    if (!notificationHasFloatingPosition(block)) {
        return 'w-full';
    }

    return 'flex min-h-[10rem] w-full items-stretch overflow-hidden rounded-[22px] border border-dashed border-[#3562a7] bg-[#081a39]/75 p-3';
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

function levelProgress(block: StageBlock): number {
    return Math.max(
        0,
        Math.min(100, Math.round(Number(block.level_percentage ?? 0))),
    );
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

function defaultOptionsLabels(type: StageBlockType): string[] {
    if (type === 'yes_no') {
        return ['\u2705 Sim', '\u{1F6AB} Nao'];
    }

    return [...defaultChoiceOptions];
}

function normalizeYesNoOptionLabel(label: string, index: number): string {
    const normalized = label
        .trim()
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');

    if (normalized === 'sim' || normalized === '\u2705 sim') {
        return '\u2705 Sim';
    }

    if (normalized === 'nao' || normalized === '\u{1F6AB} nao') {
        return '\u{1F6AB} Nao';
    }

    return index === 0 ? '\u2705 Sim' : '\u{1F6AB} Nao';
}

function defaultOptionsDetail(type: StageBlockType): 'checkout' | 'none' {
    return type === 'yes_no' ? 'none' : 'checkout';
}

function normalizeDetailValue(
    value: StageBlock['options_detail'],
): 'checkout' | 'arrow' | 'points' | 'value' | 'none' {
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

const localStages = ref<StageDraft[]>(
    props.funnel.stages
        .slice()
        .sort((first, second) => first.stage_order - second.stage_order)
        .map((stage) => ({
            id: stage.id,
            clientId: createClientId(),
            name: stage.name,
            conversion_rate: stage.conversion_rate ?? '',
            expected_volume: stage.expected_volume?.toString() ?? '',
            header: {
                show_logo: stage.meta?.header?.show_logo ?? true,
                show_progress: stage.meta?.header?.show_progress ?? true,
                allow_back: stage.meta?.header?.allow_back ?? true,
            },
            title: stage.meta?.builder?.title ?? defaultStageTitle,
            subtitle: stage.meta?.builder?.subtitle ?? defaultStageSubtitle,
            buttonText: stage.meta?.builder?.button_text ?? defaultButtonText,
            stageButtonAction:
                stage.meta?.builder?.stage_button_action ?? 'next_stage',
            stageButtonTargetStageOrder:
                stage.meta?.builder?.stage_button_target_stage_order ?? 'next',
            stageButtonLink: stage.meta?.builder?.stage_button_link ?? '',
            stageButtonOpenNewTab:
                stage.meta?.builder?.stage_button_open_new_tab ?? false,
            stageButtonColorStyle:
                stage.meta?.builder?.stage_button_color_style ?? 'theme',
            stageButtonAnimated:
                stage.meta?.builder?.stage_button_animated ?? false,
            stageButtonElevated:
                stage.meta?.builder?.stage_button_elevated ?? false,
            stageButtonStickyFooter:
                stage.meta?.builder?.stage_button_sticky_footer ?? false,
            blocks:
                stage.meta?.builder?.blocks?.map((block) => ({
                    id: block.id ?? createClientId(),
                    type: normalizeLegacyBlockType(block.type),
                    label: block.label,
                    placeholder: block.placeholder,
                    variable_name: block.variable_name,
                    required: block.required ?? false,
                    options: block.options,
                    option_items: isOptionsComponentType(block.type)
                        ? (block.option_items?.length ?? 0) > 0
                            ? (block.option_items ?? []).map((item, index) => ({
                                  id: item.id ?? createClientId(),
                                  label: item.label ?? '',
                                  points: Number.isFinite(Number(item.points))
                                      ? Number(item.points)
                                      : index + 1,
                                  value:
                                      item.value ??
                                      String.fromCharCode(65 + (index % 26)),
                                  destination: item.destination ?? 'next_stage',
                                  image_url: item.image_url ?? '',
                              }))
                            : createOptionItems(
                                  block.options ??
                                      defaultOptionsLabels(block.type),
                              )
                        : block.type === 'testimonials'
                          ? normalizeTestimonialItems(
                                block.option_items,
                                block.options,
                            )
                          : block.type === 'faq'
                            ? normalizeFaqItems(
                                  block.option_items,
                                  block.options,
                              )
                            : block.type === 'carousel'
                              ? normalizeCarouselItems(
                                    block.option_items,
                                    block.options,
                                )
                              : block.type === 'metrics'
                                ? normalizeMetricItems(
                                      block.option_items,
                                      block.options,
                                  )
                                : undefined,
                    options_intro_type: isOptionsComponentType(block.type)
                        ? (block.options_intro_type ?? 'text')
                        : undefined,
                    options_intro_title: isOptionsComponentType(block.type)
                        ? (block.options_intro_title ?? '')
                        : undefined,
                    options_intro_description: isOptionsComponentType(
                        block.type,
                    )
                        ? (block.options_intro_description ?? '')
                        : undefined,
                    options_required_selection: isOptionsComponentType(
                        block.type,
                    )
                        ? (block.options_required_selection ?? true)
                        : undefined,
                    options_allow_multiple: isOptionsComponentType(block.type)
                        ? (block.options_allow_multiple ??
                          defaultOptionsAllowMultiple(block.type))
                        : undefined,
                    options_disable_auto_follow: isOptionsComponentType(
                        block.type,
                    )
                        ? (block.options_disable_auto_follow ?? false)
                        : undefined,
                    options_style: isOptionsComponentType(block.type)
                        ? (block.options_style ?? 'simple')
                        : undefined,
                    options_transparent_image: isOptionsComponentType(
                        block.type,
                    )
                        ? (block.options_transparent_image ?? true)
                        : undefined,
                    options_layout: isOptionsComponentType(block.type)
                        ? (block.options_layout ?? 'grid_2')
                        : undefined,
                    options_orientation: isOptionsComponentType(block.type)
                        ? (block.options_orientation ?? 'vertical')
                        : undefined,
                    options_image_ratio: isOptionsComponentType(block.type)
                        ? (block.options_image_ratio ?? '1:1')
                        : undefined,
                    options_disposition: isOptionsComponentType(block.type)
                        ? (block.options_disposition ?? 'image_text')
                        : undefined,
                    options_detail: isOptionsComponentType(block.type)
                        ? block.options_detail
                            ? normalizeDetailValue(block.options_detail)
                            : defaultOptionsDetail(block.type)
                        : undefined,
                    options_detail_position: isOptionsComponentType(block.type)
                        ? (block.options_detail_position ?? 'start')
                        : undefined,
                    options_border_size:
                        isOptionsComponentType(block.type) ||
                        block.type === 'testimonials'
                            ? (block.options_border_size ?? 'small')
                            : undefined,
                    options_shadow:
                        isOptionsComponentType(block.type) ||
                        block.type === 'testimonials'
                            ? (block.options_shadow ?? 'none')
                            : undefined,
                    options_spacing:
                        isOptionsComponentType(block.type) ||
                        block.type === 'testimonials'
                            ? (block.options_spacing ?? 'simple')
                            : undefined,
                    testimonials_layout:
                        block.type === 'testimonials'
                            ? (block.testimonials_layout ?? 'list')
                            : undefined,
                    faq_first_active:
                        block.type === 'faq'
                            ? (block.faq_first_active ?? true)
                            : undefined,
                    faq_detail:
                        block.type === 'faq'
                            ? (block.faq_detail ?? 'arrow')
                            : undefined,
                    price_title:
                        block.type === 'price'
                            ? (block.price_title ?? block.label ?? '')
                            : undefined,
                    price_prefix:
                        block.type === 'price'
                            ? (block.price_prefix ?? '')
                            : undefined,
                    price_value:
                        block.type === 'price'
                            ? (block.price_value ?? '')
                            : undefined,
                    price_suffix:
                        block.type === 'price'
                            ? (block.price_suffix ?? '')
                            : undefined,
                    price_badge_text:
                        block.type === 'price'
                            ? (block.price_badge_text ?? '')
                            : undefined,
                    price_mode:
                        block.type === 'price'
                            ? (block.price_mode ?? 'illustrative')
                            : undefined,
                    price_layout:
                        block.type === 'price'
                            ? (block.price_layout ?? 'horizontal')
                            : undefined,
                    price_style:
                        block.type === 'price'
                            ? (block.price_style ?? 'theme')
                            : undefined,
                    price_link:
                        block.type === 'price'
                            ? (block.price_link ?? '')
                            : undefined,
                    carousel_layout:
                        block.type === 'carousel'
                            ? (block.carousel_layout ?? 'image_text')
                            : undefined,
                    carousel_pagination:
                        block.type === 'carousel'
                            ? (block.carousel_pagination ?? true)
                            : undefined,
                    carousel_autoplay:
                        block.type === 'carousel'
                            ? (block.carousel_autoplay ?? false)
                            : undefined,
                    carousel_autoplay_seconds:
                        block.type === 'carousel'
                            ? Math.max(
                                  1,
                                  Math.min(
                                      60,
                                      Number(
                                          block.carousel_autoplay_seconds ?? 3,
                                      ),
                                  ),
                              )
                            : undefined,
                    carousel_border_type:
                        block.type === 'carousel'
                            ? (block.carousel_border_type ?? 'none')
                            : undefined,
                    image_ratio:
                        block.type === 'image'
                            ? (block.image_ratio ?? 'auto')
                            : undefined,
                    image_fit:
                        block.type === 'image'
                            ? (block.image_fit ?? 'cover')
                            : undefined,
                    image_radius:
                        block.type === 'image'
                            ? (block.image_radius ?? 'medium')
                            : undefined,
                    image_frame:
                        block.type === 'image'
                            ? (block.image_frame ?? 'subtle')
                            : undefined,
                    video_ratio:
                        block.type === 'video'
                            ? (block.video_ratio ?? '16:9')
                            : undefined,
                    audio_sender:
                        block.type === 'audio'
                            ? (block.audio_sender ?? '')
                            : undefined,
                    audio_src:
                        block.type === 'audio'
                            ? (block.audio_src ?? '')
                            : undefined,
                    audio_avatar_url:
                        block.type === 'audio'
                            ? (block.audio_avatar_url ?? '')
                            : undefined,
                    audio_model:
                        block.type === 'audio'
                            ? (block.audio_model ?? 'whatsapp')
                            : undefined,
                    audio_theme:
                        block.type === 'audio'
                            ? (block.audio_theme ?? 'light')
                            : undefined,
                    attention_style:
                        block.type === 'attention'
                            ? (block.attention_style ?? 'red')
                            : undefined,
                    attention_emphasis:
                        block.type === 'attention'
                            ? (block.attention_emphasis ?? false)
                            : undefined,
                    attention_padding:
                        block.type === 'attention'
                            ? (block.attention_padding ?? 'default')
                            : undefined,
                    notification_title:
                        block.type === 'notification'
                            ? (block.notification_title ?? '')
                            : undefined,
                    notification_description:
                        block.type === 'notification'
                            ? (block.notification_description ?? '')
                            : undefined,
                    notification_avatar_url:
                        block.type === 'notification'
                            ? (block.notification_avatar_url ?? '')
                            : undefined,
                    notification_position:
                        block.type === 'notification'
                            ? (block.notification_position ?? 'default')
                            : undefined,
                    notification_duration_seconds:
                        block.type === 'notification'
                            ? Number(block.notification_duration_seconds ?? 5)
                            : undefined,
                    notification_interval_seconds:
                        block.type === 'notification'
                            ? Number(block.notification_interval_seconds ?? 2)
                            : undefined,
                    notification_style:
                        block.type === 'notification'
                            ? (block.notification_style ?? 'white')
                            : undefined,
                    notification_size:
                        block.type === 'notification'
                            ? (block.notification_size ?? 'default')
                            : undefined,
                    notification_variant:
                        block.type === 'notification'
                            ? (block.notification_variant ?? 'social')
                            : undefined,
                    notification_variations:
                        block.type === 'notification'
                            ? ((block.notification_variations?.length ?? 0) > 0
                                  ? (block.notification_variations ?? [])
                                  : createNotificationVariations()
                              ).map((variation) => ({
                                  id: variation.id ?? createClientId(),
                                  value1: variation.value1 ?? '',
                                  value2: variation.value2 ?? '',
                                  value3: variation.value3 ?? '',
                                  value4: variation.value4 ?? '',
                              }))
                            : undefined,
                    timer_seconds:
                        block.type === 'timer'
                            ? Number(
                                  block.timer_seconds ??
                                      parseLegacyTimerSeconds(
                                          block.placeholder,
                                      ),
                              )
                            : undefined,
                    timer_text:
                        block.type === 'timer'
                            ? (block.timer_text ?? block.placeholder ?? '')
                            : undefined,
                    timer_style:
                        block.type === 'timer'
                            ? (block.timer_style ?? 'red')
                            : undefined,
                    loading_start_seconds:
                        block.type === 'loading'
                            ? Number(block.loading_start_seconds ?? 0)
                            : undefined,
                    loading_duration_seconds:
                        block.type === 'loading'
                            ? Number(block.loading_duration_seconds ?? 5)
                            : undefined,
                    loading_navigation_action:
                        block.type === 'loading'
                            ? (block.loading_navigation_action ?? 'none')
                            : undefined,
                    loading_target_stage_order:
                        block.type === 'loading'
                            ? (block.loading_target_stage_order ?? 'next')
                            : undefined,
                    loading_link:
                        block.type === 'loading'
                            ? (block.loading_link ?? '')
                            : undefined,
                    loading_show_title:
                        block.type === 'loading'
                            ? (block.loading_show_title ?? true)
                            : undefined,
                    loading_show_progress:
                        block.type === 'loading'
                            ? (block.loading_show_progress ?? true)
                            : undefined,
                    level_title:
                        block.type === 'level'
                            ? (block.level_title ?? block.label ?? '')
                            : undefined,
                    level_subtitle:
                        block.type === 'level'
                            ? (block.level_subtitle ?? block.placeholder ?? '')
                            : undefined,
                    level_percentage:
                        block.type === 'level'
                            ? Math.max(
                                  0,
                                  Math.min(
                                      100,
                                      Number(block.level_percentage ?? 75),
                                  ),
                              )
                            : undefined,
                    level_indicator_text:
                        block.type === 'level'
                            ? (block.level_indicator_text ?? '')
                            : undefined,
                    level_legends:
                        block.type === 'level'
                            ? (block.level_legends ?? '')
                            : undefined,
                    level_show_meter:
                        block.type === 'level'
                            ? (block.level_show_meter ?? true)
                            : undefined,
                    level_show_progress:
                        block.type === 'level'
                            ? (block.level_show_progress ?? true)
                            : undefined,
                    level_type:
                        block.type === 'level'
                            ? (block.level_type ?? 'line')
                            : undefined,
                    level_color:
                        block.type === 'level'
                            ? (block.level_color ?? 'theme')
                            : undefined,
                    phone_mask:
                        block.type === 'phone'
                            ? (block.phone_mask ?? 'br')
                            : undefined,
                    number_mask:
                        block.type === 'number'
                            ? (block.number_mask ?? 'decimal')
                            : undefined,
                    height_mode:
                        block.type === 'height'
                            ? (block.height_mode ?? 'ruler')
                            : undefined,
                    weight_mode:
                        block.type === 'weight'
                            ? (block.weight_mode ?? 'ruler')
                            : undefined,
                    button_action: block.button_action ?? 'next_stage',
                    button_target_stage_order:
                        block.button_target_stage_order ?? 'next',
                    button_link: block.button_link,
                    button_open_new_tab: block.button_open_new_tab ?? true,
                    button_color_style: block.button_color_style ?? 'theme',
                    button_animated: block.button_animated ?? false,
                    button_elevated: block.button_elevated ?? false,
                    button_sticky_footer: block.button_sticky_footer ?? false,
                    label_style: block.label_style ?? 'default',
                    text_align: block.text_align ?? 'text-left',
                    width_percent: Number(block.width_percent ?? 100),
                    align_horizontal: block.align_horizontal ?? 'start',
                    align_vertical: block.align_vertical ?? 'start',
                    show_after_seconds: block.show_after_seconds ?? null,
                    display_rule_mode: block.display_rule_mode ?? 'all',
                    display_rules: normalizeDisplayRules(block.display_rules),
                    display_rule_groups: normalizeDisplayRuleGroups(
                        block.display_rule_groups,
                        block.display_rules,
                        block.display_rule_mode,
                    ),
                })) ?? [],
        })),
);

localStages.value.forEach((stage) => {
    migrateLegacyStageFieldsToBlocks(stage);

    stage.blocks.forEach((block) => {
        if (isOptionsComponentType(block.type)) {
            normalizeOptionsBlock(block);

            return;
        }

        if (block.type === 'testimonials') {
            normalizeTestimonialsBlock(block);

            return;
        }

        if (block.type === 'faq') {
            normalizeFaqBlock(block);

            return;
        }

        if (block.type === 'carousel') {
            normalizeCarouselBlock(block);

            return;
        }

        if (block.type === 'metrics') {
            normalizeMetricsBlock(block);
        }
    });
});

const selectedStageKey = ref(localStages.value[0]?.clientId ?? '');
const activePanelTab = ref<'step' | 'appearance'>('step');
const componentPanelTab = ref<'component' | 'appearance' | 'display'>(
    'component',
);
const openStageMenuKey = ref<string | null>(null);
const copiedLink = ref(false);
const draggedStageKey = ref<string | null>(null);
const dragOverStageKey = ref<string | null>(null);
const draggedBlockId = ref<string | null>(null);
const dragOverBlockId = ref<string | null>(null);
const draggedPaletteType = ref<StageBlockType | null>(null);
const funnelNameDraft = ref(props.funnel.name);
const selectedBlockId = ref<string | null>(null);
const expandedOptionItemId = ref<string | null>(null);
const draggedOptionItemId = ref<string | null>(null);
const dragOverOptionItemId = ref<string | null>(null);
const reorderedOptionItemId = ref<string | null>(null);
const draggedNotificationVariationId = ref<string | null>(null);
const dragOverNotificationVariationId = ref<string | null>(null);
const introEditorTarget = ref<'title' | 'description' | null>(null);
const introActiveElement = ref<HTMLElement | null>(null);
const imageComponentTab = ref<'image' | 'url'>('image');
const imagePickerInput = ref<HTMLInputElement | null>(null);
const uploadingImageBlockId = ref<string | null>(null);
const audioFileInput = ref<HTMLInputElement | null>(null);
const carouselImageInput = ref<HTMLInputElement | null>(null);
const carouselTargetItemId = ref<string | null>(null);
const uploadingCarouselItemId = ref<string | null>(null);
const audioElements = ref<Record<string, HTMLAudioElement | null>>({});
const audioDurations = ref<Record<string, number>>({});
const audioCurrentTimes = ref<Record<string, number>>({});
const activeAudioKey = ref<string | null>(null);
const appearanceBackgroundTab = ref<'image' | 'url'>('image');
const appearanceBackgroundUrl = ref('');
const appearanceSize = ref('Tela inteira');
const appearancePosition = ref('Centro');
const appearanceScale = ref('100%');
const historyStack = ref<BuilderStateSnapshot[]>([]);
const historyIndex = ref(-1);
const isRestoringHistory = ref(false);
const autosaveStatus = ref<'idle' | 'saving' | 'saved' | 'error'>('idle');
const pendingSaveRequest = ref<{ isPublishing: boolean; auto: boolean } | null>(
    null,
);
const actionToast = ref<{
    visible: boolean;
    message: string;
    tone: 'info' | 'success' | 'error';
}>({
    visible: false,
    message: '',
    tone: 'info',
});
const notificationPreviewTick = ref(0);
let historyTimer: ReturnType<typeof setTimeout> | null = null;
let autosaveTimer: ReturnType<typeof setTimeout> | null = null;
let autosaveStatusTimer: ReturnType<typeof setTimeout> | null = null;
let actionToastTimer: ReturnType<typeof setTimeout> | null = null;
let reorderHighlightTimer: ReturnType<typeof setTimeout> | null = null;
let notificationPreviewTimer: number | null = null;
let importMediaStatusTimer: number | null = null;

const currentStage = computed(() => {
    return (
        localStages.value.find(
            (stage) => stage.clientId === selectedStageKey.value,
        ) ??
        localStages.value[0] ??
        null
    );
});

const selectedStageStorageKey = computed(
    () => `builder:selected-stage:${props.funnel.id}`,
);

function stageStorageValue(stage: StageDraft | null | undefined): string {
    if (!stage) {
        return '';
    }

    if (stage.id !== null) {
        return `stage:${stage.id}`;
    }

    return `client:${stage.clientId}`;
}

function findStageByStoredValue(
    storedStageKey: string | null,
): StageDraft | null {
    const normalizedKey = safeTrim(storedStageKey);

    if (normalizedKey.length === 0) {
        return null;
    }

    if (normalizedKey.startsWith('stage:')) {
        const stageId = Number(normalizedKey.slice('stage:'.length));

        if (Number.isFinite(stageId)) {
            return (
                localStages.value.find((stage) => stage.id === stageId) ?? null
            );
        }
    }

    if (normalizedKey.startsWith('client:')) {
        const clientId = normalizedKey.slice('client:'.length);

        return (
            localStages.value.find((stage) => stage.clientId === clientId) ??
            null
        );
    }

    return (
        localStages.value.find((stage) => stage.clientId === normalizedKey) ??
        null
    );
}

const selectedBlock = computed(() => {
    if (!currentStage.value || !selectedBlockId.value) {
        return null;
    }

    return (
        currentStage.value.blocks.find(
            (entry) => entry.id === selectedBlockId.value,
        ) ?? null
    );
});

const visibleCurrentStageBlocks = computed(() => {
    return currentStage.value?.blocks ?? [];
});

const contentTextEditorElement = ref<HTMLElement | null>(null);
const audioWaveHeights = [
    7, 10, 13, 16, 14, 12, 10, 8, 7, 9, 12, 15, 13, 10, 8, 7, 8, 10, 13, 16, 14,
    12, 10, 8, 7,
];

function audioPreviewKey(stageClientId: string, blockId: string): string {
    return `${stageClientId}:${blockId}`;
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
    audioDurations.value[key] = Number.isFinite(element.duration)
        ? element.duration
        : 0;
}

function onAudioTimeUpdate(key: string, event: Event): void {
    const element = event.target as HTMLAudioElement;
    audioCurrentTimes.value[key] = Number.isFinite(element.currentTime)
        ? element.currentTime
        : 0;
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

    const currentTime = audioCurrentTimes.value[key] ?? 0;

    return Math.min(1, Math.max(0, currentTime / duration));
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
    const ratio = Math.min(
        1,
        Math.max(0, (event.clientX - rect.left) / rect.width),
    );
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

function openIntroInlineEditor(
    target: 'title' | 'description',
    event?: FocusEvent,
): void {
    if (!props.permissions.canEdit) {
        return;
    }

    introEditorTarget.value = target;
    introActiveElement.value = (event?.target as HTMLElement | null) ?? null;
}

function closeIntroInlineEditor(): void {
    introEditorTarget.value = null;
    introActiveElement.value = null;
}

function syncIntroInlineText(
    event: Event,
    field: 'title' | 'description',
): void {
    if (
        !selectedBlock.value ||
        !supportsRichTextPanel(selectedBlock.value.type)
    ) {
        return;
    }

    const value = (event.target as HTMLElement).innerText.trim();

    if (field === 'title') {
        selectedBlock.value.options_intro_title = value;
    } else {
        selectedBlock.value.options_intro_description = value;
    }
}

function applyIntroEditorCommand(command: string): void {
    if (!props.permissions.canEdit || introEditorTarget.value === null) {
        return;
    }

    if (introActiveElement.value) {
        introActiveElement.value.focus();
    }

    document.execCommand(command, false);
}

function applyIntroEditorValueCommand(command: string, value: string): void {
    if (!props.permissions.canEdit || introEditorTarget.value === null) {
        return;
    }

    if (introActiveElement.value) {
        introActiveElement.value.focus();
    }

    document.execCommand(command, false, value);
}

function applyIntroHeading(level: 'h1' | 'h2' | 'h3' | 'p'): void {
    if (!props.permissions.canEdit || introEditorTarget.value !== 'title') {
        return;
    }

    const tag = level.toUpperCase();
    applyIntroEditorValueCommand('formatBlock', tag);
}

function applyIntroAlignment(alignment: 'left' | 'center' | 'right'): void {
    if (alignment === 'left') {
        applyIntroEditorCommand('justifyLeft');
        return;
    }

    if (alignment === 'center') {
        applyIntroEditorCommand('justifyCenter');
        return;
    }

    applyIntroEditorCommand('justifyRight');
}

function escapeHtml(value: string): string {
    return value
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');
}

function safeTrim(value: unknown): string {
    if (typeof value === 'string') {
        return value.trim();
    }

    if (value === null || value === undefined) {
        return '';
    }

    return String(value).trim();
}

function normalizeRichTextHtml(value: string | undefined): string {
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

const importedVariablePreviewValues: Record<string, string> = {
    nome: 'Visitante',
    name: 'Visitante',
    altura: '170',
    peso_actual: '70',
    peso_ideal: '65',
    edad: '30',
};

function previewDynamicText(value: string | null | undefined): string {
    return safeTrim(value).replace(
        /\{\{\s*([A-Za-z0-9_-]+)\s*\}\}/g,
        (_token, variableName: string) =>
            importedVariablePreviewValues[variableName.toLowerCase()] ??
            'Exemplo',
    );
}

function contentTextMarkup(block: StageBlock): string {
    if (block.type !== 'content_text') {
        return '';
    }

    const storedMarkup = previewDynamicText(
        normalizeRichTextHtml(block.placeholder),
    );
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

function ensureContentTextMarkup(block: StageBlock | null): void {
    if (!block || block.type !== 'content_text') {
        return;
    }

    const markup = contentTextMarkup(block);
    block.placeholder = markup;
    block.label = '';
}

function bindContentTextEditorElement(
    element: Element | ComponentPublicInstance | null,
): void {
    if (element instanceof HTMLElement) {
        contentTextEditorElement.value = element;
    } else if (
        element !== null &&
        '$el' in element &&
        element.$el instanceof HTMLElement
    ) {
        contentTextEditorElement.value = element.$el;
    } else {
        contentTextEditorElement.value = null;
    }

    if (
        contentTextEditorElement.value &&
        selectedBlock.value?.type === 'content_text'
    ) {
        const markup = contentTextMarkup(selectedBlock.value);

        if (contentTextEditorElement.value.innerHTML !== markup) {
            contentTextEditorElement.value.innerHTML = markup;
        }
    }
}

function syncContentTextMarkupFromEditor(): void {
    if (
        !selectedBlock.value ||
        selectedBlock.value.type !== 'content_text' ||
        !contentTextEditorElement.value
    ) {
        return;
    }

    selectedBlock.value.placeholder = normalizeRichTextHtml(
        contentTextEditorElement.value.innerHTML,
    );
    selectedBlock.value.label = '';
}

function syncContentTextMarkupForBlock(
    block: StageBlock | null,
    element: HTMLElement | null,
): void {
    if (!block || block.type !== 'content_text' || !element) {
        return;
    }

    block.placeholder = normalizeRichTextHtml(element.innerHTML);
    block.label = '';
}

function flushActiveInlineEditors(): void {
    if (!selectedBlock.value) {
        return;
    }

    if (selectedBlock.value.type === 'content_text') {
        syncContentTextMarkupForBlock(
            selectedBlock.value,
            contentTextEditorElement.value,
        );

        return;
    }

    if (
        !supportsRichTextPanel(selectedBlock.value.type) ||
        !introActiveElement.value ||
        introEditorTarget.value === null
    ) {
        return;
    }

    const value = introActiveElement.value.innerText.trim();

    if (introEditorTarget.value === 'title') {
        selectedBlock.value.options_intro_title = value;
    } else {
        selectedBlock.value.options_intro_description = value;
    }
}

function activateContentTextEditor(event?: FocusEvent): void {
    if (!props.permissions.canEdit) {
        return;
    }

    introEditorTarget.value = 'description';
    contentTextEditorElement.value =
        (event?.target as HTMLElement | null) ?? contentTextEditorElement.value;
    introActiveElement.value = contentTextEditorElement.value;
}

function applyContentTextEditorCommand(command: string): void {
    if (!props.permissions.canEdit || !contentTextEditorElement.value) {
        return;
    }

    contentTextEditorElement.value.focus();
    document.execCommand(command, false);
    syncContentTextMarkupFromEditor();
}

function applyContentTextEditorValueCommand(
    command: string,
    value: string,
): void {
    if (!props.permissions.canEdit || !contentTextEditorElement.value) {
        return;
    }

    contentTextEditorElement.value.focus();
    document.execCommand(command, false, value);
    syncContentTextMarkupFromEditor();
}

function applyContentTextHeading(level: 'h1' | 'h2' | 'h3' | 'p'): void {
    applyContentTextEditorValueCommand('formatBlock', level.toUpperCase());
}

function applyContentTextAlignment(
    alignment: 'left' | 'center' | 'right',
): void {
    if (alignment === 'left') {
        applyContentTextEditorCommand('justifyLeft');
        return;
    }

    if (alignment === 'center') {
        applyContentTextEditorCommand('justifyCenter');
        return;
    }

    applyContentTextEditorCommand('justifyRight');
}

function insertContentTextLink(): void {
    if (!props.permissions.canEdit || !contentTextEditorElement.value) {
        return;
    }

    const link = window.prompt('Informe a URL do link');

    if (!link) {
        return;
    }

    applyContentTextEditorValueCommand('createLink', link.trim());
}

function formatPastedRichText(value: string): string {
    return value
        .split(/\r?\n\r?\n/)
        .map((paragraph) => paragraph.trim())
        .filter((paragraph) => paragraph.length > 0)
        .map(
            (paragraph) =>
                `<p>${escapeHtml(paragraph).replace(/\r?\n/g, '<br>')}</p>`,
        )
        .join('');
}

function handleContentTextPaste(event: ClipboardEvent): void {
    if (!props.permissions.canEdit || !contentTextEditorElement.value) {
        return;
    }

    event.preventDefault();

    const plainText = event.clipboardData?.getData('text/plain') ?? '';
    const html = event.clipboardData?.getData('text/html') ?? '';
    const nextMarkup =
        html.trim() !== '' ? html : formatPastedRichText(plainText);

    document.execCommand('insertHTML', false, nextMarkup);
    syncContentTextMarkupFromEditor();
}

function insertIntroLink(): void {
    if (!props.permissions.canEdit || introEditorTarget.value === null) {
        return;
    }

    const url = window.prompt('URL do link (https://...)');
    if (!url) {
        return;
    }

    applyIntroEditorValueCommand('createLink', url);
}

async function uploadMediaFile(
    file: File,
    kind: 'image' | 'audio',
): Promise<string | null> {
    const currentCsrfToken = (): string | null => {
        const token =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content')
                ?.trim() ?? '';

        return token.length > 0 ? token : null;
    };

    const currentXsrfToken = (): string | null => {
        const cookie = document.cookie
            .split('; ')
            .find((entry) => entry.startsWith('XSRF-TOKEN='));

        if (!cookie) {
            return null;
        }

        const value = cookie.slice('XSRF-TOKEN='.length);

        return value.length > 0 ? decodeURIComponent(value) : null;
    };

    const refreshCsrfToken = async (): Promise<string | null> => {
        try {
            const response = await fetch(window.location.href, {
                headers: {
                    Accept: 'text/html',
                },
                credentials: 'same-origin',
                cache: 'no-store',
            });

            if (!response.ok) {
                return null;
            }

            const html = await response.text();
            const match = html.match(
                /<meta\s+name=["']csrf-token["']\s+content=["']([^"']+)["']/i,
            );
            const nextToken = match?.[1]?.trim() ?? '';

            if (nextToken.length === 0) {
                return null;
            }

            document
                .querySelector('meta[name="csrf-token"]')
                ?.setAttribute('content', nextToken);

            return nextToken;
        } catch {
            return null;
        }
    };

    const createFormData = (csrfToken: string | null): FormData => {
        const formData = new FormData();
        formData.append('kind', kind);
        formData.append('file', file);

        if (csrfToken) {
            formData.append('_token', csrfToken);
        }

        return formData;
    };

    const sendUploadRequest = (
        csrfToken: string | null,
        xsrfToken: string | null,
    ): Promise<Response> => {
        const headers: Record<string, string> = {
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
        };

        if (csrfToken) {
            headers['X-CSRF-TOKEN'] = csrfToken;
        }

        if (xsrfToken) {
            headers['X-XSRF-TOKEN'] = xsrfToken;
        }

        return fetch(`/funnels/${props.funnel.id}/media`, {
            method: 'POST',
            headers,
            body: createFormData(csrfToken),
            credentials: 'same-origin',
        });
    };

    let csrfToken = currentCsrfToken() ?? (await refreshCsrfToken());
    let xsrfToken = currentXsrfToken();

    if (!csrfToken && !xsrfToken) {
        showActionToast('Falha ao enviar arquivo: token CSRF ausente.');

        return null;
    }

    try {
        let response = await sendUploadRequest(csrfToken, xsrfToken);

        if (response.status === 419) {
            csrfToken = await refreshCsrfToken();
            xsrfToken = currentXsrfToken();

            if (csrfToken || xsrfToken) {
                response = await sendUploadRequest(csrfToken, xsrfToken);
            }
        }

        if (!response.ok) {
            if (response.status === 419) {
                showActionToast(
                    'Sua sessao expirou para upload. Recarregue a pagina e tente novamente.',
                );

                return null;
            }

            showActionToast('Falha ao enviar arquivo. Tente novamente.');

            return null;
        }

        const result = (await response.json()) as { url?: string };

        if (!result.url) {
            showActionToast('Falha ao processar o upload.');

            return null;
        }

        return result.url;
    } catch {
        showActionToast('Falha ao enviar arquivo. Verifique sua conexao.');

        return null;
    }
}

function triggerImageFilePicker(): void {
    if (
        !props.permissions.canEdit ||
        !selectedBlock.value ||
        selectedBlock.value.type !== 'image'
    ) {
        return;
    }

    imagePickerInput.value?.click();
}

function setImageComponentTab(tab: 'image' | 'url'): void {
    imageComponentTab.value = tab;
}

async function handleImageFileChange(event: Event): Promise<void> {
    if (
        !props.permissions.canEdit ||
        !selectedBlock.value ||
        selectedBlock.value.type !== 'image'
    ) {
        return;
    }

    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) {
        return;
    }

    const imageBlock = selectedBlock.value;
    uploadingImageBlockId.value = imageBlock.id;

    try {
        const uploadedUrl = await uploadMediaFile(file, 'image');
        if (uploadedUrl) {
            imageBlock.placeholder = uploadedUrl;
            imageComponentTab.value = 'image';
        }
    } finally {
        if (uploadingImageBlockId.value === imageBlock.id) {
            uploadingImageBlockId.value = null;
        }

        input.value = '';
    }
}

function triggerAudioFilePicker(): void {
    if (
        !props.permissions.canEdit ||
        !selectedBlock.value ||
        selectedBlock.value.type !== 'audio'
    ) {
        return;
    }

    audioFileInput.value?.click();
}

function triggerCarouselImagePicker(itemId: string): void {
    if (
        !props.permissions.canEdit ||
        !selectedBlock.value ||
        selectedBlock.value.type !== 'carousel'
    ) {
        return;
    }

    carouselTargetItemId.value = itemId;
    carouselImageInput.value?.click();
}

async function handleCarouselImageFileChange(event: Event): Promise<void> {
    if (
        !props.permissions.canEdit ||
        !selectedBlock.value ||
        selectedBlock.value.type !== 'carousel'
    ) {
        return;
    }

    const targetItemId = carouselTargetItemId.value;
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (!targetItemId || !file) {
        input.value = '';
        carouselTargetItemId.value = null;
        return;
    }

    const carouselBlock = selectedBlock.value;
    uploadingCarouselItemId.value = targetItemId;

    try {
        const uploadedUrl = await uploadMediaFile(file, 'image');
        if (uploadedUrl) {
            const targetItem = carouselBlock.option_items?.find(
                (item) => item.id === targetItemId,
            );
            if (targetItem) {
                targetItem.value = uploadedUrl;
                targetItem.image_url = uploadedUrl;
            }
        }
    } finally {
        if (uploadingCarouselItemId.value === targetItemId) {
            uploadingCarouselItemId.value = null;
        }

        input.value = '';
        carouselTargetItemId.value = null;
    }
}

async function handleAudioFileChange(event: Event): Promise<void> {
    if (
        !props.permissions.canEdit ||
        !selectedBlock.value ||
        selectedBlock.value.type !== 'audio'
    ) {
        return;
    }

    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) {
        return;
    }

    const uploadedUrl = await uploadMediaFile(file, 'audio');
    if (uploadedUrl && selectedBlock.value?.type === 'audio') {
        selectedBlock.value.audio_src = uploadedUrl;
    }

    input.value = '';
}

function toggleOptionItemSettings(itemId: string): void {
    if (!props.permissions.canEdit) {
        return;
    }

    expandedOptionItemId.value =
        expandedOptionItemId.value === itemId ? null : itemId;
}

function onOptionItemDragStart(itemId: string): void {
    if (!props.permissions.canEdit) {
        return;
    }

    draggedOptionItemId.value = itemId;
}

function onOptionItemDragOver(itemId: string): void {
    if (
        !props.permissions.canEdit ||
        draggedOptionItemId.value === null ||
        draggedOptionItemId.value === itemId
    ) {
        dragOverOptionItemId.value = null;

        return;
    }

    dragOverOptionItemId.value = itemId;
}

function onOptionItemDrop(block: StageBlock, targetItemId: string): void {
    if (
        !props.permissions.canEdit ||
        !isOptionsComponentType(block.type) ||
        !block.option_items ||
        draggedOptionItemId.value === null
    ) {
        return;
    }

    const sourceIndex = block.option_items.findIndex(
        (item) => item.id === draggedOptionItemId.value,
    );
    const targetIndex = block.option_items.findIndex(
        (item) => item.id === targetItemId,
    );

    if (sourceIndex < 0 || targetIndex < 0 || sourceIndex === targetIndex) {
        draggedOptionItemId.value = null;
        dragOverOptionItemId.value = null;

        return;
    }

    const [movedItem] = block.option_items.splice(sourceIndex, 1);
    block.option_items.splice(targetIndex, 0, movedItem);
    block.options = block.option_items.map((item) => item.label);
    reorderedOptionItemId.value = movedItem.id;

    if (reorderHighlightTimer) {
        clearTimeout(reorderHighlightTimer);
    }

    reorderHighlightTimer = setTimeout(() => {
        reorderedOptionItemId.value = null;
    }, 700);

    draggedOptionItemId.value = null;
    dragOverOptionItemId.value = null;
}

function onOptionItemDragEnd(): void {
    draggedOptionItemId.value = null;
    dragOverOptionItemId.value = null;
}

function selectBlock(blockId: string): void {
    selectedBlockId.value = blockId;
    componentPanelTab.value = 'component';
    mobileBuilderPanel.value = 'settings';
}

function clearSelectedBlock(): void {
    selectedBlockId.value = null;
    openStageMenuKey.value = null;
}
const stageDestinationOptions = computed(() => {
    return [
        { value: 'next', label: 'Etapa seguinte' },
        ...localStages.value.map((stage, index) => ({
            value: String(index + 1),
            label: `${index + 1}. ${stage.name || `Etapa ${index + 1}`}`,
        })),
    ];
});

const hiddenPaletteTypes: StageBlockType[] = ['video_response'];
const paletteBlocks = computed(() =>
    blockCatalog.filter((block) => !hiddenPaletteTypes.includes(block.type)),
);
const formBlocks = computed(() =>
    paletteBlocks.value.filter((block) => block.category === 'form'),
);
const quizBlocks = computed(() =>
    paletteBlocks.value.filter((block) => block.category === 'quiz'),
);
const mediaBlocks = computed(() =>
    paletteBlocks.value.filter((block) => block.category === 'media'),
);
const argumentBlocks = computed(() =>
    paletteBlocks.value.filter((block) => block.category === 'argument'),
);
const personalizationBlocks = computed(() =>
    paletteBlocks.value.filter((block) => block.category === 'personalization'),
);

const saveForm = useForm({
    name: props.funnel.name,
    is_active: props.funnel.is_active,
    stages: [] as object[],
});

const templateForm = useForm({
    name: props.funnel.name,
    description: props.funnel.description ?? '',
    category: '',
    thumbnail_path: '',
    is_active: true,
    is_premium: false,
});
const isSaveTemplateModalOpen = ref(false);
const mobileBuilderPanel = ref<'stages' | 'library' | 'preview' | 'settings'>(
    'preview',
);
const mobileBuilderPanels = [
    { id: 'stages', label: 'Etapas' },
    { id: 'library', label: 'Blocos' },
    { id: 'preview', label: 'Preview' },
    { id: 'settings', label: 'Editar' },
] as const;

const flashStatus = computed(() => page.props.flash?.status ?? '');
const flashMessage = computed(
    () =>
        ({
            'ai-funnel-created':
                'Funil criado com IA. Revise o conteúdo e salve suas alterações antes de publicar.',
            'template-created': 'Template salvo na sua biblioteca.',
            'funnel-imported': 'Funil importado com sucesso.',
            'funnel-imported-media-queued':
                'Funil importado. As imagens serão copiadas em segundo plano.',
        })[flashStatus.value] ?? flashStatus.value,
);
const aiGeneration = computed(
    () => props.funnel.design_settings?.aiGeneration ?? null,
);
const importMediaStatus = computed(
    () => props.funnel.design_settings?.importMedia ?? null,
);
const isImportMediaActive = computed(() =>
    ['queued', 'processing'].includes(importMediaStatus.value?.status ?? ''),
);
const importMediaNotice = computed(() => {
    const total = importMediaStatus.value?.total ?? 0;

    return importMediaStatus.value?.status === 'processing'
        ? `Copiando ${total} ${total === 1 ? 'imagem' : 'imagens'} para o InovaForm. Você pode continuar editando o funil.`
        : `${total} ${total === 1 ? 'imagem aguarda' : 'imagens aguardam'} processamento. A página continuará disponível.`;
});

function scheduleImportMediaStatusRefresh(): void {
    if (!isImportMediaActive.value || importMediaStatusTimer !== null) {
        return;
    }

    importMediaStatusTimer = window.setTimeout(() => {
        importMediaStatusTimer = null;
        router.reload({
            only: ['funnel'],
            onFinish: () => scheduleImportMediaStatusRefresh(),
        });
    }, 2500);
}

function cloneBlock(block: StageBlock): StageBlock {
    return {
        id: block.id,
        type: block.type,
        label: block.label,
        placeholder: block.placeholder,
        variable_name: block.variable_name,
        required: block.required,
        options: block.options ? [...block.options] : undefined,
        option_items: block.option_items
            ? block.option_items.map((item) => ({ ...item }))
            : undefined,
        options_intro_type: block.options_intro_type,
        options_intro_title: block.options_intro_title,
        options_intro_description: block.options_intro_description,
        options_required_selection: block.options_required_selection,
        options_allow_multiple: block.options_allow_multiple,
        options_disable_auto_follow: block.options_disable_auto_follow,
        options_style: block.options_style,
        options_transparent_image: block.options_transparent_image,
        options_layout: block.options_layout,
        options_orientation: block.options_orientation,
        options_image_ratio: block.options_image_ratio,
        options_disposition: block.options_disposition,
        options_detail: block.options_detail,
        options_detail_position: block.options_detail_position,
        options_border_size: block.options_border_size,
        options_shadow: block.options_shadow,
        options_spacing: block.options_spacing,
        testimonials_layout: block.testimonials_layout,
        faq_first_active: block.faq_first_active,
        faq_detail: block.faq_detail,
        price_title: block.price_title,
        price_prefix: block.price_prefix,
        price_value: block.price_value,
        price_suffix: block.price_suffix,
        price_badge_text: block.price_badge_text,
        price_mode: block.price_mode,
        price_layout: block.price_layout,
        price_style: block.price_style,
        price_link: block.price_link,
        carousel_layout: block.carousel_layout,
        carousel_pagination: block.carousel_pagination,
        carousel_autoplay: block.carousel_autoplay,
        carousel_autoplay_seconds: block.carousel_autoplay_seconds,
        carousel_border_type: block.carousel_border_type,
        image_ratio: block.image_ratio,
        image_fit: block.image_fit,
        image_radius: block.image_radius,
        image_frame: block.image_frame,
        video_ratio: block.video_ratio,
        audio_sender: block.audio_sender,
        audio_src: block.audio_src,
        audio_avatar_url: block.audio_avatar_url,
        audio_model: block.audio_model,
        audio_theme: block.audio_theme,
        attention_style: block.attention_style,
        attention_emphasis: block.attention_emphasis,
        attention_padding: block.attention_padding,
        notification_title: block.notification_title,
        notification_description: block.notification_description,
        notification_avatar_url: block.notification_avatar_url,
        notification_position: block.notification_position,
        notification_duration_seconds: block.notification_duration_seconds,
        notification_interval_seconds: block.notification_interval_seconds,
        notification_style: block.notification_style,
        notification_size: block.notification_size,
        notification_variant: block.notification_variant,
        notification_variations: block.notification_variations
            ? block.notification_variations.map((variation) => ({
                  ...variation,
              }))
            : undefined,
        timer_seconds: block.timer_seconds,
        timer_text: block.timer_text,
        timer_style: block.timer_style,
        loading_start_seconds: block.loading_start_seconds,
        loading_duration_seconds: block.loading_duration_seconds,
        loading_navigation_action: block.loading_navigation_action,
        loading_target_stage_order: block.loading_target_stage_order,
        loading_link: block.loading_link,
        loading_show_title: block.loading_show_title,
        loading_show_progress: block.loading_show_progress,
        level_title: block.level_title,
        level_subtitle: block.level_subtitle,
        level_percentage: block.level_percentage,
        level_indicator_text: block.level_indicator_text,
        level_legends: block.level_legends,
        level_show_meter: block.level_show_meter,
        level_show_progress: block.level_show_progress,
        level_type: block.level_type,
        level_color: block.level_color,
        phone_mask: block.phone_mask,
        number_mask: block.number_mask,
        height_mode: block.height_mode,
        weight_mode: block.weight_mode,
        button_action: block.button_action,
        button_target_stage_order: block.button_target_stage_order,
        button_link: block.button_link,
        button_open_new_tab: block.button_open_new_tab,
        button_color_style: block.button_color_style,
        button_animated: block.button_animated,
        button_elevated: block.button_elevated,
        button_sticky_footer: block.button_sticky_footer,
        label_style: block.label_style,
        text_align: block.text_align,
        width_percent: block.width_percent,
        align_horizontal: block.align_horizontal,
        align_vertical: block.align_vertical,
        show_after_seconds: block.show_after_seconds,
        display_rule_mode: block.display_rule_mode ?? 'all',
        display_rules: normalizeDisplayRules(block.display_rules),
        display_rule_groups: normalizeDisplayRuleGroups(
            block.display_rule_groups,
            block.display_rules,
            block.display_rule_mode ?? 'all',
        ),
    };
}

function cloneStage(stage: StageDraft): StageDraft {
    return {
        id: stage.id,
        clientId: stage.clientId,
        name: stage.name,
        conversion_rate: stage.conversion_rate,
        expected_volume: stage.expected_volume,
        header: {
            show_logo: stage.header.show_logo,
            show_progress: stage.header.show_progress,
            allow_back: stage.header.allow_back,
        },
        title: stage.title,
        subtitle: stage.subtitle,
        buttonText: stage.buttonText,
        stageButtonAction: stage.stageButtonAction,
        stageButtonTargetStageOrder: stage.stageButtonTargetStageOrder,
        stageButtonLink: stage.stageButtonLink,
        stageButtonOpenNewTab: stage.stageButtonOpenNewTab,
        stageButtonColorStyle: stage.stageButtonColorStyle,
        stageButtonAnimated: stage.stageButtonAnimated,
        stageButtonElevated: stage.stageButtonElevated,
        stageButtonStickyFooter: stage.stageButtonStickyFooter,
        blocks: stage.blocks.map(cloneBlock),
    };
}

function createBuilderSnapshot(): BuilderStateSnapshot {
    return {
        funnelName: funnelNameDraft.value,
        selectedStageKey: selectedStageKey.value,
        stages: localStages.value.map(cloneStage),
    };
}

function restoreBuilderSnapshot(snapshot: BuilderStateSnapshot): void {
    isRestoringHistory.value = true;
    funnelNameDraft.value = snapshot.funnelName;
    localStages.value = snapshot.stages.map(cloneStage);

    const stageExists = localStages.value.some(
        (stage) => stage.clientId === snapshot.selectedStageKey,
    );
    selectedStageKey.value = stageExists
        ? snapshot.selectedStageKey
        : (localStages.value[0]?.clientId ?? '');
    selectedBlockId.value = null;

    isRestoringHistory.value = false;
}

function showActionToast(
    message: string,
    tone: 'info' | 'success' | 'error' = 'info',
): void {
    if (actionToastTimer) {
        clearTimeout(actionToastTimer);
    }

    actionToast.value = {
        visible: true,
        message,
        tone,
    };

    actionToastTimer = setTimeout(() => {
        actionToast.value.visible = false;
    }, 1400);
}

function carouselItemImageUrl(
    item: Pick<OptionItem, 'value' | 'image_url'>,
): string | null {
    return sanitizeStoredAssetUrl(
        safeTrim(item.value) || safeTrim(item.image_url),
    );
}

function resolveFirstFormError(errors: Record<string, string>): string {
    return (
        Object.values(errors).find((message) => safeTrim(message).length > 0) ??
        'Nao foi possivel salvar. Revise os campos e tente novamente.'
    );
}

function pushHistorySnapshot(force = false): void {
    if (!props.permissions.canEdit || isRestoringHistory.value) {
        return;
    }

    const snapshot = createBuilderSnapshot();
    const snapshotJson = JSON.stringify(snapshot);
    const currentJson =
        historyIndex.value >= 0
            ? JSON.stringify(historyStack.value[historyIndex.value])
            : null;

    if (!force && snapshotJson === currentJson) {
        return;
    }

    if (historyIndex.value < historyStack.value.length - 1) {
        historyStack.value = historyStack.value.slice(
            0,
            historyIndex.value + 1,
        );
    }

    historyStack.value.push(snapshot);

    if (historyStack.value.length > 80) {
        historyStack.value.shift();
    }

    historyIndex.value = historyStack.value.length - 1;
}

const canUndo = computed(() => historyIndex.value > 0);
const canRedo = computed(
    () =>
        historyIndex.value >= 0 &&
        historyIndex.value < historyStack.value.length - 1,
);

function undoHistory(): void {
    if (!props.permissions.canEdit || !canUndo.value) {
        return;
    }

    historyIndex.value -= 1;
    restoreBuilderSnapshot(historyStack.value[historyIndex.value]);
    showActionToast('Desfeito');
}

function redoHistory(): void {
    if (!props.permissions.canEdit || !canRedo.value) {
        return;
    }

    historyIndex.value += 1;
    restoreBuilderSnapshot(historyStack.value[historyIndex.value]);
    showActionToast('Refeito');
}

function createBlock(type: StageBlockType): StageBlock {
    const catalogItem = blockCatalog.find((item) => item.type === type);
    const fallbackLabel = catalogItem?.label ?? 'Campo';

    const block: StageBlock = {
        id: createClientId(),
        type,
        label: fallbackLabel,
        required: false,
        label_style: 'default',
        text_align: 'text-left',
        width_percent: 100,
        align_horizontal: 'start',
        align_vertical: 'start',
        show_after_seconds: null,
        display_rule_mode: 'all',
        display_rules: [],
        display_rule_groups: [],
    };

    if (type === 'phone') {
        block.phone_mask = 'br';
    }

    if (type === 'number') {
        block.placeholder = 'Digite um valor...';
        block.number_mask = 'decimal';
    }

    if (type === 'height') {
        block.height_mode = 'ruler';
        block.placeholder = '170';
    }

    if (type === 'weight') {
        block.weight_mode = 'ruler';
        block.placeholder = '70';
    }

    if (isOptionsComponentType(type)) {
        block.options = type === 'yes_no' ? defaultOptionsLabels(type) : [''];
        block.option_items =
            type === 'yes_no'
                ? createOptionItems(block.options)
                : createBlankOptionItems(1);
        block.options_intro_type = 'none';
        block.options_intro_title = '';
        block.options_intro_description = '';
        block.options_required_selection = true;
        block.options_allow_multiple = defaultOptionsAllowMultiple(type);
        block.options_disable_auto_follow = false;
        block.options_style = 'simple';
        block.options_transparent_image = true;
        block.options_layout = 'grid_2';
        block.options_orientation = 'vertical';
        block.options_image_ratio = '1:1';
        block.options_disposition = 'image_text';
        block.options_detail = defaultOptionsDetail(type);
        block.options_detail_position = 'start';
        block.options_border_size = 'small';
        block.options_shadow = 'none';
        block.options_spacing = 'simple';
    }

    if (type === 'single_choice') {
        block.options = [''];
        block.option_items = createBlankOptionItems(1);
    }

    if (type === 'arguments') {
        block.options = [''];
    }

    if (type === 'testimonials') {
        block.options = [];
        block.option_items = [];
        block.options_border_size = 'small';
        block.options_shadow = 'none';
        block.options_spacing = 'simple';
        block.testimonials_layout = 'list';
    }

    if (type === 'faq') {
        block.options = [];
        block.option_items = [];
        block.faq_first_active = true;
        block.faq_detail = 'arrow';
    }

    if (type === 'price') {
        block.options = [];
        block.price_title = '';
        block.price_prefix = '';
        block.price_value = '';
        block.price_suffix = '';
        block.price_badge_text = '';
        block.price_mode = 'illustrative';
        block.price_layout = 'horizontal';
        block.price_style = 'theme';
        block.price_link = '';
    }

    if (type === 'before_after') {
        block.options = ['', ''];
    }

    if (type === 'carousel') {
        block.options = [];
        block.option_items = [];
        block.carousel_layout = 'image_text';
        block.carousel_pagination = true;
        block.carousel_autoplay = false;
        block.carousel_autoplay_seconds = 3;
        block.carousel_border_type = 'none';
    }

    if (type === 'metrics') {
        block.label = '';
        block.options = [];
        block.option_items = [
            {
                id: createClientId(),
                label: '',
                value: '',
                description: '',
                points: 0,
                destination: '',
            },
        ];
    }

    if (type === 'level') {
        block.label = '';
        block.placeholder = '';
        block.level_title = '';
        block.level_subtitle = '';
        block.level_percentage = 0;
        block.level_indicator_text = '';
        block.level_legends = '';
        block.level_show_meter = true;
        block.level_show_progress = true;
        block.level_type = 'line';
        block.level_color = 'theme';
    }

    if (type === 'timer') {
        block.label = '';
        block.placeholder = '';
        block.timer_seconds = 20;
        block.timer_text = '';
        block.timer_style = 'red';
    }

    if (type === 'loading') {
        block.label = '';
        block.placeholder = '';
        block.loading_start_seconds = 0;
        block.loading_duration_seconds = 5;
        block.loading_navigation_action = 'none';
        block.loading_target_stage_order = 'next';
        block.loading_link = '';
        block.loading_show_title = true;
        block.loading_show_progress = true;
    }

    if (type === 'content_text') {
        block.label = '';
        block.placeholder = '';
    }

    if (type === 'video') {
        block.placeholder = '';
        block.video_ratio = '16:9';
    }

    if (type === 'image') {
        block.placeholder = '';
        block.image_ratio = 'auto';
        block.image_fit = 'cover';
        block.image_radius = 'medium';
        block.image_frame = 'subtle';
    }

    if (type === 'audio') {
        block.label = '';
        block.audio_sender = '';
        block.audio_src = '';
        block.audio_avatar_url = '';
        block.audio_model = 'whatsapp';
        block.audio_theme = 'light';
    }

    if (type === 'attention') {
        block.label = '';
        block.placeholder = '';
        block.attention_style = 'red';
        block.attention_emphasis = false;
        block.attention_padding = 'default';
    }

    if (type === 'notification') {
        block.label = '';
        block.notification_title = defaultNotificationTitle();
        block.notification_description = defaultNotificationDescription();
        block.notification_avatar_url = defaultNotificationAvatarUrl();
        block.notification_position = 'default';
        block.notification_duration_seconds = 5;
        block.notification_interval_seconds = 2;
        block.notification_style = 'white';
        block.notification_size = 'default';
        block.notification_variant = 'social';
        block.notification_variations = createNotificationVariations(true);
    }

    if (type === 'button') {
        block.label = '';
        block.button_action = 'next_stage';
        block.button_target_stage_order = 'next';
        block.button_link = '';
        block.button_open_new_tab = true;
        block.button_color_style = 'theme';
        block.button_animated = false;
        block.button_elevated = false;
        block.button_sticky_footer = false;
    }

    return block;
}

function migrateLegacyStageFieldsToBlocks(stage: StageDraft): void {
    const title = safeTrim(stage.title);
    const subtitle = safeTrim(stage.subtitle);
    const buttonText = safeTrim(stage.buttonText);

    if (title.length > 0 || subtitle.length > 0) {
        const introBlock = createBlock('content_text');
        const fragments: string[] = [];

        if (title.length > 0) {
            fragments.push(`<h1>${escapeHtml(title)}</h1>`);
        }

        if (subtitle.length > 0) {
            fragments.push(`<p>${escapeHtml(subtitle)}</p>`);
        }

        introBlock.placeholder = fragments.join('');
        stage.blocks.unshift(introBlock);
        stage.title = '';
        stage.subtitle = '';
    }

    if (buttonText.length > 0) {
        const buttonBlock = createBlock('button');
        buttonBlock.label = buttonText;
        buttonBlock.button_action = stage.stageButtonAction;
        buttonBlock.button_target_stage_order =
            stage.stageButtonTargetStageOrder ?? 'next';
        buttonBlock.button_link = stage.stageButtonLink;
        buttonBlock.button_open_new_tab = stage.stageButtonOpenNewTab;
        buttonBlock.button_color_style = stage.stageButtonColorStyle;
        buttonBlock.button_animated = stage.stageButtonAnimated;
        buttonBlock.button_elevated = stage.stageButtonElevated;
        buttonBlock.button_sticky_footer = stage.stageButtonStickyFooter;
        stage.blocks.push(buttonBlock);
        stage.buttonText = '';
        stage.stageButtonAction = 'next_stage';
        stage.stageButtonTargetStageOrder = 'next';
        stage.stageButtonLink = '';
        stage.stageButtonOpenNewTab = false;
        stage.stageButtonColorStyle = 'theme';
        stage.stageButtonAnimated = false;
        stage.stageButtonElevated = false;
        stage.stageButtonStickyFooter = false;
    }
}

function isChoiceBlock(type: StageBlockType): boolean {
    return [
        'options',
        'multiple_choice',
        'single_choice',
        'yes_no',
        'arguments',
        'testimonials',
        'faq',
        'before_after',
        'metrics',
    ].includes(type);
}

function isOptionsComponentType(type: StageBlockType): boolean {
    return (
        type === 'options' ||
        type === 'multiple_choice' ||
        type === 'single_choice' ||
        type === 'yes_no'
    );
}

function supportsRichTextPanel(type: StageBlockType): boolean {
    return isOptionsComponentType(type) || type === 'content_text';
}

function defaultOptionsAllowMultiple(type: StageBlockType): boolean {
    return type === 'multiple_choice';
}

function shouldShowLabel(block: StageBlock): boolean {
    return (
        block.type !== 'button' &&
        block.type !== 'spacer' &&
        block.type !== 'content_text' &&
        block.type !== 'arguments' &&
        block.type !== 'metrics' &&
        block.type !== 'testimonials' &&
        block.type !== 'faq' &&
        block.type !== 'price' &&
        block.type !== 'carousel' &&
        block.type !== 'image' &&
        block.type !== 'video' &&
        block.type !== 'audio' &&
        block.type !== 'attention' &&
        block.type !== 'notification' &&
        block.type !== 'timer' &&
        block.type !== 'loading' &&
        block.type !== 'level' &&
        !isOptionsComponentType(block.type) &&
        block.label_style !== 'hidden'
    );
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
    return block.attention_emphasis
        ? 'ring-1 ring-offset-2 ring-offset-[#061635] ring-[#ef9a9a]/70 shadow-[0_0_0_2px_rgba(198,40,40,0.15)]'
        : '';
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

function previewNotificationVariation(
    block: StageBlock,
): NotificationVariation | undefined {
    const variations = block.notification_variations ?? [];

    if (variations.length === 0) {
        return undefined;
    }

    const intervalSeconds = Math.max(
        1,
        Number(block.notification_interval_seconds ?? 2),
    );
    const variationIndex =
        Math.floor(notificationPreviewTick.value / intervalSeconds) %
        variations.length;

    return variations[variationIndex] ?? variations[0];
}

function moveNotificationVariation(
    block: StageBlock,
    draggedId: string,
    targetId: string,
): void {
    const variations = block.notification_variations ?? [];
    const fromIndex = variations.findIndex(
        (variation) => variation.id === draggedId,
    );
    const toIndex = variations.findIndex(
        (variation) => variation.id === targetId,
    );

    if (fromIndex < 0 || toIndex < 0 || fromIndex === toIndex) {
        return;
    }

    const [movedVariation] = variations.splice(fromIndex, 1);
    variations.splice(toIndex, 0, movedVariation);
}

function onNotificationVariationDragStart(id: string): void {
    if (!props.permissions.canEdit) {
        return;
    }

    draggedNotificationVariationId.value = id;
}

function onNotificationVariationDragOver(id: string): void {
    if (
        !props.permissions.canEdit ||
        draggedNotificationVariationId.value === null ||
        draggedNotificationVariationId.value === id
    ) {
        return;
    }

    dragOverNotificationVariationId.value = id;
}

function onNotificationVariationDrop(
    block: StageBlock,
    targetId: string,
): void {
    if (
        !props.permissions.canEdit ||
        draggedNotificationVariationId.value === null
    ) {
        return;
    }

    moveNotificationVariation(
        block,
        draggedNotificationVariationId.value,
        targetId,
    );
    draggedNotificationVariationId.value = null;
    dragOverNotificationVariationId.value = null;
}

function onNotificationVariationDragEnd(): void {
    draggedNotificationVariationId.value = null;
    dragOverNotificationVariationId.value = null;
}

function addNotificationVariation(block: StageBlock): void {
    if (!props.permissions.canEdit || block.type !== 'notification') {
        return;
    }

    if (!Array.isArray(block.notification_variations)) {
        block.notification_variations = [];
    }

    block.notification_variations.push({
        id: createClientId(),
        value1: '',
        value2: '',
        value3: '',
        value4: '',
    });
}

function removeNotificationVariation(
    block: StageBlock,
    variationId: string,
): void {
    if (!props.permissions.canEdit || block.type !== 'notification') {
        return;
    }

    block.notification_variations = (
        block.notification_variations ?? []
    ).filter((variation) => variation.id !== variationId);

    if ((block.notification_variations?.length ?? 0) === 0) {
        block.notification_variations = createNotificationVariations().slice(
            0,
            1,
        );
    }
}

function toEmbedVideoUrl(url: string | undefined): string | null {
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
                const shortsId = parsed.pathname
                    .split('/')
                    .filter((segment) => segment.length > 0)[1];

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
            const segments = parsed.pathname
                .split('/')
                .filter((segment) => segment.length > 0);
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

function labelClass(block: StageBlock): string {
    if (block.label_style === 'muted') {
        return 'text-[#88a8df]';
    }

    return 'text-[#bad4ff]';
}

function blockWrapperStyle(block: StageBlock): Record<string, string> {
    const width = Math.max(
        25,
        Math.min(100, Number(block.width_percent ?? 100)),
    );
    const horizontal = block.align_horizontal ?? 'start';
    const textAlign =
        block.text_align === 'text-center'
            ? 'center'
            : block.text_align === 'text-right'
              ? 'right'
              : 'left';

    return {
        width: `${width}%`,
        marginLeft:
            horizontal === 'end'
                ? 'auto'
                : horizontal === 'center'
                  ? 'auto'
                  : '0',
        marginRight:
            horizontal === 'start'
                ? 'auto'
                : horizontal === 'center'
                  ? 'auto'
                  : '0',
        textAlign,
    };
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
        ? 'border-[#4f8fff] bg-[#102a56] p-2.5'
        : block.image_frame === 'none'
          ? 'border-transparent bg-transparent p-0'
          : 'border-[#2a4e88] bg-[#0b274f] p-2';
}

function imageFitClass(block: StageBlock): string {
    return block.image_fit === 'contain' ? 'object-contain' : 'object-cover';
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

    return 'px-3.5 py-2.5';
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

function normalizeOptionsStyle(
    style?: StageBlock['options_style'],
): 'simple' | 'highlight' | 'relief' | 'contrast' {
    if (style === 'highlight' || style === 'relief' || style === 'contrast') {
        return style;
    }

    if (style === 'cards') {
        return 'highlight';
    }

    return 'simple';
}

function optionsCardToneClass(block: StageBlock): string {
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

    return 'border-[#2a4e88] bg-[#0b274f]';
}

function optionsCardMinWidthClass(block: StageBlock): string {
    return block.options_layout === 'grid_2' ? '' : 'min-w-0';
}

function optionsDetailLabel(
    block: StageBlock,
    item: { points?: number; value?: string },
    itemIndex: number,
): string {
    const detail = normalizeDetailValue(block.options_detail);

    if (detail === 'checkout' || detail === 'none') {
        return '';
    }

    if (detail === 'points') {
        const points = Number(item.points);
        return Number.isFinite(points) && points > 0
            ? String(Math.round(points))
            : String(itemIndex + 1);
    }

    if (detail === 'value') {
        const value = (item.value ?? '').trim();
        return value !== ''
            ? value
            : String.fromCharCode(65 + (itemIndex % 26));
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
    const detail = normalizeDetailValue(block.options_detail);

    if (detail === 'arrow') {
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

function testimonialItems(block: StageBlock): OptionItem[] {
    if (block.type !== 'testimonials') {
        return [];
    }

    return normalizeTestimonialItems(block.option_items, block.options);
}

function ratingStars(rating?: number | null): number[] {
    const safeRating = Math.max(
        1,
        Math.min(
            5,
            Math.round(
                Number.isFinite(Number(rating ?? 5)) ? Number(rating ?? 5) : 5,
            ),
        ),
    );

    return Array.from({ length: safeRating }, (_, index) => index);
}

function testimonialGridClass(block: StageBlock): string {
    if (block.testimonials_layout === 'grid') {
        return 'grid gap-2 sm:grid-cols-2';
    }

    if (block.testimonials_layout === 'slide') {
        return 'flex gap-2 overflow-x-auto pb-1';
    }

    return 'space-y-2';
}

function faqDetailLabel(block: StageBlock, index: number): string {
    const detail = block.faq_detail ?? 'arrow';

    if (detail === 'none') {
        return '';
    }

    if (detail === 'chevron') {
        return '>';
    }

    if (detail === 'plus_minus') {
        return (block.faq_first_active ?? true) && index === 0 ? '-' : '+';
    }

    return '^';
}

function addDisplayRuleGroup(block: StageBlock): void {
    if (!props.permissions.canEdit) {
        return;
    }

    if (!block.display_rule_groups) {
        block.display_rule_groups = [];
    }

    block.display_rule_groups.push({
        id: createClientId(),
        mode: 'all',
        rules: [
            {
                id: createClientId(),
                source_block_id: '',
                operator: 'filled',
                value: '',
            },
        ],
    });
}

function addDisplayRule(block: StageBlock, groupIndex: number): void {
    if (
        !props.permissions.canEdit ||
        !block.display_rule_groups?.[groupIndex]
    ) {
        return;
    }

    block.display_rule_groups[groupIndex].rules.push({
        id: createClientId(),
        source_block_id: '',
        operator: 'filled',
        value: '',
    });
}

function removeDisplayRuleGroup(block: StageBlock, groupIndex: number): void {
    if (!props.permissions.canEdit || !block.display_rule_groups) {
        return;
    }

    block.display_rule_groups.splice(groupIndex, 1);
}

function removeDisplayRule(
    block: StageBlock,
    groupIndex: number,
    ruleIndex: number,
): void {
    if (
        !props.permissions.canEdit ||
        !block.display_rule_groups?.[groupIndex]
    ) {
        return;
    }

    block.display_rule_groups[groupIndex].rules.splice(ruleIndex, 1);
}

function displayRuleOperatorNeedsValue(
    operator: DisplayRule['operator'],
): boolean {
    return (
        operator === 'equals' ||
        operator === 'not_equals' ||
        operator === 'contains_any' ||
        operator === 'contains_all'
    );
}

function displayRuleBlockOptions(
    block: StageBlock,
): Array<{ value: string; label: string }> {
    const stage = currentStage.value;

    if (!stage) {
        return [];
    }

    return stage.blocks
        .filter(
            (candidate) =>
                candidate.id !== block.id && supportsRequired(candidate.type),
        )
        .map((candidate) => ({
            value: candidate.id,
            label: safeTrim(candidate.label) || blockTitle(candidate.type),
        }));
}

function isInputBlock(type: StageBlockType): boolean {
    return [
        'text',
        'email',
        'phone',
        'number',
        'date',
        'height',
        'address',
        'weight',
    ].includes(type);
}

function isTextAreaBlock(type: StageBlockType): boolean {
    return ['textarea', 'video_response'].includes(type);
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

function numericBlockPlaceholder(block: StageBlock, fallback: number): number {
    const parsedValue = Number(block.placeholder ?? fallback);

    return Number.isFinite(parsedValue) ? parsedValue : fallback;
}

function isMediaContentBlock(type: StageBlockType): boolean {
    return [
        'image',
        'video',
        'audio',
        'attention',
        'alert',
        'notification',
        'timer',
        'loading',
    ].includes(type);
}

function supportsPlaceholder(type: StageBlockType): boolean {
    return [
        'text',
        'email',
        'phone',
        'number',
        'textarea',
        'date',
        'height',
        'address',
        'weight',
        'content_text',
    ].includes(type);
}

function supportsRequired(type: StageBlockType): boolean {
    return ![
        'button',
        'spacer',
        'loading',
        'image',
        'video',
        'audio',
        'attention',
        'alert',
        'notification',
        'timer',
        'content_text',
        'level',
        'testimonials',
        'faq',
        'price',
        'carousel',
    ].includes(type);
}

function blockOptionsTitle(type: StageBlockType): string {
    if (type === 'faq') {
        return 'Perguntas';
    }

    if (type === 'testimonials') {
        return 'Depoimentos';
    }

    if (type === 'price') {
        return 'Planos';
    }

    if (type === 'before_after') {
        return 'Comparativos';
    }

    if (type === 'carousel') {
        return 'Slides';
    }

    if (type === 'metrics') {
        return 'Metricas';
    }

    if (type === 'arguments') {
        return 'Argumentos';
    }

    if (type === 'level') {
        return 'Niveis';
    }

    return 'Opcoes';
}

function addOptionLabel(type: StageBlockType): string {
    if (type === 'faq') {
        return 'Adicionar pergunta';
    }

    if (type === 'testimonials') {
        return 'Adicionar depoimento';
    }

    if (type === 'price') {
        return 'Adicionar plano';
    }

    if (type === 'before_after') {
        return 'Adicionar comparativo';
    }

    if (type === 'carousel') {
        return 'Adicionar slide';
    }

    if (type === 'metrics') {
        return 'Adicionar metrica';
    }

    if (type === 'arguments') {
        return 'Adicionar argumento';
    }

    return 'Adicionar opcao';
}

function minimumOptions(type: StageBlockType): number {
    if (type === 'yes_no' || type === 'before_after') {
        return 2;
    }

    return 1;
}

function normalizeOptionsBlock(block: StageBlock): void {
    if (!isOptionsComponentType(block.type)) {
        return;
    }

    block.option_items = (
        block.option_items ??
        createOptionItems(block.options ?? defaultOptionsLabels(block.type))
    ).map((item, index) => ({
        id: item.id ?? createClientId(),
        label: item.label?.trim() ?? '',
        points: Number.isFinite(Number(item.points)) ? Number(item.points) : 0,
        value: item.value?.trim() || String.fromCharCode(65 + (index % 26)),
        destination: item.destination?.trim() || 'next_stage',
        image_url: item.image_url?.trim() ?? '',
    }));
    block.options = block.option_items.map((item) => item.label);
    if (block.type === 'yes_no' && block.options.length < 2) {
        block.option_items = createOptionItems(
            defaultOptionsLabels(block.type),
        );
        block.options = block.option_items.map((item) => item.label);
    }
    if (block.type === 'yes_no') {
        block.option_items = block.option_items
            .slice(0, 2)
            .map((item, index) => ({
                ...item,
                label: normalizeYesNoOptionLabel(item.label, index),
            }));
        block.options = block.option_items.map((item) => item.label);
    }
    block.options_intro_type = block.options_intro_type ?? 'none';
    block.options_intro_title = block.options_intro_title ?? '';
    block.options_intro_description = block.options_intro_description ?? '';
    block.options_required_selection = block.options_required_selection ?? true;
    block.options_allow_multiple =
        block.type === 'single_choice'
            ? false
            : (block.options_allow_multiple ??
              defaultOptionsAllowMultiple(block.type));
    block.options_disable_auto_follow =
        block.options_disable_auto_follow ?? false;
    block.options_style = normalizeOptionsStyle(block.options_style);
    block.options_transparent_image = block.options_transparent_image ?? true;
    block.options_layout = block.options_layout ?? 'grid_2';
    block.options_orientation = block.options_orientation ?? 'vertical';
    block.options_image_ratio = block.options_image_ratio ?? '1:1';
    block.options_disposition = block.options_disposition ?? 'image_text';
    block.options_detail = block.options_detail
        ? normalizeDetailValue(block.options_detail)
        : defaultOptionsDetail(block.type);
    block.options_detail_position = block.options_detail_position ?? 'start';
    block.options_border_size = block.options_border_size ?? 'small';
    block.options_shadow = block.options_shadow ?? 'none';
    block.options_spacing = block.options_spacing ?? 'simple';
}

function normalizeTestimonialsBlock(block: StageBlock): void {
    if (block.type !== 'testimonials') {
        return;
    }

    const normalizedItems = normalizeTestimonialItems(
        block.option_items,
        block.options,
    );
    block.option_items = normalizedItems;
    block.options = normalizedItems.map(
        (item) =>
            `${item.label}|${item.subtitle ?? ''}|${item.rating ?? 5}|${item.description ?? ''}`,
    );
    block.testimonials_layout = block.testimonials_layout ?? 'list';
    block.options_border_size = block.options_border_size ?? 'small';
    block.options_shadow = block.options_shadow ?? 'none';
    block.options_spacing = block.options_spacing ?? 'simple';
}

function normalizeFaqBlock(block: StageBlock): void {
    if (block.type !== 'faq') {
        return;
    }

    const normalizedItems = normalizeFaqItems(
        block.option_items,
        block.options,
    );
    block.option_items = normalizedItems;
    block.options = normalizedItems.map((item) => item.label);
    block.faq_first_active = block.faq_first_active ?? true;
    block.faq_detail = block.faq_detail ?? 'arrow';
}

function normalizeCarouselBlock(block: StageBlock): void {
    if (block.type !== 'carousel') {
        return;
    }

    const normalizedItems = normalizeCarouselItems(
        block.option_items,
        block.options,
    );
    block.option_items = normalizedItems;
    block.options = normalizedItems.map(
        (item) => item.description ?? item.label,
    );
    block.carousel_layout = block.carousel_layout ?? 'image_text';
    block.carousel_pagination = block.carousel_pagination ?? true;
    block.carousel_autoplay = block.carousel_autoplay ?? false;
    block.carousel_autoplay_seconds = Math.max(
        1,
        Math.min(60, Number(block.carousel_autoplay_seconds ?? 3)),
    );
    block.carousel_border_type = block.carousel_border_type ?? 'none';
}

function normalizeMetricsBlock(block: StageBlock): void {
    if (block.type !== 'metrics') {
        return;
    }

    const normalizedItems = normalizeMetricItems(
        block.option_items,
        block.options,
    );
    block.option_items = normalizedItems;
    block.options = normalizedItems.map((item) => item.label);
    block.label = '';
}

function carouselPreviewItems(
    block: StageBlock,
): Array<{ id: string; image: string; description: string }> {
    if (block.type !== 'carousel') {
        return [];
    }

    return normalizeCarouselItems(block.option_items, block.options)
        .map((item, index) => ({
            id: item.id ?? `${block.id}-carousel-${index}`,
            image: safeTrim(item.value) || safeTrim(item.image_url),
            description: safeTrim(item.description),
        }))
        .filter((item) => item.image.length > 0 || item.description.length > 0);
}

function carouselPreviewIndex(block: StageBlock): number {
    const items = carouselPreviewItems(block);

    if (items.length === 0 || !block.carousel_autoplay) {
        return 0;
    }

    const autoplaySeconds = Math.max(
        1,
        Math.min(60, Number(block.carousel_autoplay_seconds ?? 3)),
    );

    return (
        Math.floor(notificationPreviewTick.value / autoplaySeconds) %
        items.length
    );
}

function currentCarouselPreviewItem(
    block: StageBlock,
): { id: string; image: string; description: string } | null {
    const items = carouselPreviewItems(block);

    return items[carouselPreviewIndex(block)] ?? items[0] ?? null;
}

function carouselShowsImage(block: StageBlock): boolean {
    return (block.carousel_layout ?? 'image_text') !== 'text_only';
}

function carouselShowsDescription(block: StageBlock): boolean {
    return (block.carousel_layout ?? 'image_text') !== 'image_only';
}

function metricItems(
    block: StageBlock,
): Array<{ id: string; label: string; value: string; description: string }> {
    return normalizeMetricItems(block.option_items, block.options)
        .map((item, index) => ({
            id: item.id ?? `${block.id}-metric-${index}`,
            label: item.label?.trim() ?? '',
            value: item.value?.trim() ?? '',
            description: item.description?.trim() ?? '',
        }))
        .filter(
            (item) =>
                item.label.length > 0 ||
                item.value.length > 0 ||
                item.description.length > 0,
        );
}

function normalizeDisplayRules(rules: unknown): DisplayRule[] {
    if (!Array.isArray(rules)) {
        return [];
    }

    return rules
        .map((rule): DisplayRule | null => {
            if (typeof rule === 'string') {
                const trimmed = rule.trim();

                if (trimmed.length === 0) {
                    return null;
                }

                const presenceMatch = trimmed.match(/^(filled|empty):(.+)$/i);

                if (presenceMatch) {
                    return {
                        id: createClientId(),
                        source_block_id: presenceMatch[2].trim(),
                        operator:
                            presenceMatch[1].toLowerCase() as DisplayRule['operator'],
                        value: '',
                    };
                }

                const comparisonMatch = trimmed.match(
                    /^([^!=:]+)\s*(=|!=)\s*(.+)$/,
                );

                if (!comparisonMatch) {
                    return null;
                }

                return {
                    id: createClientId(),
                    source_block_id: comparisonMatch[1].trim(),
                    operator:
                        comparisonMatch[2] === '!=' ? 'not_equals' : 'equals',
                    value: comparisonMatch[3].trim(),
                };
            }

            if (rule && typeof rule === 'object') {
                const sourceBlockId = String(
                    (rule as { source_block_id?: unknown }).source_block_id ??
                        '',
                ).trim();
                const operator = String(
                    (rule as { operator?: unknown }).operator ?? '',
                ).trim();
                const value = String(
                    (rule as { value?: unknown }).value ?? '',
                ).trim();

                if (
                    sourceBlockId === '' ||
                    ![
                        'filled',
                        'empty',
                        'equals',
                        'not_equals',
                        'contains_any',
                        'contains_all',
                    ].includes(operator)
                ) {
                    return null;
                }

                return {
                    id:
                        String((rule as { id?: unknown }).id ?? '').trim() ||
                        createClientId(),
                    source_block_id: sourceBlockId,
                    operator: operator as DisplayRule['operator'],
                    value,
                };
            }

            return null;
        })
        .filter((rule): rule is DisplayRule => rule !== null);
}

function normalizeDisplayRuleGroups(
    groups: unknown,
    legacyRules: unknown = [],
    fallbackMode: 'all' | 'any' = 'all',
): DisplayRuleGroup[] {
    if (Array.isArray(groups) && groups.length > 0) {
        return groups
            .map((group): DisplayRuleGroup | null => {
                if (!group || typeof group !== 'object') {
                    return null;
                }

                const rules = normalizeDisplayRules(
                    (group as { rules?: unknown }).rules ?? [],
                );

                if (rules.length === 0) {
                    return null;
                }

                const mode = String(
                    (group as { mode?: unknown }).mode ?? 'all',
                ).trim();

                return {
                    id:
                        String((group as { id?: unknown }).id ?? '').trim() ||
                        createClientId(),
                    mode: mode === 'any' ? 'any' : 'all',
                    rules,
                };
            })
            .filter((group): group is DisplayRuleGroup => group !== null);
    }

    const normalizedLegacyRules = normalizeDisplayRules(legacyRules);

    if (normalizedLegacyRules.length === 0) {
        return [];
    }

    return [
        {
            id: createClientId(),
            mode: fallbackMode === 'any' ? 'any' : 'all',
            rules: normalizedLegacyRules,
        },
    ];
}

function optionsDisplayItems(block: StageBlock): Array<{
    id: string;
    label: string;
    points: number;
    value: string;
    destination: string;
    image_url: string;
}> {
    if (Array.isArray(block.option_items) && block.option_items.length > 0) {
        return block.option_items.map((item, index) => ({
            id: item.id ?? `${block.id}-option-${index}`,
            label: safeTrim(item.label),
            points: Number.isFinite(Number(item.points))
                ? Number(item.points)
                : index + 1,
            value:
                safeTrim(item.value) || String.fromCharCode(65 + (index % 26)),
            destination: safeTrim(item.destination) || 'next_stage',
            image_url: safeTrim(item.image_url),
        }));
    }

    return (block.options ?? []).map((label, index) => ({
        id: `${block.id}-fallback-${index}`,
        label: safeTrim(label),
        points: index + 1,
        value: String.fromCharCode(65 + (index % 26)),
        destination: 'next_stage',
        image_url: '',
    }));
}

function hasOptionsIntroContent(block: StageBlock): boolean {
    if ((block.options_intro_type ?? 'none') === 'none') {
        return false;
    }

    return (
        safeTrim(block.options_intro_title).length > 0 ||
        safeTrim(block.options_intro_description).length > 0
    );
}

function optionsShouldRenderImage(
    block: StageBlock,
    item: { image_url: string },
): boolean {
    return (
        (block.options_disposition ?? 'image_text') !== 'text' &&
        item.image_url.length > 0
    );
}

function optionsMediaOrderClass(block: StageBlock): string {
    return (block.options_disposition ?? 'image_text') === 'text_image'
        ? 'order-2'
        : 'order-1';
}

function optionsLabelOrderClass(block: StageBlock): string {
    return (block.options_disposition ?? 'image_text') === 'text_image'
        ? 'order-1'
        : 'order-2';
}

function optionsBodyClass(block: StageBlock): string {
    return block.options_layout === 'grid_1'
        ? 'flex items-center gap-3'
        : 'flex items-center gap-2';
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
        block.options_transparent_image ? 'bg-transparent' : 'bg-white/10',
        'overflow-hidden rounded-lg border border-[#8fa9d9]/35',
    ].join(' ');
}

function optionsDetailWrapClass(block: StageBlock): string {
    return block.options_detail_position === 'end'
        ? 'order-3 ml-auto'
        : 'order-1';
}

function argumentItems(block: StageBlock): string[] {
    return (block.options ?? [])
        .map((item) => safeTrim(item))
        .filter((item) => item.length > 0);
}

function beforeAfterItems(
    block: StageBlock,
): Array<{ label: string; value: string }> {
    return [
        { label: 'Antes', value: safeTrim(block.options?.[0]) },
        { label: 'Depois', value: safeTrim(block.options?.[1]) },
    ].filter((item) => item.value.length > 0);
}

function placeholderLabel(type: StageBlockType): string {
    if (type === 'timer') {
        return 'Tempo (mm:ss)';
    }

    if (type === 'content_text') {
        return 'Texto';
    }

    return 'Placeholder';
}

function changeBlockType(block: StageBlock, nextType: StageBlockType): void {
    if (!props.permissions.canEdit) {
        return;
    }

    const previousType = block.type;
    const previousOptions = block.options ? [...block.options] : undefined;
    const previousOptionItems = block.option_items
        ? block.option_items.map((item) => ({ ...item }))
        : undefined;
    const previousOptionsIntroType = block.options_intro_type;
    const previousOptionsIntroTitle = block.options_intro_title;
    const previousOptionsIntroDescription = block.options_intro_description;
    const previousOptionsRequiredSelection = block.options_required_selection;
    const previousOptionsAllowMultiple = block.options_allow_multiple;
    const previousOptionsDisableAutoFollow = block.options_disable_auto_follow;
    const previousOptionsStyle = block.options_style;
    const previousOptionsTransparentImage = block.options_transparent_image;
    const previousOptionsLayout = block.options_layout;
    const previousOptionsOrientation = block.options_orientation;
    const previousOptionsImageRatio = block.options_image_ratio;
    const previousOptionsDisposition = block.options_disposition;
    const previousOptionsDetail = block.options_detail;
    const previousOptionsDetailPosition = block.options_detail_position;
    const previousOptionsBorderSize = block.options_border_size;
    const previousOptionsShadow = block.options_shadow;
    const previousOptionsSpacing = block.options_spacing;
    const template = createBlock(nextType);
    block.type = nextType;

    if (supportsPlaceholder(nextType)) {
        block.placeholder = template.placeholder ?? block.placeholder ?? '';
    } else {
        delete block.placeholder;
    }

    if (isChoiceBlock(nextType)) {
        block.options = template.options
            ? [...template.options]
            : defaultOptionsLabels(nextType);
    } else {
        delete block.options;
    }

    if (isOptionsComponentType(nextType)) {
        const preserveOptionsState = isOptionsComponentType(previousType);

        block.option_items = preserveOptionsState
            ? previousOptionItems?.map((item) => ({ ...item }))
            : template.option_items
              ? template.option_items.map((item) => ({ ...item }))
              : createOptionItems(
                    block.options ?? defaultOptionsLabels(nextType),
                );
        block.options = preserveOptionsState
            ? [
                  ...(previousOptions ??
                      block.option_items?.map((item) => item.label) ??
                      defaultOptionsLabels(nextType)),
              ]
            : (block.options ?? defaultOptionsLabels(nextType));
        block.options_intro_type = preserveOptionsState
            ? (previousOptionsIntroType ?? 'none')
            : (template.options_intro_type ?? 'none');
        block.options_intro_title = preserveOptionsState
            ? (previousOptionsIntroTitle ?? '')
            : (template.options_intro_title ?? '');
        block.options_intro_description = preserveOptionsState
            ? (previousOptionsIntroDescription ?? '')
            : (template.options_intro_description ?? '');
        block.options_required_selection = preserveOptionsState
            ? (previousOptionsRequiredSelection ?? true)
            : (template.options_required_selection ?? true);
        block.options_allow_multiple =
            nextType === 'single_choice'
                ? false
                : preserveOptionsState
                  ? (previousOptionsAllowMultiple ??
                    defaultOptionsAllowMultiple(nextType))
                  : (template.options_allow_multiple ??
                    defaultOptionsAllowMultiple(nextType));
        block.options_disable_auto_follow = preserveOptionsState
            ? (previousOptionsDisableAutoFollow ?? false)
            : (template.options_disable_auto_follow ?? false);
        block.options_style = preserveOptionsState
            ? normalizeOptionsStyle(previousOptionsStyle)
            : normalizeOptionsStyle(template.options_style);
        block.options_transparent_image = preserveOptionsState
            ? (previousOptionsTransparentImage ?? true)
            : (template.options_transparent_image ?? true);
        block.options_layout = preserveOptionsState
            ? (previousOptionsLayout ?? 'grid_2')
            : (template.options_layout ?? 'grid_2');
        block.options_orientation = preserveOptionsState
            ? (previousOptionsOrientation ?? 'vertical')
            : (template.options_orientation ?? 'vertical');
        block.options_image_ratio = preserveOptionsState
            ? (previousOptionsImageRatio ?? '1:1')
            : (template.options_image_ratio ?? '1:1');
        block.options_disposition = preserveOptionsState
            ? (previousOptionsDisposition ?? 'image_text')
            : (template.options_disposition ?? 'image_text');
        block.options_detail = preserveOptionsState
            ? previousOptionsDetail
                ? normalizeDetailValue(previousOptionsDetail)
                : defaultOptionsDetail(nextType)
            : template.options_detail
              ? normalizeDetailValue(template.options_detail)
              : defaultOptionsDetail(nextType);
        block.options_detail_position = preserveOptionsState
            ? (previousOptionsDetailPosition ?? 'start')
            : (template.options_detail_position ?? 'start');
        block.options_border_size = preserveOptionsState
            ? (previousOptionsBorderSize ?? 'small')
            : (template.options_border_size ?? 'small');
        block.options_shadow = preserveOptionsState
            ? (previousOptionsShadow ?? 'none')
            : (template.options_shadow ?? 'none');
        block.options_spacing = preserveOptionsState
            ? (previousOptionsSpacing ?? 'simple')
            : (template.options_spacing ?? 'simple');
        normalizeOptionsBlock(block);
    } else if (nextType === 'testimonials') {
        block.option_items = template.option_items
            ? template.option_items.map((item) => ({ ...item }))
            : [];
        block.options_border_size = template.options_border_size ?? 'small';
        block.options_shadow = template.options_shadow ?? 'none';
        block.options_spacing = template.options_spacing ?? 'simple';
        block.testimonials_layout = template.testimonials_layout ?? 'list';
        normalizeTestimonialsBlock(block);
    } else if (nextType === 'faq') {
        block.option_items = template.option_items
            ? template.option_items.map((item) => ({ ...item }))
            : [];
        block.faq_first_active = template.faq_first_active ?? true;
        block.faq_detail = template.faq_detail ?? 'arrow';
        normalizeFaqBlock(block);
    } else if (nextType === 'price') {
        block.price_title = template.price_title ?? '';
        block.price_prefix = template.price_prefix ?? '';
        block.price_value = template.price_value ?? '';
        block.price_suffix = template.price_suffix ?? '';
        block.price_badge_text = template.price_badge_text ?? '';
        block.price_mode = template.price_mode ?? 'illustrative';
        block.price_layout = template.price_layout ?? 'horizontal';
        block.price_style = template.price_style ?? 'theme';
        block.price_link = template.price_link ?? '';
    } else if (nextType === 'carousel') {
        block.option_items = template.option_items
            ? template.option_items.map((item) => ({ ...item }))
            : [];
        block.carousel_layout = template.carousel_layout ?? 'image_text';
        block.carousel_pagination = template.carousel_pagination ?? true;
        block.carousel_autoplay = template.carousel_autoplay ?? false;
        block.carousel_autoplay_seconds =
            template.carousel_autoplay_seconds ?? 3;
        block.carousel_border_type = template.carousel_border_type ?? 'none';
        normalizeCarouselBlock(block);
    } else if (nextType === 'metrics') {
        block.label = '';
        block.option_items = template.option_items
            ? template.option_items.map((item) => ({ ...item }))
            : [
                  {
                      id: createClientId(),
                      label: '',
                      value: '',
                      description: '',
                      points: 0,
                      destination: '',
                  },
              ];
        normalizeMetricsBlock(block);
    } else {
        delete block.option_items;
        delete block.options_intro_type;
        delete block.options_intro_title;
        delete block.options_intro_description;
        delete block.options_required_selection;
        delete block.options_allow_multiple;
        delete block.options_disable_auto_follow;
        delete block.options_style;
        delete block.options_transparent_image;
        delete block.options_layout;
        delete block.options_orientation;
        delete block.options_image_ratio;
        delete block.options_disposition;
        delete block.options_detail;
        delete block.options_detail_position;
        delete block.options_border_size;
        delete block.options_shadow;
        delete block.options_spacing;
        delete block.testimonials_layout;
        delete block.faq_first_active;
        delete block.faq_detail;
        delete block.price_title;
        delete block.price_prefix;
        delete block.price_value;
        delete block.price_suffix;
        delete block.price_badge_text;
        delete block.price_mode;
        delete block.price_layout;
        delete block.price_style;
        delete block.price_link;
        delete block.carousel_layout;
        delete block.carousel_pagination;
        delete block.carousel_autoplay;
        delete block.carousel_autoplay_seconds;
        delete block.carousel_border_type;
    }

    if (!supportsRequired(nextType)) {
        block.required = false;
    }

    if (nextType === 'button') {
        block.button_action = template.button_action ?? 'next_stage';
        block.button_target_stage_order =
            template.button_target_stage_order ?? 'next';
        block.button_link = template.button_link ?? '';
        block.button_open_new_tab = template.button_open_new_tab ?? true;
        block.button_color_style = template.button_color_style ?? 'theme';
        block.button_animated = template.button_animated ?? false;
        block.button_elevated = template.button_elevated ?? false;
        block.button_sticky_footer = template.button_sticky_footer ?? false;
    } else {
        delete block.button_action;
        delete block.button_target_stage_order;
        delete block.button_link;
        delete block.button_open_new_tab;
        delete block.button_color_style;
        delete block.button_animated;
        delete block.button_elevated;
        delete block.button_sticky_footer;
    }

    if (nextType === 'phone') {
        block.phone_mask = template.phone_mask ?? 'br';
    } else {
        delete block.phone_mask;
    }

    if (nextType === 'number') {
        block.number_mask = template.number_mask ?? 'decimal';
    } else {
        delete block.number_mask;
    }

    if (nextType === 'height') {
        block.height_mode = template.height_mode ?? 'ruler';
        block.placeholder = template.placeholder ?? block.placeholder ?? '170';
    } else {
        delete block.height_mode;
    }

    if (nextType === 'weight') {
        block.weight_mode = template.weight_mode ?? 'ruler';
        block.placeholder = template.placeholder ?? block.placeholder ?? '70';
    } else {
        delete block.weight_mode;
    }

    if (nextType === 'video') {
        block.video_ratio = template.video_ratio ?? '16:9';
        block.placeholder = template.placeholder ?? '';
    } else {
        delete block.video_ratio;
    }

    if (nextType === 'audio') {
        block.label = '';
        block.audio_sender = template.audio_sender ?? '';
        block.audio_src = template.audio_src ?? '';
        block.audio_avatar_url = template.audio_avatar_url ?? '';
        block.audio_model = template.audio_model ?? 'whatsapp';
        block.audio_theme = template.audio_theme ?? 'light';
    } else {
        delete block.audio_sender;
        delete block.audio_src;
        delete block.audio_avatar_url;
        delete block.audio_model;
        delete block.audio_theme;
    }

    if (nextType === 'attention') {
        block.label = '';
        block.placeholder = template.placeholder ?? '';
        block.attention_style = template.attention_style ?? 'red';
        block.attention_emphasis = template.attention_emphasis ?? false;
        block.attention_padding = template.attention_padding ?? 'default';
    } else {
        delete block.attention_style;
        delete block.attention_emphasis;
        delete block.attention_padding;
    }

    if (nextType === 'notification') {
        block.label = '';
        block.notification_title =
            template.notification_title ?? defaultNotificationTitle();
        block.notification_description =
            template.notification_description ??
            defaultNotificationDescription();
        block.notification_avatar_url =
            template.notification_avatar_url ?? defaultNotificationAvatarUrl();
        block.notification_position =
            template.notification_position ?? 'default';
        block.notification_duration_seconds = Number(
            template.notification_duration_seconds ?? 5,
        );
        block.notification_interval_seconds = Number(
            template.notification_interval_seconds ?? 2,
        );
        block.notification_style = template.notification_style ?? 'white';
        block.notification_size = template.notification_size ?? 'default';
        block.notification_variant = template.notification_variant ?? 'social';
        block.notification_variations =
            (template.notification_variations?.length ?? 0) > 0
                ? template.notification_variations
                : createNotificationVariations(true);
    } else {
        delete block.notification_title;
        delete block.notification_description;
        delete block.notification_avatar_url;
        delete block.notification_position;
        delete block.notification_duration_seconds;
        delete block.notification_interval_seconds;
        delete block.notification_style;
        delete block.notification_size;
        delete block.notification_variant;
        delete block.notification_variations;
    }

    if (nextType === 'timer') {
        block.label = '';
        block.timer_seconds = Number(
            template.timer_seconds ??
                parseLegacyTimerSeconds(template.placeholder),
        );
        block.timer_text = template.timer_text ?? template.placeholder ?? '';
        block.timer_style = template.timer_style ?? 'red';
        block.placeholder = block.timer_text;
    } else {
        delete block.timer_seconds;
        delete block.timer_text;
        delete block.timer_style;
    }

    if (nextType === 'loading') {
        block.label = template.label ?? '';
        block.placeholder = template.placeholder ?? '';
        block.loading_start_seconds = Number(
            template.loading_start_seconds ?? 0,
        );
        block.loading_duration_seconds = Number(
            template.loading_duration_seconds ?? 5,
        );
        block.loading_navigation_action =
            template.loading_navigation_action ?? 'none';
        block.loading_target_stage_order =
            template.loading_target_stage_order ?? 'next';
        block.loading_link = template.loading_link ?? '';
        block.loading_show_title = template.loading_show_title ?? true;
        block.loading_show_progress = template.loading_show_progress ?? true;
    } else {
        delete block.loading_start_seconds;
        delete block.loading_duration_seconds;
        delete block.loading_navigation_action;
        delete block.loading_target_stage_order;
        delete block.loading_link;
        delete block.loading_show_title;
        delete block.loading_show_progress;
    }

    if (nextType === 'level') {
        block.label = '';
        block.placeholder = '';
        block.level_title = template.level_title ?? '';
        block.level_subtitle = template.level_subtitle ?? '';
        block.level_percentage = Math.max(
            0,
            Math.min(100, Number(template.level_percentage ?? 0)),
        );
        block.level_indicator_text = template.level_indicator_text ?? '';
        block.level_legends = template.level_legends ?? '';
        block.level_show_meter = template.level_show_meter ?? true;
        block.level_show_progress = template.level_show_progress ?? true;
        block.level_type = template.level_type ?? 'line';
        block.level_color = template.level_color ?? 'theme';
    } else {
        delete block.level_title;
        delete block.level_subtitle;
        delete block.level_percentage;
        delete block.level_indicator_text;
        delete block.level_legends;
        delete block.level_show_meter;
        delete block.level_show_progress;
        delete block.level_type;
        delete block.level_color;
    }

    block.label_style = block.label_style ?? 'default';
    block.text_align = block.text_align ?? 'text-left';
    block.width_percent = Number(block.width_percent ?? 100);
    block.align_horizontal = block.align_horizontal ?? 'start';
    block.align_vertical = block.align_vertical ?? 'start';
    block.show_after_seconds =
        nextType === 'email' ? null : (block.show_after_seconds ?? null);
    block.display_rules =
        nextType === 'email' ? [] : (block.display_rules ?? []);
}

function onButtonActionChange(block: StageBlock): void {
    if (block.type !== 'button') {
        return;
    }

    if (block.button_action === 'open_link') {
        block.button_link = block.button_link ?? '';
        block.button_target_stage_order = 'next';

        return;
    }

    block.button_target_stage_order = block.button_target_stage_order ?? 'next';
}

function onLoadingNavigationChange(block: StageBlock): void {
    if (block.type !== 'loading') {
        return;
    }

    if (block.loading_navigation_action === 'none') {
        block.loading_link = '';
        block.loading_target_stage_order = 'next';

        return;
    }

    if (block.loading_navigation_action === 'open_link') {
        block.loading_link = block.loading_link ?? '';
        block.loading_target_stage_order = 'next';

        return;
    }

    block.loading_target_stage_order =
        block.loading_target_stage_order ?? 'next';
}

function addBlock(type: StageBlockType): void {
    if (!props.permissions.canEdit || !currentStage.value) {
        return;
    }

    const block = createBlock(type);
    currentStage.value.blocks.push(block);
    selectedBlockId.value = block.id;
    mobileBuilderPanel.value = 'settings';
}

function onPaletteDragStart(type: StageBlockType): void {
    if (!props.permissions.canEdit) {
        return;
    }

    draggedPaletteType.value = type;
}

function onPaletteDragEnd(): void {
    draggedPaletteType.value = null;
}

function onBlocksPanelDrop(): void {
    if (
        !props.permissions.canEdit ||
        !currentStage.value ||
        draggedPaletteType.value === null
    ) {
        return;
    }

    const block = createBlock(draggedPaletteType.value);
    currentStage.value.blocks.push(block);
    selectedBlockId.value = block.id;
    mobileBuilderPanel.value = 'settings';
    draggedPaletteType.value = null;
}

function removeBlock(blockId: string): void {
    if (!props.permissions.canEdit || !currentStage.value) {
        return;
    }

    currentStage.value.blocks = currentStage.value.blocks.filter(
        (block) => block.id !== blockId,
    );

    if (selectedBlockId.value === blockId) {
        selectedBlockId.value = null;
    }
}

function duplicateBlock(blockId: string): void {
    if (!props.permissions.canEdit || !currentStage.value) {
        return;
    }

    const sourceIndex = currentStage.value.blocks.findIndex(
        (block) => block.id === blockId,
    );

    if (sourceIndex < 0) {
        return;
    }

    const source = currentStage.value.blocks[sourceIndex];
    const duplicated: StageBlock = {
        ...cloneBlock(source),
        id: createClientId(),
        options: source.options ? [...source.options] : undefined,
    };

    currentStage.value.blocks.splice(sourceIndex + 1, 0, duplicated);
    selectedBlockId.value = duplicated.id;
}

function moveBlock(blockId: string, direction: 'up' | 'down'): void {
    if (!props.permissions.canEdit || !currentStage.value) {
        return;
    }

    const index = currentStage.value.blocks.findIndex(
        (block) => block.id === blockId,
    );

    if (index < 0) {
        return;
    }

    const targetIndex = direction === 'up' ? index - 1 : index + 1;

    if (targetIndex < 0 || targetIndex >= currentStage.value.blocks.length) {
        return;
    }

    const [block] = currentStage.value.blocks.splice(index, 1);
    currentStage.value.blocks.splice(targetIndex, 0, block);
    selectedBlockId.value = block.id;
}

function moveSelectedBlock(
    direction: 'up' | 'down',
    options: { showToast?: boolean } = {},
): void {
    if (!selectedBlockId.value || !currentStage.value) {
        return;
    }

    const index = currentStage.value.blocks.findIndex(
        (block) => block.id === selectedBlockId.value,
    );

    if (index < 0) {
        return;
    }

    const targetIndex = direction === 'up' ? index - 1 : index + 1;

    if (targetIndex < 0 || targetIndex >= currentStage.value.blocks.length) {
        return;
    }

    moveBlock(selectedBlockId.value, direction);

    if (options.showToast) {
        showActionToast(
            direction === 'up'
                ? 'Bloco movido para cima'
                : 'Bloco movido para baixo',
            'success',
        );
    }
}

function onBlockDragStart(blockId: string): void {
    if (!props.permissions.canEdit) {
        return;
    }

    draggedBlockId.value = blockId;
}

function onBlockDragOver(blockId: string): void {
    if (!props.permissions.canEdit) {
        dragOverBlockId.value = null;

        return;
    }

    if (draggedPaletteType.value !== null) {
        dragOverBlockId.value = blockId;

        return;
    }

    if (draggedBlockId.value === null || draggedBlockId.value === blockId) {
        dragOverBlockId.value = null;

        return;
    }

    dragOverBlockId.value = blockId;
}

function onBlockDrop(targetBlockId: string): void {
    if (!props.permissions.canEdit || !currentStage.value) {
        return;
    }

    if (draggedPaletteType.value !== null) {
        const targetIndex = currentStage.value.blocks.findIndex(
            (block) => block.id === targetBlockId,
        );

        if (targetIndex < 0) {
            draggedPaletteType.value = null;
            dragOverBlockId.value = null;

            return;
        }

        currentStage.value.blocks.splice(
            targetIndex,
            0,
            createBlock(draggedPaletteType.value),
        );
        selectedBlockId.value =
            currentStage.value.blocks[targetIndex]?.id ?? null;
        draggedPaletteType.value = null;
        dragOverBlockId.value = null;

        return;
    }

    if (draggedBlockId.value === null) {
        return;
    }

    const sourceIndex = currentStage.value.blocks.findIndex(
        (block) => block.id === draggedBlockId.value,
    );
    const targetIndex = currentStage.value.blocks.findIndex(
        (block) => block.id === targetBlockId,
    );

    if (sourceIndex < 0 || targetIndex < 0 || sourceIndex === targetIndex) {
        draggedBlockId.value = null;
        dragOverBlockId.value = null;

        return;
    }

    const [movedBlock] = currentStage.value.blocks.splice(sourceIndex, 1);
    currentStage.value.blocks.splice(targetIndex, 0, movedBlock);
    selectedBlockId.value = movedBlock.id;
    draggedBlockId.value = null;
    dragOverBlockId.value = null;
}

function onBlockDragEnd(): void {
    draggedBlockId.value = null;
    dragOverBlockId.value = null;
}

function addBlockOption(block: StageBlock): void {
    if (!props.permissions.canEdit) {
        return;
    }

    if (!block.options) {
        block.options = [];
    }

    if (isOptionsComponentType(block.type)) {
        normalizeOptionsBlock(block);
        const nextIndex = (block.option_items?.length ?? 0) + 1;
        block.option_items?.push({
            id: createClientId(),
            label: '',
            points: 0,
            value: String.fromCharCode(65 + ((nextIndex - 1) % 26)),
            destination: 'next_stage',
            image_url: '',
        });
        block.options =
            block.option_items?.map((item) => item.label) ?? block.options;
        expandedOptionItemId.value =
            block.option_items?.[block.option_items.length - 1]?.id ?? null;

        return;
    }

    if (block.type === 'testimonials') {
        normalizeTestimonialsBlock(block);
        block.option_items?.push({
            id: createClientId(),
            label: '',
            subtitle: '',
            description: '',
            rating: 5,
            points: 5,
            value: '',
            destination: '',
        });
        normalizeTestimonialsBlock(block);
        expandedOptionItemId.value =
            block.option_items?.[block.option_items.length - 1]?.id ?? null;

        return;
    }

    if (block.type === 'faq') {
        normalizeFaqBlock(block);
        block.option_items?.push({
            id: createClientId(),
            label: '',
            description: '',
            points: 0,
            value: '',
            destination: '',
        });
        normalizeFaqBlock(block);
        return;
    }

    if (block.type === 'carousel') {
        normalizeCarouselBlock(block);
        block.option_items?.push({
            id: createClientId(),
            label: '',
            value: '',
            description: '',
            points: 0,
            destination: '',
        });
        normalizeCarouselBlock(block);
        return;
    }

    if (block.type === 'metrics') {
        normalizeMetricsBlock(block);
        block.option_items?.push({
            id: createClientId(),
            label: '',
            value: '',
            description: '',
            points: 0,
            destination: '',
        });
        normalizeMetricsBlock(block);
        return;
    }

    if (block.type === 'price') {
        block.options.push('');

        return;
    }

    if (block.type === 'before_after') {
        block.options.push('');

        return;
    }

    if (block.type === 'arguments') {
        block.options.push('');

        return;
    }

    block.options.push('');
}

function removeBlockOption(block: StageBlock, optionIndex: number): void {
    if (!props.permissions.canEdit || !block.options) {
        return;
    }

    if (isOptionsComponentType(block.type)) {
        normalizeOptionsBlock(block);
        if ((block.option_items?.length ?? 0) <= minimumOptions(block.type)) {
            return;
        }
        const removedItem = block.option_items?.[optionIndex];
        block.option_items?.splice(optionIndex, 1);
        block.options = block.option_items?.map((item) => item.label) ?? [];

        if (removedItem && expandedOptionItemId.value === removedItem.id) {
            expandedOptionItemId.value = null;
        }

        return;
    }

    if (block.type === 'testimonials') {
        normalizeTestimonialsBlock(block);
        if ((block.option_items?.length ?? 0) <= 1) {
            return;
        }
        const removedItem = block.option_items?.[optionIndex];
        block.option_items?.splice(optionIndex, 1);
        normalizeTestimonialsBlock(block);

        if (removedItem && expandedOptionItemId.value === removedItem.id) {
            expandedOptionItemId.value = null;
        }

        return;
    }

    if (block.type === 'faq') {
        normalizeFaqBlock(block);
        if ((block.option_items?.length ?? 0) <= 1) {
            return;
        }
        block.option_items?.splice(optionIndex, 1);
        normalizeFaqBlock(block);
        return;
    }

    if (block.type === 'carousel') {
        normalizeCarouselBlock(block);
        if ((block.option_items?.length ?? 0) <= 1) {
            return;
        }
        block.option_items?.splice(optionIndex, 1);
        normalizeCarouselBlock(block);
        return;
    }

    if (block.type === 'metrics') {
        normalizeMetricsBlock(block);
        if ((block.option_items?.length ?? 0) <= 1) {
            return;
        }
        block.option_items?.splice(optionIndex, 1);
        normalizeMetricsBlock(block);
        return;
    }

    block.options.splice(optionIndex, 1);
}

function blockTitle(type: StageBlockType): string {
    return blockCatalog.find((item) => item.type === type)?.label ?? 'Campo';
}

function addStage(): void {
    if (!props.permissions.canEdit) {
        return;
    }

    const stageNumber = localStages.value.length + 1;
    const stage = {
        id: null,
        clientId: createClientId(),
        name: `Etapa ${stageNumber}`,
        conversion_rate: '',
        expected_volume: '',
        header: {
            show_logo: true,
            show_progress: true,
            allow_back: true,
        },
        title: '',
        subtitle: '',
        buttonText: '',
        stageButtonAction: 'next_stage',
        stageButtonTargetStageOrder: 'next',
        stageButtonLink: '',
        stageButtonOpenNewTab: false,
        stageButtonColorStyle: 'theme',
        stageButtonAnimated: false,
        stageButtonElevated: false,
        stageButtonStickyFooter: false,
        blocks: [],
    } satisfies StageDraft;

    localStages.value.push(stage);
    selectedStageKey.value = stage.clientId;
}

function duplicateStageByClientId(stageClientId: string): void {
    if (!props.permissions.canEdit) {
        return;
    }

    const index = localStages.value.findIndex(
        (stage) => stage.clientId === stageClientId,
    );

    if (index < 0) {
        return;
    }

    const source = localStages.value[index];
    const duplicated: StageDraft = {
        ...cloneStage(source),
        id: null,
        clientId: createClientId(),
        name: `${source.name} copia`,
        blocks: source.blocks.map((block) => ({
            ...cloneBlock(block),
            id: createClientId(),
            option_items: block.option_items?.map((item) => ({
                ...item,
                id: createClientId(),
            })),
            notification_variations: block.notification_variations?.map(
                (variation) => ({
                    ...variation,
                    id: createClientId(),
                }),
            ),
        })),
    };

    localStages.value.splice(index + 1, 0, duplicated);
    selectedStageKey.value = duplicated.clientId;
    openStageMenuKey.value = null;
}

function removeStageByClientId(stageClientId: string): void {
    if (!props.permissions.canEdit || localStages.value.length <= 2) {
        return;
    }

    const index = localStages.value.findIndex(
        (stage) => stage.clientId === stageClientId,
    );

    if (index < 0) {
        return;
    }

    localStages.value.splice(index, 1);

    if (selectedStageKey.value === stageClientId) {
        const nextStage =
            localStages.value[index] ??
            localStages.value[index - 1] ??
            localStages.value[0] ??
            null;
        selectedStageKey.value = nextStage?.clientId ?? '';
    }

    openStageMenuKey.value = null;
}

function toggleStageMenu(stageClientId: string): void {
    openStageMenuKey.value =
        openStageMenuKey.value === stageClientId ? null : stageClientId;
}

function onStageDragStart(stageKey: string): void {
    if (!props.permissions.canEdit) {
        return;
    }

    draggedStageKey.value = stageKey;
}

function onStageDragOver(stageKey: string): void {
    if (!props.permissions.canEdit) {
        dragOverStageKey.value = null;

        return;
    }

    if (draggedStageKey.value === null || draggedStageKey.value === stageKey) {
        dragOverStageKey.value = null;

        return;
    }

    dragOverStageKey.value = stageKey;
}

function onStageDrop(targetStageKey: string): void {
    if (!props.permissions.canEdit) {
        return;
    }

    if (draggedStageKey.value === null) {
        return;
    }

    const sourceIndex = localStages.value.findIndex(
        (stage) => stage.clientId === draggedStageKey.value,
    );
    const targetIndex = localStages.value.findIndex(
        (stage) => stage.clientId === targetStageKey,
    );

    if (sourceIndex < 0 || targetIndex < 0 || sourceIndex === targetIndex) {
        draggedStageKey.value = null;
        dragOverStageKey.value = null;

        return;
    }

    const [movedStage] = localStages.value.splice(sourceIndex, 1);
    localStages.value.splice(targetIndex, 0, movedStage);
    selectedStageKey.value = movedStage.clientId;
    draggedStageKey.value = null;
    dragOverStageKey.value = null;
}

function onStageDragEnd(): void {
    draggedStageKey.value = null;
    dragOverStageKey.value = null;
}

function copyBuilderLink(): void {
    const url = `${window.location.origin}/f/${props.funnel.slug}`;

    navigator.clipboard
        .writeText(url)
        .then(() => {
            copiedLink.value = true;
            window.setTimeout(() => {
                copiedLink.value = false;
            }, 1500);
        })
        .catch(() => {
            copiedLink.value = false;
        });
}

function buildStagesPayload(): Array<{
    id?: number;
    name: string;
    conversion_rate: number | null;
    expected_volume: number | null;
    meta: {
        header: StageHeaderSettings;
        builder: {
            title: string;
            subtitle: string;
            button_text: string;
            stage_button_action: 'next_stage' | 'open_link';
            stage_button_target_stage_order: string | null;
            stage_button_link: string;
            stage_button_open_new_tab: boolean;
            stage_button_color_style: 'theme' | 'dark' | 'light';
            stage_button_animated: boolean;
            stage_button_elevated: boolean;
            stage_button_sticky_footer: boolean;
            blocks: FormDataConvertible[];
        };
    };
}> {
    return localStages.value.map((stage, index) => {
        const basePayload = {
            name: stage.name.trim() || `Etapa ${index + 1}`,
            conversion_rate:
                stage.conversion_rate.length > 0
                    ? Number(stage.conversion_rate)
                    : null,
            expected_volume:
                stage.expected_volume.length > 0
                    ? Number(stage.expected_volume)
                    : null,
            meta: {
                header: stage.header,
                builder: {
                    title: safeTrim(stage.title),
                    subtitle: safeTrim(stage.subtitle),
                    button_text: safeTrim(stage.buttonText),
                    stage_button_action: stage.stageButtonAction,
                    stage_button_target_stage_order:
                        stage.stageButtonTargetStageOrder,
                    stage_button_link: safeTrim(stage.stageButtonLink),
                    stage_button_open_new_tab: stage.stageButtonOpenNewTab,
                    stage_button_color_style: stage.stageButtonColorStyle,
                    stage_button_animated: stage.stageButtonAnimated,
                    stage_button_elevated: stage.stageButtonElevated,
                    stage_button_sticky_footer: stage.stageButtonStickyFooter,
                    blocks: stage.blocks.map((block) => {
                        if (isOptionsComponentType(block.type)) {
                            normalizeOptionsBlock(block);
                        } else if (block.type === 'testimonials') {
                            normalizeTestimonialsBlock(block);
                        } else if (block.type === 'faq') {
                            normalizeFaqBlock(block);
                        } else if (block.type === 'carousel') {
                            normalizeCarouselBlock(block);
                        } else if (block.type === 'metrics') {
                            normalizeMetricsBlock(block);
                        }

                        return {
                            id: block.id,
                            type: normalizeLegacyBlockType(block.type),
                            label: safeTrim(block.label) || null,
                            placeholder:
                                block.type === 'image'
                                    ? sanitizeStoredAssetUrl(block.placeholder)
                                    : safeTrim(block.placeholder) || null,
                            variable_name:
                                safeTrim(block.variable_name) || null,
                            required: block.required,
                            options:
                                block.options
                                    ?.map((option) => safeTrim(option))
                                    .filter((option) => option.length > 0) ??
                                undefined,
                            option_items:
                                isOptionsComponentType(block.type) ||
                                block.type === 'testimonials' ||
                                block.type === 'faq' ||
                                block.type === 'carousel' ||
                                block.type === 'metrics'
                                    ? (block.option_items ?? []).map(
                                          (item) => ({
                                              id: item.id,
                                              label: safeTrim(item.label),
                                              points: Number(item.points ?? 0),
                                              value:
                                                  block.type === 'carousel'
                                                      ? (sanitizeStoredAssetUrl(
                                                            safeTrim(
                                                                item.value,
                                                            ) ||
                                                                safeTrim(
                                                                    item.image_url,
                                                                ),
                                                        ) ??
                                                        (safeTrim(item.value) ||
                                                            safeTrim(
                                                                item.image_url,
                                                            )))
                                                      : safeTrim(item.value),
                                              destination: safeTrim(
                                                  item.destination,
                                              ),
                                              image_url:
                                                  block.type === 'carousel'
                                                      ? (sanitizeStoredAssetUrl(
                                                            safeTrim(
                                                                item.image_url,
                                                            ) ||
                                                                safeTrim(
                                                                    item.value,
                                                                ),
                                                        ) ??
                                                        (safeTrim(
                                                            item.image_url,
                                                        ) ||
                                                            safeTrim(
                                                                item.value,
                                                            )))
                                                      : sanitizeStoredAssetUrl(
                                                            item.image_url,
                                                        ),
                                              subtitle:
                                                  safeTrim(item.subtitle) ||
                                                  null,
                                              description:
                                                  safeTrim(item.description) ||
                                                  null,
                                              rating: (() => {
                                                  const r = Number(
                                                      item.rating ??
                                                          item.points ??
                                                          5,
                                                  );
                                                  return Math.max(
                                                      1,
                                                      Math.min(
                                                          5,
                                                          Math.round(
                                                              Number.isFinite(r)
                                                                  ? r
                                                                  : 5,
                                                          ),
                                                      ),
                                                  );
                                              })(),
                                          }),
                                      )
                                    : undefined,
                            options_intro_type: isOptionsComponentType(
                                block.type,
                            )
                                ? (block.options_intro_type ?? 'text')
                                : undefined,
                            options_intro_title: isOptionsComponentType(
                                block.type,
                            )
                                ? block.options_intro_title?.trim() || null
                                : undefined,
                            options_intro_description: isOptionsComponentType(
                                block.type,
                            )
                                ? block.options_intro_description?.trim() ||
                                  null
                                : undefined,
                            options_required_selection: isOptionsComponentType(
                                block.type,
                            )
                                ? (block.options_required_selection ?? true)
                                : undefined,
                            options_allow_multiple: isOptionsComponentType(
                                block.type,
                            )
                                ? (block.options_allow_multiple ??
                                  defaultOptionsAllowMultiple(block.type))
                                : undefined,
                            options_disable_auto_follow: isOptionsComponentType(
                                block.type,
                            )
                                ? (block.options_disable_auto_follow ?? false)
                                : undefined,
                            options_style: isOptionsComponentType(block.type)
                                ? (block.options_style ?? 'simple')
                                : undefined,
                            options_transparent_image: isOptionsComponentType(
                                block.type,
                            )
                                ? (block.options_transparent_image ?? true)
                                : undefined,
                            options_layout: isOptionsComponentType(block.type)
                                ? (block.options_layout ?? 'grid_2')
                                : undefined,
                            options_orientation: isOptionsComponentType(
                                block.type,
                            )
                                ? (block.options_orientation ?? 'vertical')
                                : undefined,
                            options_image_ratio: isOptionsComponentType(
                                block.type,
                            )
                                ? (block.options_image_ratio ?? '1:1')
                                : undefined,
                            options_disposition: isOptionsComponentType(
                                block.type,
                            )
                                ? (block.options_disposition ?? 'image_text')
                                : undefined,
                            options_detail: isOptionsComponentType(block.type)
                                ? normalizeDetailValue(block.options_detail)
                                : undefined,
                            options_detail_position: isOptionsComponentType(
                                block.type,
                            )
                                ? (block.options_detail_position ?? 'start')
                                : undefined,
                            options_border_size:
                                isOptionsComponentType(block.type) ||
                                block.type === 'testimonials'
                                    ? (block.options_border_size ?? 'small')
                                    : undefined,
                            options_shadow:
                                isOptionsComponentType(block.type) ||
                                block.type === 'testimonials'
                                    ? (block.options_shadow ?? 'none')
                                    : undefined,
                            options_spacing:
                                isOptionsComponentType(block.type) ||
                                block.type === 'testimonials'
                                    ? (block.options_spacing ?? 'simple')
                                    : undefined,
                            testimonials_layout:
                                block.type === 'testimonials'
                                    ? (block.testimonials_layout ?? 'list')
                                    : undefined,
                            faq_first_active:
                                block.type === 'faq'
                                    ? (block.faq_first_active ?? true)
                                    : undefined,
                            faq_detail:
                                block.type === 'faq'
                                    ? (block.faq_detail ?? 'arrow')
                                    : undefined,
                            price_title:
                                block.type === 'price'
                                    ? block.price_title?.trim() || null
                                    : undefined,
                            price_prefix:
                                block.type === 'price'
                                    ? block.price_prefix?.trim() || null
                                    : undefined,
                            price_value:
                                block.type === 'price'
                                    ? block.price_value?.trim() || null
                                    : undefined,
                            price_suffix:
                                block.type === 'price'
                                    ? block.price_suffix?.trim() || null
                                    : undefined,
                            price_badge_text:
                                block.type === 'price'
                                    ? block.price_badge_text?.trim() || ''
                                    : undefined,
                            price_mode:
                                block.type === 'price'
                                    ? (block.price_mode ?? 'illustrative')
                                    : undefined,
                            price_layout:
                                block.type === 'price'
                                    ? (block.price_layout ?? 'horizontal')
                                    : undefined,
                            price_style:
                                block.type === 'price'
                                    ? (block.price_style ?? 'theme')
                                    : undefined,
                            price_link:
                                block.type === 'price'
                                    ? block.price_link?.trim() || ''
                                    : undefined,
                            carousel_layout:
                                block.type === 'carousel'
                                    ? (block.carousel_layout ?? 'image_text')
                                    : undefined,
                            carousel_pagination:
                                block.type === 'carousel'
                                    ? (block.carousel_pagination ?? true)
                                    : undefined,
                            carousel_autoplay:
                                block.type === 'carousel'
                                    ? (block.carousel_autoplay ?? false)
                                    : undefined,
                            carousel_autoplay_seconds:
                                block.type === 'carousel'
                                    ? Math.max(
                                          1,
                                          Math.min(
                                              60,
                                              Number(
                                                  block.carousel_autoplay_seconds ??
                                                      3,
                                              ),
                                          ),
                                      )
                                    : undefined,
                            carousel_border_type:
                                block.type === 'carousel'
                                    ? (block.carousel_border_type ?? 'none')
                                    : undefined,
                            image_ratio:
                                block.type === 'image'
                                    ? (block.image_ratio ?? 'auto')
                                    : undefined,
                            image_fit:
                                block.type === 'image'
                                    ? (block.image_fit ?? 'cover')
                                    : undefined,
                            image_radius:
                                block.type === 'image'
                                    ? (block.image_radius ?? 'medium')
                                    : undefined,
                            image_frame:
                                block.type === 'image'
                                    ? (block.image_frame ?? 'subtle')
                                    : undefined,
                            video_ratio:
                                block.type === 'video'
                                    ? (block.video_ratio ?? '16:9')
                                    : undefined,
                            audio_sender:
                                block.type === 'audio'
                                    ? block.audio_sender?.trim() || null
                                    : undefined,
                            audio_src:
                                block.type === 'audio'
                                    ? sanitizeStoredAssetUrl(block.audio_src)
                                    : undefined,
                            audio_avatar_url:
                                block.type === 'audio'
                                    ? sanitizeStoredAssetUrl(
                                          block.audio_avatar_url,
                                      )
                                    : undefined,
                            audio_model:
                                block.type === 'audio'
                                    ? (block.audio_model ?? 'whatsapp')
                                    : undefined,
                            audio_theme:
                                block.type === 'audio'
                                    ? (block.audio_theme ?? 'light')
                                    : undefined,
                            attention_style:
                                block.type === 'attention'
                                    ? (block.attention_style ?? 'red')
                                    : undefined,
                            attention_emphasis:
                                block.type === 'attention'
                                    ? (block.attention_emphasis ?? false)
                                    : undefined,
                            attention_padding:
                                block.type === 'attention'
                                    ? (block.attention_padding ?? 'default')
                                    : undefined,
                            notification_title:
                                block.type === 'notification'
                                    ? block.notification_title?.trim() || null
                                    : undefined,
                            notification_description:
                                block.type === 'notification'
                                    ? block.notification_description?.trim() ||
                                      null
                                    : undefined,
                            notification_avatar_url:
                                block.type === 'notification'
                                    ? sanitizeStoredAssetUrl(
                                          block.notification_avatar_url,
                                      )
                                    : undefined,
                            notification_position:
                                block.type === 'notification'
                                    ? (block.notification_position ?? 'default')
                                    : undefined,
                            notification_duration_seconds:
                                block.type === 'notification'
                                    ? Math.max(
                                          1,
                                          Number(
                                              block.notification_duration_seconds ??
                                                  5,
                                          ),
                                      )
                                    : undefined,
                            notification_interval_seconds:
                                block.type === 'notification'
                                    ? Math.max(
                                          1,
                                          Number(
                                              block.notification_interval_seconds ??
                                                  2,
                                          ),
                                      )
                                    : undefined,
                            notification_style:
                                block.type === 'notification'
                                    ? (block.notification_style ?? 'white')
                                    : undefined,
                            notification_size:
                                block.type === 'notification'
                                    ? (block.notification_size ?? 'default')
                                    : undefined,
                            notification_variant:
                                block.type === 'notification'
                                    ? (block.notification_variant ?? 'social')
                                    : undefined,
                            notification_variations:
                                block.type === 'notification'
                                    ? (block.notification_variations ?? []).map(
                                          (variation) => ({
                                              id: variation.id,
                                              value1:
                                                  variation.value1?.trim() ||
                                                  '',
                                              value2:
                                                  variation.value2?.trim() ||
                                                  '',
                                              value3:
                                                  variation.value3?.trim() ||
                                                  '',
                                              value4:
                                                  variation.value4?.trim() ||
                                                  '',
                                          }),
                                      )
                                    : undefined,
                            timer_seconds:
                                block.type === 'timer'
                                    ? Math.max(
                                          1,
                                          Number(
                                              block.timer_seconds ??
                                                  parseLegacyTimerSeconds(
                                                      block.placeholder,
                                                  ),
                                          ),
                                      )
                                    : undefined,
                            timer_text:
                                block.type === 'timer'
                                    ? (
                                          block.timer_text ??
                                          block.placeholder ??
                                          ''
                                      ).trim() || null
                                    : undefined,
                            timer_style:
                                block.type === 'timer'
                                    ? (block.timer_style ?? 'red')
                                    : undefined,
                            loading_start_seconds:
                                block.type === 'loading'
                                    ? Math.max(
                                          0,
                                          Number(
                                              block.loading_start_seconds ?? 0,
                                          ),
                                      )
                                    : undefined,
                            loading_duration_seconds:
                                block.type === 'loading'
                                    ? Math.max(
                                          1,
                                          Number(
                                              block.loading_duration_seconds ??
                                                  5,
                                          ),
                                      )
                                    : undefined,
                            loading_navigation_action:
                                block.type === 'loading'
                                    ? (block.loading_navigation_action ??
                                      'none')
                                    : undefined,
                            loading_target_stage_order:
                                block.type === 'loading'
                                    ? (block.loading_target_stage_order ??
                                      'next')
                                    : undefined,
                            loading_link:
                                block.type === 'loading'
                                    ? block.loading_link?.trim() || ''
                                    : undefined,
                            loading_show_title:
                                block.type === 'loading'
                                    ? (block.loading_show_title ?? true)
                                    : undefined,
                            loading_show_progress:
                                block.type === 'loading'
                                    ? (block.loading_show_progress ?? true)
                                    : undefined,
                            level_title:
                                block.type === 'level'
                                    ? block.level_title?.trim() || null
                                    : undefined,
                            level_subtitle:
                                block.type === 'level'
                                    ? block.level_subtitle?.trim() || null
                                    : undefined,
                            level_percentage:
                                block.type === 'level'
                                    ? Math.max(
                                          0,
                                          Math.min(
                                              100,
                                              Number(
                                                  block.level_percentage ?? 0,
                                              ),
                                          ),
                                      )
                                    : undefined,
                            level_indicator_text:
                                block.type === 'level'
                                    ? block.level_indicator_text?.trim() || ''
                                    : undefined,
                            level_legends:
                                block.type === 'level'
                                    ? block.level_legends?.trim() || ''
                                    : undefined,
                            level_show_meter:
                                block.type === 'level'
                                    ? (block.level_show_meter ?? true)
                                    : undefined,
                            level_show_progress:
                                block.type === 'level'
                                    ? (block.level_show_progress ?? true)
                                    : undefined,
                            level_type:
                                block.type === 'level'
                                    ? (block.level_type ?? 'line')
                                    : undefined,
                            level_color:
                                block.type === 'level'
                                    ? (block.level_color ?? 'theme')
                                    : undefined,
                            phone_mask:
                                block.type === 'phone'
                                    ? (block.phone_mask ?? 'br')
                                    : undefined,
                            number_mask:
                                block.type === 'number'
                                    ? (block.number_mask ?? 'decimal')
                                    : undefined,
                            height_mode:
                                block.type === 'height'
                                    ? (block.height_mode ?? 'ruler')
                                    : undefined,
                            weight_mode:
                                block.type === 'weight'
                                    ? (block.weight_mode ?? 'ruler')
                                    : undefined,
                            button_action: block.button_action ?? 'next_stage',
                            button_target_stage_order:
                                block.button_target_stage_order ?? 'next',
                            button_link: block.button_link?.trim() || null,
                            button_open_new_tab:
                                block.button_open_new_tab ?? true,
                            button_color_style:
                                block.button_color_style ?? 'theme',
                            button_animated: block.button_animated ?? false,
                            button_elevated: block.button_elevated ?? false,
                            button_sticky_footer:
                                block.button_sticky_footer ?? false,
                            label_style: block.label_style ?? 'default',
                            text_align: block.text_align ?? 'text-left',
                            width_percent: Number(block.width_percent ?? 100),
                            align_horizontal: block.align_horizontal ?? 'start',
                            align_vertical: block.align_vertical ?? 'start',
                            show_after_seconds:
                                block.show_after_seconds ?? null,
                            display_rule_mode: block.display_rule_mode ?? 'all',
                            display_rules: (block.display_rules ?? [])
                                .map((rule) => ({
                                    id: safeTrim(rule.id) || createClientId(),
                                    source_block_id: safeTrim(
                                        rule.source_block_id,
                                    ),
                                    operator: rule.operator,
                                    value: safeTrim(rule.value),
                                }))
                                .filter(
                                    (rule) =>
                                        rule.source_block_id !== '' &&
                                        (!displayRuleOperatorNeedsValue(
                                            rule.operator,
                                        ) ||
                                            rule.value !== ''),
                                ),
                            display_rule_groups: (
                                block.display_rule_groups ?? []
                            )
                                .map((group) => ({
                                    id: safeTrim(group.id) || createClientId(),
                                    mode: group.mode === 'any' ? 'any' : 'all',
                                    rules: (group.rules ?? [])
                                        .map((rule) => ({
                                            id:
                                                safeTrim(rule.id) ||
                                                createClientId(),
                                            source_block_id: safeTrim(
                                                rule.source_block_id,
                                            ),
                                            operator: rule.operator,
                                            value: safeTrim(rule.value),
                                        }))
                                        .filter(
                                            (rule) =>
                                                rule.source_block_id !== '' &&
                                                (!displayRuleOperatorNeedsValue(
                                                    rule.operator,
                                                ) ||
                                                    rule.value !== ''),
                                        ),
                                }))
                                .filter((group) => group.rules.length > 0),
                        };
                    }),
                },
            },
        };

        if (stage.id !== null) {
            return {
                ...basePayload,
                id: stage.id,
            };
        }

        return basePayload;
    });
}

function createSnapshotFromPayload(
    name: string,
    isActive: boolean,
    stages: ReturnType<typeof buildStagesPayload>,
): string {
    return JSON.stringify({
        name,
        is_active: isActive,
        stages,
    });
}

function createPersistedSnapshot(): string {
    return createSnapshotFromPayload(
        funnelNameDraft.value.trim(),
        saveForm.is_active,
        buildStagesPayload(),
    );
}

const persistedSnapshot = computed(() => createPersistedSnapshot());
const lastSavedSnapshot = ref(persistedSnapshot.value);
const hasUnsavedChanges = computed(
    () =>
        props.permissions.canEdit &&
        persistedSnapshot.value !== lastSavedSnapshot.value,
);

function openSaveTemplateModal(): void {
    if (!props.permissions.canEdit || hasUnsavedChanges.value) {
        return;
    }

    templateForm.reset();
    templateForm.name = funnelNameDraft.value.trim();
    templateForm.description = props.funnel.description ?? '';
    templateForm.category = '';
    templateForm.thumbnail_path = '';
    templateForm.is_active = true;
    templateForm.is_premium = false;
    templateForm.clearErrors();
    isSaveTemplateModalOpen.value = true;
}

function closeSaveTemplateModal(): void {
    if (templateForm.processing) {
        return;
    }

    isSaveTemplateModalOpen.value = false;
}

function submitTemplate(): void {
    templateForm.name = templateForm.name.trim();
    templateForm.description = templateForm.description.trim();
    templateForm.category = templateForm.category.trim();

    templateForm.submit(FunnelController.storeTemplate(props.funnel.id), {
        preserveScroll: true,
        onSuccess: () => {
            isSaveTemplateModalOpen.value = false;
            showActionToast('Template salvo na sua biblioteca.', 'success');
        },
        onError: (errors) => {
            showActionToast(resolveFirstFormError(errors), 'error');
        },
    });
}

function clearAutosaveStatusLater(): void {
    if (autosaveStatusTimer) {
        clearTimeout(autosaveStatusTimer);
    }

    if (actionToastTimer) {
        clearTimeout(actionToastTimer);
    }

    autosaveStatusTimer = setTimeout(() => {
        autosaveStatus.value = 'idle';
    }, 1800);
}

function scheduleAutosave(): void {
    if (
        !props.permissions.canEdit ||
        saveForm.processing ||
        !hasUnsavedChanges.value
    ) {
        return;
    }

    if (autosaveTimer) {
        clearTimeout(autosaveTimer);
    }

    autosaveTimer = setTimeout(() => {
        saveBuilder(false, { auto: true });
    }, 2200);
}

function flushPendingSaveRequest(): void {
    const pendingRequest = pendingSaveRequest.value;

    if (!pendingRequest) {
        if (hasUnsavedChanges.value) {
            scheduleAutosave();
        }

        return;
    }

    pendingSaveRequest.value = null;

    window.setTimeout(() => {
        saveBuilder(pendingRequest.isPublishing, { auto: pendingRequest.auto });
    }, 80);
}

function saveBuilder(
    isPublishing = false,
    options: { auto?: boolean } = {},
): void {
    if (!props.permissions.canEdit) {
        return;
    }

    if (autosaveTimer) {
        clearTimeout(autosaveTimer);
        autosaveTimer = null;
    }

    if (saveForm.processing) {
        pendingSaveRequest.value = {
            isPublishing:
                isPublishing || pendingSaveRequest.value?.isPublishing === true,
            auto: options.auto ?? false,
        };

        if (!options.auto) {
            showActionToast(
                'Um salvamento ja esta em andamento. Aguarde um instante.',
            );
        }

        return;
    }

    flushActiveInlineEditors();

    const stagesPayload = buildStagesPayload();
    const nextActiveState = isPublishing ? true : saveForm.is_active;
    const requestSnapshot = createSnapshotFromPayload(
        funnelNameDraft.value.trim(),
        nextActiveState,
        stagesPayload,
    );

    saveForm.name = funnelNameDraft.value.trim();
    saveForm.is_active = nextActiveState;
    saveForm.stages = stagesPayload;

    if (options.auto) {
        autosaveStatus.value = 'saving';
    }

    saveForm.submit(FunnelController.update(props.funnel.id), {
        preserveState: true,
        preserveScroll: true,
        replace: options.auto,
        onSuccess: () => {
            lastSavedSnapshot.value = requestSnapshot;
            autosaveStatus.value = 'saved';
            clearAutosaveStatusLater();

            if (isPublishing) {
                saveForm.is_active = true;
            }

            if (!options.auto) {
                showActionToast(
                    isPublishing
                        ? 'Funil publicado com sucesso.'
                        : 'Alteracoes salvas com sucesso.',
                    'success',
                );
            }

            flushPendingSaveRequest();
        },
        onError: (errors) => {
            autosaveStatus.value = 'error';

            showActionToast(resolveFirstFormError(errors), 'error');
            flushPendingSaveRequest();
        },
    });
}

function shouldIgnoreGlobalShortcut(event: KeyboardEvent): boolean {
    const target = event.target as HTMLElement | null;

    if (!target) {
        return false;
    }

    const tag = target.tagName;

    return (
        tag === 'INPUT' ||
        tag === 'TEXTAREA' ||
        tag === 'SELECT' ||
        target.isContentEditable
    );
}

function onGlobalKeydown(event: KeyboardEvent): void {
    if (!props.permissions.canEdit) {
        return;
    }

    const isMod = event.ctrlKey || event.metaKey;

    if (!isMod) {
        return;
    }

    const key = event.key.toLowerCase();
    const insideInput = shouldIgnoreGlobalShortcut(event);

    if (key === 's') {
        event.preventDefault();
        saveBuilder(false);

        return;
    }

    if (!insideInput && key === 'z' && !event.shiftKey) {
        event.preventDefault();
        undoHistory();

        return;
    }

    if (!insideInput && ((key === 'z' && event.shiftKey) || key === 'y')) {
        event.preventDefault();
        redoHistory();

        return;
    }

    if (!insideInput && key === 'd' && selectedBlockId.value) {
        event.preventDefault();
        duplicateBlock(selectedBlockId.value);

        return;
    }

    if (
        !insideInput &&
        event.shiftKey &&
        selectedBlockId.value &&
        event.key === 'ArrowUp'
    ) {
        event.preventDefault();
        moveSelectedBlock('up', { showToast: true });

        return;
    }

    if (
        !insideInput &&
        event.shiftKey &&
        selectedBlockId.value &&
        event.key === 'ArrowDown'
    ) {
        event.preventDefault();
        moveSelectedBlock('down', { showToast: true });
    }
}

function onBeforeWindowUnload(event: BeforeUnloadEvent): void {
    if (!hasUnsavedChanges.value) {
        return;
    }

    event.preventDefault();
    event.returnValue = '';
}

watch(
    [localStages, funnelNameDraft, selectedStageKey],
    () => {
        if (!props.permissions.canEdit || isRestoringHistory.value) {
            return;
        }

        if (historyTimer) {
            clearTimeout(historyTimer);
        }

        historyTimer = setTimeout(() => {
            pushHistorySnapshot();
        }, 350);
    },
    { deep: true },
);

watch(selectedBlockId, (blockId) => {
    if (blockId) {
        componentPanelTab.value = 'component';
    }
});

watch(
    selectedStageKey,
    () => {
        if (typeof window === 'undefined') {
            return;
        }

        const persistedStageKey = stageStorageValue(currentStage.value);

        if (persistedStageKey) {
            window.sessionStorage.setItem(
                selectedStageStorageKey.value,
                persistedStageKey,
            );

            return;
        }

        window.sessionStorage.removeItem(selectedStageStorageKey.value);
    },
    { immediate: true },
);

watch(
    currentStage,
    (stage) => {
        if (!stage) {
            selectedBlockId.value = null;

            return;
        }

        const blockExists = stage.blocks.some(
            (block) => block.id === selectedBlockId.value,
        );
        selectedBlockId.value = blockExists ? selectedBlockId.value : null;
    },
    { immediate: true },
);

watch(
    selectedBlock,
    (block, previousBlock) => {
        if (previousBlock?.type === 'content_text') {
            syncContentTextMarkupForBlock(
                previousBlock,
                contentTextEditorElement.value,
            );
        }

        if (
            !block ||
            (!isOptionsComponentType(block.type) &&
                block.type !== 'content_text' &&
                block.type !== 'image' &&
                block.type !== 'audio')
        ) {
            expandedOptionItemId.value = null;
            introEditorTarget.value = null;
            introActiveElement.value = null;
            draggedOptionItemId.value = null;
            dragOverOptionItemId.value = null;
            contentTextEditorElement.value = null;

            return;
        }

        if (block.type === 'image') {
            imageComponentTab.value =
                block.placeholder && /^https?:\/\//i.test(block.placeholder)
                    ? 'url'
                    : 'image';
            expandedOptionItemId.value = null;
            introEditorTarget.value = null;
            introActiveElement.value = null;
            draggedOptionItemId.value = null;
            dragOverOptionItemId.value = null;
            contentTextEditorElement.value = null;
            return;
        }

        if (block.type === 'content_text') {
            ensureContentTextMarkup(block);
            if (contentTextEditorElement.value) {
                const markup = contentTextMarkup(block);

                if (contentTextEditorElement.value.innerHTML !== markup) {
                    contentTextEditorElement.value.innerHTML = markup;
                }
            }
            expandedOptionItemId.value = null;
            draggedOptionItemId.value = null;
            dragOverOptionItemId.value = null;
            return;
        }

        if (block.type === 'audio') {
            expandedOptionItemId.value = null;
            introEditorTarget.value = null;
            introActiveElement.value = null;
            draggedOptionItemId.value = null;
            dragOverOptionItemId.value = null;
            contentTextEditorElement.value = null;
            return;
        }

        normalizeOptionsBlock(block);

        const hasExpandedItem =
            block.option_items?.some(
                (item) => item.id === expandedOptionItemId.value,
            ) ?? false;
        if (!hasExpandedItem) {
            expandedOptionItemId.value = null;
        }

        const hasDraggedItem =
            block.option_items?.some(
                (item) => item.id === draggedOptionItemId.value,
            ) ?? false;
        if (!hasDraggedItem) {
            draggedOptionItemId.value = null;
            dragOverOptionItemId.value = null;
        }

        const hasReorderedItem =
            block.option_items?.some(
                (item) => item.id === reorderedOptionItemId.value,
            ) ?? false;
        if (!hasReorderedItem) {
            reorderedOptionItemId.value = null;
        }
    },
    { immediate: true },
);

watch(
    hasUnsavedChanges,
    (dirty) => {
        if (!dirty) {
            if (autosaveTimer) {
                clearTimeout(autosaveTimer);
            }

            return;
        }

        scheduleAutosave();
    },
    { immediate: true },
);

watch(persistedSnapshot, () => {
    if (!props.permissions.canEdit || !hasUnsavedChanges.value) {
        return;
    }

    scheduleAutosave();
});

watch(
    () => importMediaStatus.value?.status,
    (status, previousStatus) => {
        if (status === 'queued' || status === 'processing') {
            scheduleImportMediaStatusRefresh();

            return;
        }

        if (previousStatus !== 'queued' && previousStatus !== 'processing') {
            return;
        }

        if (status === 'completed') {
            showActionToast(
                `${importMediaStatus.value?.imported ?? 0} imagens copiadas para o InovaForm.`,
                'success',
            );
        } else if (status === 'partial') {
            showActionToast(
                'Algumas imagens não puderam ser copiadas; os links externos foram preservados.',
                'info',
            );
        } else if (status === 'failed') {
            showActionToast(
                'Não foi possível copiar as imagens agora. Os links externos foram preservados.',
                'error',
            );
        }
    },
);

onMounted(() => {
    if (typeof window !== 'undefined') {
        const storedStageKey = window.sessionStorage.getItem(
            selectedStageStorageKey.value,
        );

        const storedStage = findStageByStoredValue(storedStageKey);

        if (storedStage) {
            selectedStageKey.value = storedStage.clientId;
        }
    }

    notificationPreviewTimer = window.setInterval(() => {
        notificationPreviewTick.value += 1;
    }, 1000);

    pushHistorySnapshot(true);
    lastSavedSnapshot.value = persistedSnapshot.value;
    scheduleImportMediaStatusRefresh();
    window.addEventListener('keydown', onGlobalKeydown);
    window.addEventListener('beforeunload', onBeforeWindowUnload);
});

onBeforeUnmount(() => {
    if (historyTimer) {
        clearTimeout(historyTimer);
    }

    if (autosaveTimer) {
        clearTimeout(autosaveTimer);
    }

    if (autosaveStatusTimer) {
        clearTimeout(autosaveStatusTimer);
    }

    if (reorderHighlightTimer) {
        clearTimeout(reorderHighlightTimer);
    }

    if (notificationPreviewTimer) {
        clearInterval(notificationPreviewTimer);
    }

    if (importMediaStatusTimer) {
        clearTimeout(importMediaStatusTimer);
    }

    Object.values(audioElements.value).forEach((element) => {
        element?.pause();
    });

    window.removeEventListener('keydown', onGlobalKeydown);
    window.removeEventListener('beforeunload', onBeforeWindowUnload);
});
</script>

<template>
    <Head :title="`${props.funnel.name} - Builder`" />

    <div
        class="flex h-dvh flex-col overflow-hidden bg-[#050d22] text-[#d8e7ff]"
    >
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
                            {{ funnelNameDraft }}
                        </p>
                        <p
                            class="hidden truncate text-sm text-[#88a8df] sm:block"
                        >
                            ... / {{ funnelNameDraft.toLowerCase() }}
                        </p>
                    </div>
                    <div
                        v-if="!props.permissions.canEdit"
                        class="rounded-full border border-[#4e6eaa] bg-[#163463] px-2.5 py-1 text-xs text-[#d4e5ff]"
                    >
                        Somente leitura
                    </div>
                </div>

                <nav
                    class="order-3 flex w-full items-center gap-1.5 overflow-x-auto rounded-lg border border-[#253f70] bg-[#081a39] p-1.5 text-sm xl:order-none xl:w-auto xl:overflow-visible"
                >
                    <button
                        class="shrink-0 rounded-md bg-[#1e4e9e] px-3.5 py-1.5 font-medium text-white"
                    >
                        <span class="inline-flex items-center gap-1"
                            ><BookOpen class="size-4" /> Construtor</span
                        >
                    </button>
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

                <div
                    class="flex max-w-full shrink-0 items-center gap-1.5 overflow-x-auto xl:overflow-visible"
                >
                    <button
                        @click="undoHistory"
                        :disabled="!props.permissions.canEdit || !canUndo"
                        class="rounded-md border border-[#2a487c] bg-[#081b39] p-1.5 text-[#b6cdff] disabled:opacity-40"
                    >
                        <Undo2 class="size-4" />
                    </button>
                    <button
                        @click="redoHistory"
                        :disabled="!props.permissions.canEdit || !canRedo"
                        class="rounded-md border border-[#2a487c] bg-[#081b39] p-1.5 text-[#b6cdff] disabled:opacity-40"
                    >
                        <Redo2 class="size-4" />
                    </button>
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
                        @click="copyBuilderLink"
                        class="rounded-md border border-[#2a487c] bg-[#081b39] p-1.5 text-[#b6cdff]"
                    >
                        <Share2 v-if="!copiedLink" class="size-4" />
                        <Check v-else class="size-4" />
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
                        data-testid="builder-save-template-button"
                        :disabled="
                            !props.permissions.canEdit ||
                            hasUnsavedChanges ||
                            saveForm.processing
                        "
                        :title="
                            hasUnsavedChanges
                                ? 'Salve as alterações antes de criar um template'
                                : 'Salvar este funil como template'
                        "
                        class="hidden shrink-0 rounded-md border border-[#3860a7] bg-[#0a2146] px-3 py-1.5 text-sm font-medium text-[#cfe0ff] transition hover:bg-[#10336d] disabled:cursor-not-allowed disabled:opacity-40 sm:block"
                        @click="openSaveTemplateModal"
                    >
                        <span class="inline-flex items-center gap-1">
                            <Sparkles class="size-4" /> Template
                        </span>
                    </button>
                    <button
                        @click="saveBuilder(false)"
                        :disabled="
                            !props.permissions.canEdit || saveForm.processing
                        "
                        data-testid="builder-save-button"
                        class="shrink-0 rounded-md border border-[#3860a7] bg-[#0a2c61] px-3 py-1.5 text-sm font-medium text-white disabled:opacity-50 sm:px-4"
                    >
                        <span class="inline-flex items-center gap-1"
                            ><Save class="size-4" /> Salvar</span
                        >
                    </button>
                    <button
                        @click="saveBuilder(true)"
                        :disabled="
                            !props.permissions.canEdit || saveForm.processing
                        "
                        data-testid="builder-publish-button"
                        class="shrink-0 rounded-md bg-gradient-to-r from-[#1d5fd2] to-[#3f8dff] px-3 py-1.5 text-sm font-semibold text-white disabled:opacity-50 sm:px-4"
                    >
                        Publicar
                    </button>
                    <span
                        v-if="props.permissions.canEdit && hasUnsavedChanges"
                        data-testid="builder-unsaved-status"
                        class="rounded-md border border-amber-300/40 bg-amber-500/10 px-2 py-1 text-[11px] text-amber-200"
                    >
                        Alteracoes pendentes
                    </span>
                    <span
                        v-else-if="autosaveStatus === 'saving'"
                        data-testid="builder-autosave-status"
                        class="text-[11px] text-[#9bb9ef]"
                        >Salvando automaticamente...</span
                    >
                    <span
                        v-else-if="autosaveStatus === 'saved'"
                        data-testid="builder-autosave-status"
                        class="text-[11px] text-emerald-300"
                        >Salvo</span
                    >
                    <span
                        v-else-if="autosaveStatus === 'error'"
                        data-testid="builder-autosave-status"
                        class="text-[11px] text-rose-300"
                        >Erro ao salvar</span
                    >
                </div>
            </div>
        </header>

        <div
            v-if="isImportMediaActive"
            data-testid="builder-import-media-status"
            role="status"
            class="flex shrink-0 items-center gap-2 border-b border-[#2e588f] bg-[#0a2146] px-3 py-2 text-xs text-[#cde0ff] sm:px-4"
        >
            <LoaderCircle class="size-4 shrink-0 animate-spin text-[#67a2ff]" />
            <span>{{ importMediaNotice }}</span>
        </div>

        <div
            v-if="actionToast.visible"
            class="fixed top-20 right-5 z-50 rounded-lg border px-3 py-2 text-xs shadow-[0_14px_30px_rgba(0,0,0,0.35)]"
            :class="
                actionToast.tone === 'success'
                    ? 'border-emerald-300/40 bg-emerald-500/15 text-emerald-100'
                    : actionToast.tone === 'error'
                      ? 'border-rose-300/40 bg-rose-500/15 text-rose-100'
                      : 'border-[#4f8fff]/40 bg-[#10366f] text-[#d8e7ff]'
            "
        >
            {{ actionToast.message }}
        </div>

        <div
            data-testid="builder-mobile-panel-nav"
            class="grid shrink-0 grid-cols-4 border-b border-[#1e3157] bg-[#071430] p-1 xl:hidden"
        >
            <button
                v-for="panel in mobileBuilderPanels"
                :key="panel.id"
                type="button"
                class="rounded-md px-2 py-2 text-xs font-medium transition"
                :class="
                    mobileBuilderPanel === panel.id
                        ? 'bg-[#1e4e9e] text-white'
                        : 'text-[#91afe3]'
                "
                :data-testid="`builder-mobile-panel-${panel.id}`"
                @click="mobileBuilderPanel = panel.id"
            >
                {{ panel.label }}
            </button>
        </div>

        <main
            class="grid min-h-0 flex-1 grid-cols-1 overflow-hidden xl:grid-cols-[230px_210px_1fr_430px]"
            @click="clearSelectedBlock"
        >
            <aside
                class="h-full overflow-y-auto border-r border-[#1c3158] bg-[#07142e] xl:block"
                :class="mobileBuilderPanel === 'stages' ? 'block' : 'hidden'"
                @click.stop
            >
                <div
                    v-for="(stage, index) in localStages"
                    :key="stage.clientId"
                    :data-testid="`builder-stage-item-${index + 1}`"
                    class="relative flex items-center justify-between border-b border-[#1a2b4d] px-4 py-3 transition"
                    :class="[
                        stage.clientId === selectedStageKey
                            ? 'bg-[#0f2c61]'
                            : 'hover:bg-[#0b2148]',
                        dragOverStageKey === stage.clientId
                            ? 'ring-1 ring-[#4f8fff] ring-inset'
                            : '',
                        props.permissions.canEdit
                            ? 'cursor-move'
                            : 'cursor-pointer',
                    ]"
                    :draggable="props.permissions.canEdit"
                    @click="selectedStageKey = stage.clientId"
                    @dragstart="onStageDragStart(stage.clientId)"
                    @dragover.prevent="onStageDragOver(stage.clientId)"
                    @drop.prevent="onStageDrop(stage.clientId)"
                    @dragend="onStageDragEnd"
                >
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-[#7ea4e8]">{{
                            index + 1
                        }}</span>
                        <p class="text-lg leading-none text-[#e8f1ff]">
                            {{ stage.name }}
                        </p>
                    </div>
                    <div class="relative">
                        <button
                            type="button"
                            :data-testid="`stage-menu-trigger-${stage.clientId}`"
                            :disabled="!props.permissions.canEdit"
                            class="rounded p-1 text-[#8fb2ee] transition hover:bg-[#17376c] disabled:opacity-40"
                            @click.stop="toggleStageMenu(stage.clientId)"
                        >
                            <MoreVertical class="size-4" />
                        </button>
                        <div
                            v-if="openStageMenuKey === stage.clientId"
                            class="absolute top-8 right-0 z-20 min-w-36 rounded-lg border border-[#27477f] bg-[#081a3c] p-1 shadow-[0_12px_28px_rgba(0,0,0,0.35)]"
                        >
                            <button
                                type="button"
                                :data-testid="`stage-menu-duplicate-${stage.clientId}`"
                                class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-[#d8e7ff] transition hover:bg-[#12315f]"
                                @click.stop="
                                    duplicateStageByClientId(stage.clientId)
                                "
                            >
                                <Blend class="size-3.5" /> Duplicar etapa
                            </button>
                            <button
                                type="button"
                                :data-testid="`stage-menu-delete-${stage.clientId}`"
                                :disabled="localStages.length <= 2"
                                class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-rose-200 transition hover:bg-[#3a1630] disabled:cursor-not-allowed disabled:opacity-40"
                                @click.stop="
                                    removeStageByClientId(stage.clientId)
                                "
                            >
                                <Trash2 class="size-3.5" /> Excluir etapa
                            </button>
                        </div>
                    </div>
                </div>
                <div
                    class="flex items-center gap-4 px-4 py-3 text-sm text-[#8fb2ee]"
                >
                    <button
                        @click="addStage"
                        :disabled="!props.permissions.canEdit"
                        class="inline-flex items-center gap-1 disabled:opacity-50"
                    >
                        <Plus class="size-3.5" /> Etapa
                    </button>
                    <Link :href="FunnelController.index().url">Modelos</Link>
                </div>
            </aside>

            <aside
                class="min-h-0 flex-col border-r border-[#1c3158] bg-[#071a37] p-2.5 xl:flex"
                :class="mobileBuilderPanel === 'library' ? 'flex' : 'hidden'"
                @click.stop
            >
                <h2 class="mb-3 text-lg font-medium text-[#e4efff]">
                    Formulario
                </h2>
                <div
                    class="form-scroll-area flex-1 space-y-2 overflow-y-auto pr-1"
                >
                    <button
                        v-for="block in formBlocks"
                        :key="block.type"
                        :data-testid="`palette-block-${block.type}`"
                        :disabled="!props.permissions.canEdit"
                        class="flex w-full items-center gap-2.5 rounded-lg border border-[#27477f] bg-[#0a244d] px-2.5 py-2 text-left text-sm text-[#d4e5ff] disabled:cursor-not-allowed disabled:opacity-60"
                        :draggable="props.permissions.canEdit"
                        @dragstart="onPaletteDragStart(block.type)"
                        @dragend="onPaletteDragEnd"
                        @click="addBlock(block.type)"
                    >
                        <FormInput class="size-3.5 text-[#8fb5ff]" />
                        <span>{{ block.label }}</span>
                    </button>

                    <p class="mt-4 mb-2 text-sm text-[#88a8df]">Quiz</p>
                    <button
                        v-for="block in quizBlocks"
                        :key="block.type"
                        :data-testid="`palette-block-${block.type}`"
                        :disabled="!props.permissions.canEdit"
                        class="flex w-full items-center gap-2.5 rounded-lg border border-[#27477f] bg-[#0a244d] px-2.5 py-2 text-left text-sm text-[#d4e5ff] disabled:cursor-not-allowed disabled:opacity-60"
                        :draggable="props.permissions.canEdit"
                        @dragstart="onPaletteDragStart(block.type)"
                        @dragend="onPaletteDragEnd"
                        @click="addBlock(block.type)"
                    >
                        <Sparkles class="size-3.5 text-[#72d7ff]" />
                        <span>{{ block.label }}</span>
                    </button>

                    <p class="mt-4 mb-2 text-sm text-[#88a8df]">
                        Midia e conteudo
                    </p>
                    <button
                        v-for="block in mediaBlocks"
                        :key="block.type"
                        :data-testid="`palette-block-${block.type}`"
                        :disabled="!props.permissions.canEdit"
                        class="flex w-full items-center gap-2.5 rounded-lg border border-[#27477f] bg-[#0a244d] px-2.5 py-2 text-left text-sm text-[#d4e5ff] disabled:cursor-not-allowed disabled:opacity-60"
                        :draggable="props.permissions.canEdit"
                        @dragstart="onPaletteDragStart(block.type)"
                        @dragend="onPaletteDragEnd"
                        @click="addBlock(block.type)"
                    >
                        <Sparkles class="size-3.5 text-[#72d7ff]" />
                        <span>{{ block.label }}</span>
                    </button>

                    <p class="mt-4 mb-2 text-sm text-[#88a8df]">Argumentacao</p>
                    <button
                        v-for="block in argumentBlocks"
                        :key="block.type"
                        :data-testid="`palette-block-${block.type}`"
                        :disabled="!props.permissions.canEdit"
                        class="flex w-full items-center gap-2.5 rounded-lg border border-[#27477f] bg-[#0a244d] px-2.5 py-2 text-left text-sm text-[#d4e5ff] disabled:cursor-not-allowed disabled:opacity-60"
                        :draggable="props.permissions.canEdit"
                        @dragstart="onPaletteDragStart(block.type)"
                        @dragend="onPaletteDragEnd"
                        @click="addBlock(block.type)"
                    >
                        <Sparkles class="size-3.5 text-[#72d7ff]" />
                        <span>{{ block.label }}</span>
                    </button>

                    <p class="mt-4 mb-2 text-sm text-[#88a8df]">
                        Personalizacao
                    </p>
                    <button
                        v-for="block in personalizationBlocks"
                        :key="block.type"
                        :data-testid="`palette-block-${block.type}`"
                        :disabled="!props.permissions.canEdit"
                        class="flex w-full items-center gap-2.5 rounded-lg border border-[#27477f] bg-[#0a244d] px-2.5 py-2 text-left text-sm text-[#d4e5ff] disabled:cursor-not-allowed disabled:opacity-60"
                        :draggable="props.permissions.canEdit"
                        @dragstart="onPaletteDragStart(block.type)"
                        @dragend="onPaletteDragEnd"
                        @click="addBlock(block.type)"
                    >
                        <Sparkles class="size-3.5 text-[#72d7ff]" />
                        <span>{{ block.label }}</span>
                    </button>
                </div>
            </aside>

            <section
                class="min-h-0 overflow-y-auto border-r border-[#1c3158] bg-[#061227] p-3 sm:p-4 xl:block"
                :class="mobileBuilderPanel === 'preview' ? 'block' : 'hidden'"
            >
                <div
                    data-testid="builder-preview-card"
                    class="mx-auto w-full max-w-[500px] rounded-[2rem] border border-[#2a4f89] bg-[#0b1f43] p-5 shadow-[0_24px_60px_rgba(0,0,0,0.48)]"
                >
                    <div class="mb-5 flex items-center gap-3 text-[#9fc0f8]">
                        <ArrowLeft
                            v-if="currentStage?.header.allow_back"
                            class="size-4"
                        />
                        <div class="h-2.5 w-full rounded-full bg-[#1a3564]">
                            <div
                                v-if="currentStage?.header.show_progress"
                                class="h-2.5 w-2/3 rounded-full bg-gradient-to-r from-[#4aa0ff] to-[#5b79ff]"
                            />
                        </div>
                    </div>

                    <div
                        v-if="currentStage"
                        data-testid="builder-preview-canvas"
                        class="mt-6 space-y-2.5 rounded-xl p-1 transition"
                        :class="
                            draggedPaletteType
                                ? 'ring-1 ring-[#4f8fff]/70 ring-inset'
                                : ''
                        "
                        @click="clearSelectedBlock"
                        @dragover.prevent
                        @drop.prevent="onBlocksPanelDrop"
                    >
                        <template v-if="visibleCurrentStageBlocks.length === 0">
                            <div
                                class="rounded-xl border border-dashed border-[#2a4e88] px-4 py-6 text-center text-sm text-[#8daee7]"
                            >
                                Arraste um componente da esquerda e solte aqui
                                para montar esta etapa.
                            </div>
                        </template>
                        <template v-else>
                            <div
                                v-for="(
                                    block, blockIndex
                                ) in visibleCurrentStageBlocks"
                                :key="block.id"
                                :data-testid="`preview-block-${block.id}`"
                                class="group relative w-full space-y-2 rounded-lg border border-transparent p-1 transition"
                                :class="[
                                    props.permissions.canEdit
                                        ? 'cursor-grab active:cursor-grabbing'
                                        : '',
                                    dragOverBlockId === block.id
                                        ? 'ring-2 ring-[#4f8fff] ring-inset'
                                        : '',
                                    selectedBlockId === block.id
                                        ? 'border-[#4f8fff] ring-1 ring-[#4f8fff] ring-inset'
                                        : '',
                                ]"
                                :style="blockWrapperStyle(block)"
                                :draggable="props.permissions.canEdit"
                                @mousedown.stop="selectBlock(block.id)"
                                @click.stop="selectBlock(block.id)"
                                @dragstart="onBlockDragStart(block.id)"
                                @dragover.prevent="onBlockDragOver(block.id)"
                                @drop.prevent="onBlockDrop(block.id)"
                                @dragend="onBlockDragEnd"
                            >
                                <div
                                    v-if="
                                        props.permissions.canEdit &&
                                        selectedBlockId === block.id
                                    "
                                    class="mb-2 inline-flex items-center gap-1 rounded-md bg-[#1c57be] p-1.5 text-white shadow-[0_8px_20px_rgba(8,20,44,0.35)]"
                                >
                                    <button
                                        type="button"
                                        class="rounded p-1 hover:bg-[#2e6fe0]"
                                        @click.stop="moveBlock(block.id, 'up')"
                                        :disabled="blockIndex === 0"
                                    >
                                        <ArrowUp class="size-3.5" />
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded p-1 hover:bg-[#2e6fe0]"
                                        @click.stop="duplicateBlock(block.id)"
                                    >
                                        <Blend class="size-3.5" />
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded p-1 hover:bg-[#2e6fe0]"
                                        @click.stop="removeBlock(block.id)"
                                    >
                                        <Trash2 class="size-3.5" />
                                    </button>
                                </div>

                                <label
                                    v-if="shouldShowLabel(block)"
                                    class="block text-sm"
                                    :class="labelClass(block)"
                                    >{{ block.label }}</label
                                >

                                <div
                                    v-if="
                                        block.type === 'height' &&
                                        (block.height_mode ?? 'ruler') ===
                                            'ruler'
                                    "
                                    class="w-full rounded-xl border border-[#2f538f] px-4 py-4 text-white"
                                    :style="{ backgroundColor: '#0b274f' }"
                                >
                                    <div
                                        class="mx-auto mb-4 flex w-fit rounded-full bg-[#153568] p-1 text-sm"
                                    >
                                        <span
                                            class="rounded-full bg-[#071733] px-4 py-1.5 font-semibold text-white"
                                            >cm</span
                                        >
                                        <span class="px-4 py-1.5 text-[#9dbbeb]"
                                            >pol</span
                                        >
                                    </div>

                                    <div
                                        class="text-center text-5xl font-semibold text-white"
                                    >
                                        {{ numericBlockPlaceholder(block, 175)
                                        }}<span class="text-3xl">cm</span>
                                    </div>
                                    <div class="mt-4 px-1">
                                        <input
                                            type="range"
                                            min="120"
                                            max="230"
                                            :value="
                                                numericBlockPlaceholder(
                                                    block,
                                                    175,
                                                )
                                            "
                                            class="w-full accent-[#4f8fff]"
                                            disabled
                                        />
                                        <div
                                            class="mt-2 flex justify-between text-xs text-[#9dbbeb]"
                                        >
                                            <span>120</span>
                                            <span>175</span>
                                            <span>230</span>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-else-if="
                                        block.type === 'weight' &&
                                        (block.weight_mode ?? 'ruler') ===
                                            'ruler'
                                    "
                                    class="w-full rounded-xl border border-[#2f538f] px-4 py-4 text-white"
                                    :style="{ backgroundColor: '#0b274f' }"
                                >
                                    <div
                                        class="mx-auto mb-4 flex w-fit rounded-full bg-[#153568] p-1 text-sm"
                                    >
                                        <span
                                            class="rounded-full bg-[#071733] px-4 py-1.5 font-semibold text-white"
                                            >kg</span
                                        >
                                        <span class="px-4 py-1.5 text-[#9dbbeb]"
                                            >lb</span
                                        >
                                    </div>

                                    <div
                                        class="text-center text-5xl font-semibold text-white"
                                    >
                                        {{ numericBlockPlaceholder(block, 80)
                                        }}<span class="text-3xl">kg</span>
                                    </div>
                                    <div class="mt-4 px-1">
                                        <input
                                            type="range"
                                            min="30"
                                            max="180"
                                            :value="
                                                numericBlockPlaceholder(
                                                    block,
                                                    80,
                                                )
                                            "
                                            class="w-full accent-[#4f8fff]"
                                            disabled
                                        />
                                        <div
                                            class="mt-2 flex justify-between text-xs text-[#9dbbeb]"
                                        >
                                            <span>30</span>
                                            <span>80</span>
                                            <span>180</span>
                                        </div>
                                    </div>
                                </div>

                                <input
                                    v-else-if="isInputBlock(block.type)"
                                    :type="fieldInputType(block.type)"
                                    :inputmode="fieldInputMode(block.type)"
                                    :placeholder="block.placeholder ?? ''"
                                    class="w-full rounded-xl border border-[#2a4e88] bg-[#0b274f] px-3.5 py-3 text-base text-white outline-none"
                                />

                                <textarea
                                    v-else-if="isTextAreaBlock(block.type)"
                                    :placeholder="block.placeholder ?? ''"
                                    class="w-full rounded-xl border border-[#2a4e88] bg-[#0b274f] px-3.5 py-3 text-base text-white outline-none"
                                    rows="3"
                                />

                                <div
                                    v-else-if="
                                        block.type === 'content_text' &&
                                        hasContentTextContent(block)
                                    "
                                    :data-testid="`content-text-preview-${block.id}`"
                                    class="px-1 py-1 text-[#dce8ff] [&_a]:text-[#9fc2ff] [&_a]:underline [&_h1]:text-3xl [&_h1]:leading-tight [&_h1]:font-bold [&_h2]:text-2xl [&_h2]:leading-tight [&_h2]:font-bold [&_h3]:text-xl [&_h3]:leading-tight [&_h3]:font-semibold [&_p]:mt-2 [&_p]:text-sm [&_p]:leading-relaxed [&_p]:text-[#9cc1ff] [&_ul]:mt-2 [&_ul]:list-disc [&_ul]:space-y-1 [&_ul]:pl-5"
                                    :class="contentTextAlignClass(block)"
                                    v-html="contentTextMarkup(block)"
                                ></div>

                                <template v-else-if="block.type === 'button'">
                                    <button
                                        class="w-full rounded-xl py-3 text-lg font-medium text-white transition"
                                        :class="[
                                            block.button_color_style === 'dark'
                                                ? 'bg-[#06183a]'
                                                : block.button_color_style ===
                                                    'light'
                                                  ? 'bg-[#d8e7ff] text-[#082252]'
                                                  : 'bg-gradient-to-r from-[#1f60d4] to-[#4d87ff]',
                                            block.button_elevated
                                                ? 'shadow-[0_10px_24px_rgba(20,86,193,0.45)]'
                                                : '',
                                            block.button_animated
                                                ? 'hover:translate-y-[-1px] hover:brightness-110'
                                                : '',
                                            block.button_sticky_footer
                                                ? 'sticky bottom-3 z-[1]'
                                                : '',
                                        ]"
                                    >
                                        {{ block.label }}
                                    </button>
                                    <p
                                        v-if="
                                            block.button_action ===
                                                'open_link' && block.button_link
                                        "
                                        class="text-xs text-[#8cb3f4]"
                                    >
                                        Link: {{ block.button_link }}
                                    </p>
                                </template>

                                <div
                                    v-else-if="isChoiceBlock(block.type)"
                                    class="space-y-2"
                                >
                                    <template
                                        v-if="
                                            isOptionsComponentType(block.type)
                                        "
                                    >
                                        <div :class="optionsListClass(block)">
                                            <div
                                                v-if="
                                                    hasOptionsIntroContent(
                                                        block,
                                                    )
                                                "
                                                :data-testid="`builder-options-intro-${block.id}`"
                                                class="sm:col-span-2"
                                                :class="[
                                                    optionsCardRadiusClass(
                                                        block,
                                                    ),
                                                    optionsCardSpacingClass(
                                                        block,
                                                    ),
                                                    optionsCardToneClass(block),
                                                    optionsCardShadowClass(
                                                        block,
                                                    ),
                                                    'border text-center',
                                                ]"
                                            >
                                                <h4
                                                    v-if="
                                                        (
                                                            block.options_intro_title ??
                                                            ''
                                                        ).trim().length
                                                    "
                                                    class="text-xl leading-tight font-semibold text-white"
                                                >
                                                    {{
                                                        block.options_intro_title
                                                    }}
                                                </h4>
                                                <p
                                                    v-if="
                                                        (
                                                            block.options_intro_description ??
                                                            ''
                                                        ).trim().length
                                                    "
                                                    class="text-sm text-[#9cc1ff]"
                                                    :class="
                                                        (
                                                            block.options_intro_title ??
                                                            ''
                                                        ).trim().length
                                                            ? 'mt-2'
                                                            : ''
                                                    "
                                                >
                                                    {{
                                                        block.options_intro_description
                                                    }}
                                                </p>
                                            </div>
                                            <button
                                                v-for="(
                                                    item, itemIndex
                                                ) in optionsDisplayItems(block)"
                                                :key="`${block.id}-opt-item-${item.id ?? itemIndex}`"
                                                type="button"
                                                class="border text-sm text-white"
                                                :class="[
                                                    optionsItemWidthClass(
                                                        block,
                                                    ),
                                                    optionsCardRadiusClass(
                                                        block,
                                                    ),
                                                    optionsCardSpacingClass(
                                                        block,
                                                    ),
                                                    optionsCardToneClass(block),
                                                    optionsCardShadowClass(
                                                        block,
                                                    ),
                                                    optionsCardMinWidthClass(
                                                        block,
                                                    ),
                                                ]"
                                            >
                                                <div
                                                    :class="
                                                        optionsBodyClass(block)
                                                    "
                                                >
                                                    <span
                                                        v-if="
                                                            normalizeDetailValue(
                                                                block.options_detail,
                                                            ) !== 'none'
                                                        "
                                                        :data-testid="`option-detail-${block.id}-${itemIndex}`"
                                                        class="mt-0.5 inline-flex shrink-0 items-center justify-center text-xs font-semibold text-[#1b2333]"
                                                        :class="[
                                                            optionsDetailWrapClass(
                                                                block,
                                                            ),
                                                            optionsDetailBadgeClass(
                                                                block,
                                                            ),
                                                            normalizeDetailValue(
                                                                block.options_detail,
                                                            ) !== 'checkout'
                                                                ? 'bg-[#d2d8e4]'
                                                                : '',
                                                            optionsDetailTextClass(
                                                                block,
                                                            ),
                                                        ]"
                                                    >
                                                        {{
                                                            optionsDetailLabel(
                                                                block,
                                                                item,
                                                                itemIndex,
                                                            )
                                                        }}
                                                    </span>
                                                    <div
                                                        v-if="
                                                            optionsShouldRenderImage(
                                                                block,
                                                                item,
                                                            )
                                                        "
                                                        :data-testid="`option-image-${block.id}-${itemIndex}`"
                                                        class="shrink-0"
                                                        :class="
                                                            optionsMediaOrderClass(
                                                                block,
                                                            )
                                                        "
                                                    >
                                                        <div
                                                            :class="
                                                                optionsImageWrapClass(
                                                                    block,
                                                                )
                                                            "
                                                        >
                                                            <img
                                                                :src="
                                                                    sanitizeStoredAssetUrl(
                                                                        item.image_url,
                                                                    ) ??
                                                                    undefined
                                                                "
                                                                alt="Imagem da opcao"
                                                                class="h-full w-full object-cover"
                                                            />
                                                        </div>
                                                    </div>
                                                    <span
                                                        class="block min-w-0 flex-1 text-[1.02rem] leading-7"
                                                        :class="[
                                                            optionTextAlignClass(
                                                                block,
                                                            ),
                                                            optionsLabelOrderClass(
                                                                block,
                                                            ),
                                                        ]"
                                                    >
                                                        {{ item.label }}
                                                    </span>
                                                </div>
                                            </button>
                                        </div>
                                    </template>
                                    <template v-else-if="block.type === 'faq'">
                                        <div
                                            v-for="(
                                                item, optionIndex
                                            ) in normalizeFaqItems(
                                                block.option_items,
                                                block.options,
                                            )"
                                            :key="`${block.id}-faq-preview-${item.id}`"
                                            class="border-b border-[#2a4e88]/70 px-1 py-1.5"
                                        >
                                            <div
                                                class="flex items-center justify-between gap-3"
                                            >
                                                <p
                                                    class="text-base font-semibold text-white"
                                                >
                                                    {{ item.label }}
                                                </p>
                                                <span
                                                    v-if="
                                                        (block.faq_detail ??
                                                            'arrow') !== 'none'
                                                    "
                                                    class="text-sm font-semibold text-[#8fb45c]"
                                                    >{{
                                                        faqDetailLabel(
                                                            block,
                                                            optionIndex,
                                                        )
                                                    }}</span
                                                >
                                            </div>
                                            <p
                                                v-if="
                                                    (block.faq_first_active ??
                                                        true) &&
                                                    optionIndex === 0
                                                "
                                                class="mt-1 text-sm leading-6 text-[#9fb3cf]"
                                            >
                                                {{ item.description }}
                                            </p>
                                        </div>
                                    </template>
                                    <template
                                        v-else-if="block.type === 'price'"
                                    >
                                        <div
                                            class="rounded-2xl border px-3 py-2"
                                            :class="
                                                block.price_style === 'dark'
                                                    ? 'border-[#355d9f] bg-[#0b274f]'
                                                    : block.price_style ===
                                                        'light'
                                                      ? 'border-[#d5deed] bg-[#f7f9fe] text-[#0d1a31]'
                                                      : 'border-[#6faa2a] bg-[#eef8e5] text-[#0d1a31]'
                                            "
                                        >
                                            <div
                                                :class="
                                                    (block.price_layout ??
                                                        'horizontal') ===
                                                    'vertical'
                                                        ? 'space-y-2'
                                                        : 'flex items-center justify-between gap-3'
                                                "
                                            >
                                                <p
                                                    v-if="
                                                        block.price_title?.trim()
                                                            .length
                                                    "
                                                    class="text-xl font-semibold"
                                                    :class="
                                                        block.price_style ===
                                                        'dark'
                                                            ? 'text-white'
                                                            : 'text-[#091225]'
                                                    "
                                                >
                                                    {{ block.price_title }}
                                                </p>
                                                <div
                                                    class="rounded-xl px-2 py-1.5"
                                                    :class="
                                                        block.price_style ===
                                                        'dark'
                                                            ? 'bg-[#13386f]'
                                                            : 'bg-[#e9edf3]'
                                                    "
                                                >
                                                    <p
                                                        v-if="
                                                            block.price_prefix?.trim()
                                                                .length
                                                        "
                                                        class="text-sm text-[#5f6875]"
                                                    >
                                                        {{ block.price_prefix }}
                                                    </p>
                                                    <p
                                                        v-if="
                                                            block.price_value?.trim()
                                                                .length
                                                        "
                                                        class="text-2xl font-semibold"
                                                        :class="
                                                            block.price_style ===
                                                            'dark'
                                                                ? 'text-white'
                                                                : 'text-[#0a1224]'
                                                        "
                                                    >
                                                        {{ block.price_value }}
                                                    </p>
                                                    <p
                                                        v-if="
                                                            block.price_suffix?.trim()
                                                                .length
                                                        "
                                                        class="text-sm text-[#5f6875]"
                                                    >
                                                        {{ block.price_suffix }}
                                                    </p>
                                                </div>
                                            </div>
                                            <p
                                                v-if="
                                                    block.price_badge_text?.trim()
                                                        .length
                                                "
                                                class="mt-1 text-xs font-medium text-[#4f8a19]"
                                            >
                                                {{ block.price_badge_text }}
                                            </p>
                                        </div>
                                    </template>
                                    <template
                                        v-else-if="block.type === 'metrics'"
                                    >
                                        <div class="grid gap-2 sm:grid-cols-3">
                                            <div
                                                v-for="item in metricItems(
                                                    block,
                                                )"
                                                :key="`${block.id}-metric-${item.id}`"
                                                class="min-w-0 overflow-hidden rounded-2xl border border-[#2a4e88] bg-[#0b274f] px-3 py-3"
                                            >
                                                <p
                                                    v-if="item.value.length"
                                                    class="text-[1.55rem] leading-none font-semibold wrap-anywhere text-white"
                                                >
                                                    {{ item.value }}
                                                </p>
                                                <p
                                                    v-if="item.label.length"
                                                    class="text-sm font-medium wrap-anywhere text-[#dce9ff]"
                                                    :class="
                                                        item.value.length
                                                            ? 'mt-2'
                                                            : ''
                                                    "
                                                >
                                                    {{ item.label }}
                                                </p>
                                                <p
                                                    v-if="
                                                        item.description.length
                                                    "
                                                    class="text-xs leading-5 wrap-anywhere text-[#8eb2ea]"
                                                    :class="
                                                        item.value.length ||
                                                        item.label.length
                                                            ? 'mt-1'
                                                            : ''
                                                    "
                                                >
                                                    {{ item.description }}
                                                </p>
                                            </div>
                                        </div>
                                    </template>
                                    <template
                                        v-else-if="
                                            block.type === 'before_after'
                                        "
                                    >
                                        <div class="grid grid-cols-2 gap-2">
                                            <div
                                                v-for="item in beforeAfterItems(
                                                    block,
                                                )"
                                                :key="`${block.id}-preview-${item.label}`"
                                                class="rounded-xl border border-[#2a4e88] bg-[#0b274f] px-3 py-2.5"
                                            >
                                                <p
                                                    class="text-xs tracking-wide text-[#9cc1ff] uppercase"
                                                >
                                                    {{ item.label }}
                                                </p>
                                                <p
                                                    class="mt-1 text-sm text-white"
                                                >
                                                    {{ item.value }}
                                                </p>
                                            </div>
                                        </div>
                                    </template>
                                    <template
                                        v-else-if="
                                            block.type === 'testimonials'
                                        "
                                    >
                                        <div
                                            :class="testimonialGridClass(block)"
                                        >
                                            <article
                                                v-for="item in testimonialItems(
                                                    block,
                                                )"
                                                :key="`${block.id}-testimonial-${item.id}`"
                                                class="border text-[#dde8fa]"
                                                :class="[
                                                    optionsCardRadiusClass(
                                                        block,
                                                    ),
                                                    optionsCardToneClass(block),
                                                    'px-2.5 py-2',
                                                    optionsCardShadowClass(
                                                        block,
                                                    ),
                                                    block.testimonials_layout ===
                                                    'slide'
                                                        ? 'min-w-[220px]'
                                                        : '',
                                                ]"
                                            >
                                                <div
                                                    class="flex items-center gap-1 text-amber-300"
                                                >
                                                    <span
                                                        v-for="starIndex in ratingStars(
                                                            item.rating,
                                                        )"
                                                        :key="`${item.id}-star-${starIndex}`"
                                                        >&#9733;</span
                                                    >
                                                </div>
                                                <p
                                                    v-if="
                                                        item.label?.trim()
                                                            .length
                                                    "
                                                    class="mt-0.5 text-base font-semibold text-white"
                                                >
                                                    {{ item.label }}
                                                </p>
                                                <p
                                                    v-if="
                                                        item.subtitle?.trim()
                                                            .length
                                                    "
                                                    class="text-sm text-[#a9c1e9]"
                                                >
                                                    {{ item.subtitle }}
                                                </p>
                                                <p
                                                    v-if="
                                                        item.description?.trim()
                                                            .length
                                                    "
                                                    class="mt-1.5 text-base leading-6 text-[#d8e5f8]"
                                                >
                                                    {{ item.description }}
                                                </p>
                                            </article>
                                        </div>
                                    </template>
                                    <template
                                        v-else-if="block.type === 'arguments'"
                                    >
                                        <ul class="space-y-2">
                                            <li
                                                v-for="(
                                                    option, optionIndex
                                                ) in argumentItems(block)"
                                                :key="`${block.id}-${optionIndex}`"
                                                class="flex items-center gap-2 rounded-xl border border-[#2a4e88] bg-[#0b274f] px-3 py-2"
                                            >
                                                <span class="text-[#7fb3ff]"
                                                    >*</span
                                                >
                                                <span
                                                    class="text-sm text-white"
                                                    >{{ option }}</span
                                                >
                                            </li>
                                        </ul>
                                    </template>
                                    <template v-else>
                                        <button
                                            v-for="(
                                                option, optionIndex
                                            ) in block.options ?? []"
                                            :key="`${block.id}-${optionIndex}`"
                                            class="flex w-full items-center gap-3 rounded-xl border border-[#2a4e88] bg-[#0b274f] px-3.5 py-3 text-left transition hover:border-[#4f8fff]"
                                        >
                                            <span
                                                class="flex h-7 w-7 items-center justify-center rounded-md bg-[#123a7b] text-xs font-bold text-white"
                                                >{{ optionIndex + 1 }}</span
                                            >
                                            <span class="text-lg text-white">{{
                                                option
                                            }}</span>
                                        </button>
                                    </template>
                                </div>

                                <div
                                    v-else-if="block.type === 'price'"
                                    class="rounded-xl border px-2.5 py-1.5 text-left transition"
                                    :class="
                                        block.price_style === 'dark'
                                            ? 'border-[#355d9f] bg-[#0b274f]'
                                            : block.price_style === 'light'
                                              ? 'border-[#d5deed] bg-[#f7f9fe] text-[#0d1a31]'
                                              : 'border-[#6faa2a] bg-[#eef8e5] text-[#0d1a31]'
                                    "
                                    :title="
                                        (block.price_mode ?? 'illustrative') ===
                                        'redirect'
                                            ? 'Abrir link do plano'
                                            : undefined
                                    "
                                    :style="{
                                        cursor:
                                            (block.price_mode ??
                                                'illustrative') === 'redirect'
                                                ? 'pointer'
                                                : 'default',
                                    }"
                                >
                                    <div
                                        :class="
                                            (block.price_layout ??
                                                'horizontal') === 'vertical'
                                                ? 'space-y-2'
                                                : 'flex items-center justify-between gap-3'
                                        "
                                    >
                                        <p
                                            v-if="
                                                block.price_title?.trim().length
                                            "
                                            class="text-lg font-semibold"
                                            :class="
                                                block.price_style === 'dark'
                                                    ? 'text-white'
                                                    : 'text-[#091225]'
                                            "
                                        >
                                            {{ block.price_title }}
                                        </p>
                                        <div
                                            class="rounded-lg px-2 py-1"
                                            :class="
                                                block.price_style === 'dark'
                                                    ? 'bg-[#13386f]'
                                                    : 'bg-[#e9edf3]'
                                            "
                                        >
                                            <p
                                                v-if="
                                                    block.price_prefix?.trim()
                                                        .length
                                                "
                                                class="text-xs text-[#5f6875]"
                                            >
                                                {{ block.price_prefix }}
                                            </p>
                                            <p
                                                v-if="
                                                    block.price_value?.trim()
                                                        .length
                                                "
                                                class="text-xl font-semibold"
                                                :class="
                                                    block.price_style === 'dark'
                                                        ? 'text-white'
                                                        : 'text-[#0a1224]'
                                                "
                                            >
                                                {{ block.price_value }}
                                            </p>
                                            <p
                                                v-if="
                                                    block.price_suffix?.trim()
                                                        .length
                                                "
                                                class="text-sm text-[#5f6875]"
                                            >
                                                {{ block.price_suffix }}
                                            </p>
                                        </div>
                                    </div>
                                    <p
                                        v-if="
                                            block.price_badge_text?.trim()
                                                .length
                                        "
                                        class="mt-1 text-xs font-medium text-[#4f8a19]"
                                    >
                                        {{ block.price_badge_text }}
                                    </p>
                                </div>

                                <div
                                    v-else-if="block.type === 'carousel'"
                                    class="rounded-xl border p-2"
                                    :class="
                                        block.carousel_border_type === 'strong'
                                            ? 'border-[#2a4e88] bg-[#0b274f]'
                                            : block.carousel_border_type ===
                                                'subtle'
                                              ? 'border-[#365b92]/60 bg-[#0b274f]/70'
                                              : 'border-transparent bg-transparent'
                                    "
                                >
                                    <div
                                        v-if="
                                            carouselShowsImage(block) &&
                                            currentCarouselPreviewItem(block)
                                                ?.image
                                        "
                                        class="rounded-2xl bg-[#bfd3b2] p-1"
                                    >
                                        <div
                                            class="aspect-[4/3] w-full rounded-2xl bg-[#bfd3b2]"
                                        >
                                            <img
                                                v-if="
                                                    currentCarouselPreviewItem(
                                                        block,
                                                    )?.image
                                                "
                                                :src="
                                                    currentCarouselPreviewItem(
                                                        block,
                                                    )?.image
                                                "
                                                alt="Imagem do slide"
                                                class="h-full w-full rounded-2xl object-cover"
                                            />
                                        </div>
                                    </div>
                                    <p
                                        v-if="
                                            carouselShowsDescription(block) &&
                                            currentCarouselPreviewItem(block)
                                                ?.description
                                        "
                                        :data-testid="`builder-carousel-current-${block.id}`"
                                        class="mt-2 text-center text-lg text-[#9fb3cf]"
                                    >
                                        {{
                                            currentCarouselPreviewItem(block)
                                                ?.description
                                        }}
                                    </p>
                                    <div
                                        v-if="
                                            block.carousel_pagination !==
                                                false &&
                                            carouselPreviewItems(block).length >
                                                1
                                        "
                                        class="mt-2 flex items-center justify-center gap-2"
                                    >
                                        <span
                                            v-for="(
                                                item, itemIndex
                                            ) in carouselPreviewItems(block)"
                                            :key="`${block.id}-preview-dot-${item.id}`"
                                            class="rounded-full"
                                            :class="
                                                itemIndex ===
                                                carouselPreviewIndex(block)
                                                    ? 'h-2.5 w-2.5 bg-[#6faa2a]'
                                                    : 'h-1.5 w-1.5 bg-[#a8ba99]'
                                            "
                                        />
                                    </div>
                                </div>

                                <div
                                    v-else-if="block.type === 'spacer'"
                                    :data-testid="`builder-spacer-${block.id}`"
                                    class="rounded-xl border border-dashed border-[#3562a7] bg-[#0b274f] px-3.5 text-center text-sm text-[#9cc1ff]"
                                    :style="{
                                        paddingTop: `${spacerHeight(block)}px`,
                                        paddingBottom: `${spacerHeight(block)}px`,
                                    }"
                                >
                                    Espaco entre blocos
                                </div>

                                <div
                                    v-else-if="block.type === 'image'"
                                    class="border"
                                    :class="[
                                        'border-[#2f538f]',
                                        imageFrameClass(block),
                                        imageRadiusClass(block),
                                    ]"
                                >
                                    <div
                                        v-if="imageAspectClass(block)"
                                        :class="[
                                            'w-full overflow-hidden',
                                            imageAspectClass(block),
                                            imageRadiusClass(block),
                                        ]"
                                    >
                                        <div
                                            v-if="
                                                uploadingImageBlockId ===
                                                block.id
                                            "
                                            :data-testid="`builder-image-uploading-${block.id}`"
                                            role="status"
                                            class="flex h-full items-center justify-center border border-dashed border-[#4b89ff] text-sm font-medium text-[#b9d2ff]"
                                            :class="imageRadiusClass(block)"
                                        >
                                            <span class="animate-pulse"
                                                >Carregando imagem...</span
                                            >
                                        </div>
                                        <img
                                            v-else-if="
                                                sanitizeStoredAssetUrl(
                                                    block.placeholder,
                                                ) !== null
                                            "
                                            :src="
                                                sanitizeStoredAssetUrl(
                                                    block.placeholder,
                                                ) ?? undefined
                                            "
                                            alt="Imagem do bloco"
                                            :class="[
                                                'h-full w-full',
                                                imageFitClass(block),
                                                imageRadiusClass(block),
                                            ]"
                                        />
                                        <div
                                            v-else
                                            class="flex h-full items-center justify-center border border-dashed border-[#3562a7] text-sm text-[#9cc1ff]"
                                            :class="imageRadiusClass(block)"
                                        >
                                            Imagem nao configurada
                                        </div>
                                    </div>
                                    <template v-else>
                                        <div
                                            v-if="
                                                uploadingImageBlockId ===
                                                block.id
                                            "
                                            :data-testid="`builder-image-uploading-${block.id}`"
                                            role="status"
                                            class="flex h-40 items-center justify-center border border-dashed border-[#4b89ff] text-sm font-medium text-[#b9d2ff]"
                                            :class="imageRadiusClass(block)"
                                        >
                                            <span class="animate-pulse"
                                                >Carregando imagem...</span
                                            >
                                        </div>
                                        <img
                                            v-else-if="
                                                sanitizeStoredAssetUrl(
                                                    block.placeholder,
                                                ) !== null
                                            "
                                            :src="
                                                sanitizeStoredAssetUrl(
                                                    block.placeholder,
                                                ) ?? undefined
                                            "
                                            alt="Imagem do bloco"
                                            :class="[
                                                'max-h-[22rem] w-full',
                                                imageFitClass(block),
                                                imageRadiusClass(block),
                                            ]"
                                        />
                                        <div
                                            v-else
                                            class="flex h-40 items-center justify-center border border-dashed border-[#3562a7] text-sm text-[#9cc1ff]"
                                            :class="imageRadiusClass(block)"
                                        >
                                            Imagem nao configurada
                                        </div>
                                    </template>
                                </div>

                                <div
                                    v-else-if="block.type === 'video'"
                                    class="rounded-xl border border-[#2f538f] p-2"
                                    :style="{ backgroundColor: '#0b274f' }"
                                >
                                    <div
                                        v-if="
                                            toEmbedVideoUrl(block.placeholder)
                                        "
                                        :class="[
                                            'overflow-hidden rounded-lg border border-[#2f538f]',
                                            getVideoAspectClass(block),
                                        ]"
                                    >
                                        <iframe
                                            :data-testid="`builder-video-preview-${block.id}`"
                                            :src="
                                                toEmbedVideoUrl(
                                                    block.placeholder,
                                                ) ?? undefined
                                            "
                                            title="Preview do video"
                                            class="h-full w-full"
                                            allow="
                                                accelerometer;
                                                autoplay;
                                                clipboard-write;
                                                encrypted-media;
                                                gyroscope;
                                                picture-in-picture;
                                                web-share;
                                            "
                                            allowfullscreen
                                        />
                                    </div>
                                    <div
                                        v-else
                                        class="flex h-40 items-center justify-center rounded-lg border border-dashed border-[#3562a7] text-sm text-[#9cc1ff]"
                                    >
                                        Video nao configurado
                                    </div>
                                </div>

                                <div
                                    v-else-if="block.type === 'audio'"
                                    class="relative rounded-[26px] border p-3"
                                    :class="
                                        (block.audio_theme ?? 'light') ===
                                        'dark'
                                            ? 'border-[#274573] bg-[#0b1f42]'
                                            : 'border-[#e6e1d8] bg-[#efebe5]'
                                    "
                                    :style="
                                        (block.audio_theme ?? 'light') ===
                                        'dark'
                                            ? {}
                                            : {
                                                  backgroundImage:
                                                      'radial-gradient(circle at 10px 10px, rgba(255,255,255,0.35) 1px, transparent 1px)',
                                                  backgroundSize: '18px 18px',
                                              }
                                    "
                                >
                                    <span
                                        class="absolute top-[26px] left-[15px] h-3 w-3 rotate-45"
                                        :class="
                                            (block.audio_theme ?? 'light') ===
                                            'dark'
                                                ? 'border-t border-l border-[#33598d] bg-[#102a56]'
                                                : 'border-t border-l border-[#ece7df] bg-white'
                                        "
                                    />
                                    <div
                                        class="relative rounded-[22px] border px-3 py-3 shadow-[0_1px_0_rgba(0,0,0,0.06)]"
                                        :class="
                                            (block.audio_theme ?? 'light') ===
                                            'dark'
                                                ? 'border-[#33598d] bg-[#102a56]'
                                                : 'border-[#ece7df] bg-white'
                                        "
                                    >
                                        <audio
                                            :ref="
                                                (element) =>
                                                    bindAudioElement(
                                                        audioPreviewKey(
                                                            currentStage.clientId,
                                                            block.id,
                                                        ),
                                                        element as HTMLAudioElement | null,
                                                    )
                                            "
                                            :src="
                                                audioSource(block) ?? undefined
                                            "
                                            preload="metadata"
                                            class="hidden"
                                            @loadedmetadata="
                                                onAudioLoadedMetadata(
                                                    audioPreviewKey(
                                                        currentStage.clientId,
                                                        block.id,
                                                    ),
                                                    $event,
                                                )
                                            "
                                            @timeupdate="
                                                onAudioTimeUpdate(
                                                    audioPreviewKey(
                                                        currentStage.clientId,
                                                        block.id,
                                                    ),
                                                    $event,
                                                )
                                            "
                                            @play="
                                                onAudioPlay(
                                                    audioPreviewKey(
                                                        currentStage.clientId,
                                                        block.id,
                                                    ),
                                                )
                                            "
                                            @pause="
                                                onAudioPause(
                                                    audioPreviewKey(
                                                        currentStage.clientId,
                                                        block.id,
                                                    ),
                                                )
                                            "
                                            @ended="
                                                onAudioEnded(
                                                    audioPreviewKey(
                                                        currentStage.clientId,
                                                        block.id,
                                                    ),
                                                )
                                            "
                                        />
                                        <div class="flex items-center gap-3">
                                            <button
                                                type="button"
                                                :data-testid="`builder-audio-toggle-${block.id}`"
                                                class="grid h-9 w-9 shrink-0 place-items-center rounded-full transition disabled:opacity-50"
                                                :disabled="!audioSource(block)"
                                                @click.stop="
                                                    toggleAudioPlayback(
                                                        audioPreviewKey(
                                                            currentStage.clientId,
                                                            block.id,
                                                        ),
                                                    )
                                                "
                                            >
                                                <template
                                                    v-if="
                                                        activeAudioKey ===
                                                        audioPreviewKey(
                                                            currentStage.clientId,
                                                            block.id,
                                                        )
                                                    "
                                                >
                                                    <span
                                                        class="flex items-center gap-1"
                                                    >
                                                        <span
                                                            class="h-4 w-1 rounded-full"
                                                            :class="
                                                                (block.audio_theme ??
                                                                    'light') ===
                                                                'dark'
                                                                    ? 'bg-[#b8cbe8]'
                                                                    : 'bg-[#9f9393]'
                                                            "
                                                        />
                                                        <span
                                                            class="h-4 w-1 rounded-full"
                                                            :class="
                                                                (block.audio_theme ??
                                                                    'light') ===
                                                                'dark'
                                                                    ? 'bg-[#b8cbe8]'
                                                                    : 'bg-[#9f9393]'
                                                            "
                                                        />
                                                    </span>
                                                </template>
                                                <span
                                                    v-else
                                                    class="ml-0.5 inline-block h-0 w-0 border-y-[8px] border-l-[12px] border-y-transparent"
                                                    :class="
                                                        (block.audio_theme ??
                                                            'light') === 'dark'
                                                            ? 'border-l-[#b8cbe8]'
                                                            : 'border-l-[#9f9393]'
                                                    "
                                                />
                                            </button>
                                            <div class="min-w-0 flex-1">
                                                <div
                                                    :data-testid="`builder-audio-seek-${block.id}`"
                                                    class="relative cursor-pointer outline-none"
                                                    role="slider"
                                                    tabindex="0"
                                                    :aria-valuemin="0"
                                                    :aria-valuemax="100"
                                                    :aria-valuenow="
                                                        Math.round(
                                                            audioProgressRatio(
                                                                audioPreviewKey(
                                                                    currentStage.clientId,
                                                                    block.id,
                                                                ),
                                                            ) * 100,
                                                        )
                                                    "
                                                    @click.stop="
                                                        seekAudio(
                                                            audioPreviewKey(
                                                                currentStage.clientId,
                                                                block.id,
                                                            ),
                                                            $event,
                                                        )
                                                    "
                                                    @keydown.stop="
                                                        handleAudioKeyboard(
                                                            audioPreviewKey(
                                                                currentStage.clientId,
                                                                block.id,
                                                            ),
                                                            $event,
                                                        )
                                                    "
                                                >
                                                    <div
                                                        class="flex items-center gap-[3px]"
                                                    >
                                                        <span
                                                            class="mr-1 inline-block h-3.5 w-3.5 rounded-full bg-[#58baf1]"
                                                        />
                                                        <span
                                                            v-for="(
                                                                barHeight,
                                                                barIndex
                                                            ) in audioWaveHeights"
                                                            :key="`audio-wave-${block.id}-${barIndex}`"
                                                            class="inline-block w-[3px] rounded-full transition-colors"
                                                            :class="
                                                                barIndex /
                                                                    audioWaveHeights.length <
                                                                audioProgressRatio(
                                                                    audioPreviewKey(
                                                                        currentStage.clientId,
                                                                        block.id,
                                                                    ),
                                                                )
                                                                    ? 'bg-[#58baf1]'
                                                                    : (block.audio_theme ??
                                                                            'light') ===
                                                                        'dark'
                                                                      ? 'bg-[#8ea3c5]/65'
                                                                      : 'bg-[#d5d6d8]'
                                                            "
                                                            :style="{
                                                                height: `${barHeight}px`,
                                                            }"
                                                        />
                                                    </div>
                                                </div>
                                                <div
                                                    class="mt-2 flex items-center justify-between pl-0.5 text-[11px] leading-none font-medium tracking-wide"
                                                    :class="
                                                        (block.audio_theme ??
                                                            'light') === 'dark'
                                                            ? 'text-[#bfd4f4]'
                                                            : 'text-[#7a8ea9]'
                                                    "
                                                >
                                                    <p
                                                        :data-testid="`builder-audio-current-${block.id}`"
                                                    >
                                                        {{
                                                            displayedAudioCurrentTime(
                                                                audioPreviewKey(
                                                                    currentStage.clientId,
                                                                    block.id,
                                                                ),
                                                            )
                                                        }}
                                                    </p>
                                                    <p
                                                        :data-testid="`builder-audio-duration-${block.id}`"
                                                    >
                                                        {{
                                                            displayedAudioDuration(
                                                                audioPreviewKey(
                                                                    currentStage.clientId,
                                                                    block.id,
                                                                ),
                                                            )
                                                        }}
                                                    </p>
                                                </div>
                                            </div>
                                            <div
                                                class="relative h-10 w-10 shrink-0 overflow-hidden rounded-full border"
                                                :class="
                                                    (block.audio_theme ??
                                                        'light') === 'dark'
                                                        ? 'border-[#4f6f9f] bg-[#cad3df]'
                                                        : 'border-[#d8dde3] bg-[#d2d9e2]'
                                                "
                                            >
                                                <img
                                                    v-if="
                                                        sanitizeStoredAssetUrl(
                                                            block.audio_avatar_url,
                                                        )
                                                    "
                                                    :src="
                                                        sanitizeStoredAssetUrl(
                                                            block.audio_avatar_url,
                                                        ) ?? undefined
                                                    "
                                                    alt="Avatar do audio"
                                                    class="h-full w-full object-cover"
                                                />
                                                <template v-else>
                                                    <div
                                                        class="absolute top-[6px] left-1/2 h-[10px] w-[10px] -translate-x-1/2 rounded-full bg-[#edf1f6]"
                                                    />
                                                    <div
                                                        class="absolute top-[17px] left-1/2 h-[14px] w-[22px] -translate-x-1/2 rounded-[999px_999px_8px_8px] bg-[#edf1f6]"
                                                    />
                                                </template>
                                                <div
                                                    class="absolute bottom-[1px] left-[1px] grid h-[15px] w-[15px] place-items-center rounded-full bg-white shadow-sm"
                                                >
                                                    <span
                                                        class="block h-[7px] w-[4px] rounded-[999px] border-2 border-b-0 border-[#39b6f3]"
                                                    />
                                                    <span
                                                        class="absolute bottom-[4px] h-[3px] w-[2px] bg-[#39b6f3]"
                                                    />
                                                    <span
                                                        class="absolute bottom-[2px] h-[2px] w-[6px] rounded bg-[#39b6f3]"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                        <p
                                            v-if="
                                                safeTrim(block.audio_sender)
                                                    .length
                                            "
                                            class="pt-2 text-[9px] tracking-[0.08em] uppercase"
                                            :class="
                                                (block.audio_theme ??
                                                    'light') === 'dark'
                                                    ? 'text-[#9fb8dd]'
                                                    : 'text-[#9ca7b7]'
                                            "
                                        >
                                            {{ block.audio_sender }}
                                        </p>
                                        <p
                                            v-if="!audioSource(block)"
                                            class="pt-2 text-[10px]"
                                            :class="
                                                (block.audio_theme ??
                                                    'light') === 'dark'
                                                    ? 'text-[#86a2cf]'
                                                    : 'text-[#8793a5]'
                                            "
                                        >
                                            Audio nao configurado
                                        </p>
                                    </div>
                                </div>

                                <div
                                    v-else-if="block.type === 'attention'"
                                    class="rounded-[20px] border text-center text-base leading-normal break-words whitespace-pre-line md:text-lg"
                                    :class="[
                                        attentionToneClass(block),
                                        attentionPaddingClass(block),
                                        attentionHighlightClass(block),
                                    ]"
                                >
                                    {{ previewDynamicText(block.placeholder) }}
                                </div>

                                <div
                                    v-else-if="block.type === 'notification'"
                                    :data-testid="`builder-notification-${block.id}`"
                                    :class="
                                        builderNotificationPreviewShellClass(
                                            block,
                                        )
                                    "
                                >
                                    <div
                                        class="flex w-full"
                                        :class="
                                            notificationHasFloatingPosition(
                                                block,
                                            )
                                                ? builderNotificationPreviewAlignmentClass(
                                                      block,
                                                  )
                                                : ''
                                        "
                                        :data-testid="
                                            notificationHasFloatingPosition(
                                                block,
                                            )
                                                ? `builder-notification-preview-frame-${block.id}`
                                                : undefined
                                        "
                                    >
                                        <div
                                            :class="
                                                notificationSizeClass(block)
                                            "
                                        >
                                            <div
                                                class="rounded-[22px] border shadow-[0_16px_38px_rgba(9,18,36,0.24)]"
                                                :class="[
                                                    notificationToneClass(
                                                        block,
                                                    ),
                                                    notificationCardPaddingClass(
                                                        block,
                                                    ),
                                                ]"
                                            >
                                                <div
                                                    class="flex items-center justify-between gap-3 text-[10px] tracking-[0.22em] uppercase opacity-65"
                                                >
                                                    <span
                                                        class="inline-flex items-center gap-2"
                                                    />
                                                    <span
                                                        class="inline-flex items-center gap-1.5"
                                                    >
                                                        <span
                                                            class="inline-block h-1.5 w-1.5 rounded-full"
                                                            :class="
                                                                notificationAccentClass(
                                                                    block,
                                                                )
                                                            "
                                                        />
                                                        {{
                                                            notificationTimeBadge(
                                                                block,
                                                            )
                                                        }}
                                                    </span>
                                                </div>
                                                <div
                                                    class="mt-2 flex items-start gap-3"
                                                >
                                                    <div
                                                        class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full border"
                                                        :class="
                                                            notificationAvatarShellClass(
                                                                block,
                                                            )
                                                        "
                                                    >
                                                        <img
                                                            v-if="
                                                                notificationAvatarUrl(
                                                                    block,
                                                                    previewNotificationVariation(
                                                                        block,
                                                                    ),
                                                                )
                                                            "
                                                            :src="
                                                                notificationAvatarUrl(
                                                                    block,
                                                                    previewNotificationVariation(
                                                                        block,
                                                                    ),
                                                                ) ?? undefined
                                                            "
                                                            alt="Avatar da notificacao"
                                                            class="h-full w-full object-cover"
                                                        />
                                                        <span
                                                            v-else
                                                            class="inline-block h-3 w-3 rounded-full"
                                                            :class="
                                                                notificationAccentClass(
                                                                    block,
                                                                )
                                                            "
                                                        />
                                                    </div>
                                                    <div class="min-w-0 flex-1">
                                                        <p
                                                            v-if="
                                                                notificationTitleText(
                                                                    block,
                                                                ).length
                                                            "
                                                            class="leading-tight font-semibold"
                                                            :class="
                                                                notificationTitleClass(
                                                                    block,
                                                                )
                                                            "
                                                        >
                                                            {{
                                                                replaceNotificationTokens(
                                                                    notificationTitleText(
                                                                        block,
                                                                    ),
                                                                    previewNotificationVariation(
                                                                        block,
                                                                    ),
                                                                )
                                                            }}
                                                        </p>
                                                        <p
                                                            v-if="
                                                                notificationDescriptionText(
                                                                    block,
                                                                ).length
                                                            "
                                                            class="leading-snug opacity-80"
                                                            :class="[
                                                                notificationDescriptionClass(
                                                                    block,
                                                                ),
                                                                notificationTitleText(
                                                                    block,
                                                                ).length
                                                                    ? 'mt-1.5'
                                                                    : 'mt-0.5',
                                                            ]"
                                                        >
                                                            {{
                                                                replaceNotificationTokens(
                                                                    notificationDescriptionText(
                                                                        block,
                                                                    ),
                                                                    previewNotificationVariation(
                                                                        block,
                                                                    ),
                                                                )
                                                            }}
                                                        </p>
                                                    </div>
                                                </div>
                                                <div
                                                    class="mt-3 h-1.5 overflow-hidden rounded-full bg-black/10"
                                                >
                                                    <div
                                                        class="h-full w-[58%] rounded-full bg-black/20"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-else-if="block.type === 'timer'"
                                    class="rounded-2xl border px-3 py-2 text-center text-base leading-tight"
                                    :class="
                                        block.timer_style === 'amber'
                                            ? 'border-[#f0dfb1] bg-[#fff4da] text-[#9f6500]'
                                            : block.timer_style === 'blue'
                                              ? 'border-[#c3d8ff] bg-[#eaf2ff] text-[#1f4ea5]'
                                              : 'border-[#f0c9c9] bg-[#f8dfdf] text-[#c62828]'
                                    "
                                >
                                    {{ timerDisplayText(block) }}
                                </div>

                                <div
                                    v-else-if="block.type === 'loading'"
                                    class="rounded-xl border border-[#e7eaf0] bg-white px-2.5 py-2 text-[#0b1020]"
                                >
                                    <div
                                        v-if="
                                            block.loading_show_title !==
                                                false ||
                                            block.loading_show_progress !==
                                                false
                                        "
                                        class="flex items-center justify-between"
                                    >
                                        <p
                                            v-if="
                                                block.loading_show_title !==
                                                    false &&
                                                safeTrim(block.label).length
                                            "
                                            class="text-sm font-semibold"
                                        >
                                            {{ block.label }}
                                        </p>
                                        <p
                                            v-if="
                                                block.loading_show_progress !==
                                                false
                                            "
                                            class="text-sm font-semibold text-[#7a7f8a]"
                                        >
                                            {{ loadingPreviewProgress(block) }}%
                                        </p>
                                    </div>
                                    <div
                                        v-if="
                                            block.loading_show_progress !==
                                            false
                                        "
                                        class="mt-1 h-2.5 overflow-hidden rounded-full bg-[#e3e6ed]"
                                    >
                                        <div
                                            class="h-full rounded-full bg-[#030a1b] transition-all duration-300"
                                            :style="{
                                                width: `${loadingPreviewProgress(block)}%`,
                                            }"
                                        />
                                    </div>
                                    <p
                                        v-if="
                                            safeTrim(block.placeholder).length
                                        "
                                        class="mt-2 text-center text-[1.15rem] leading-snug break-words whitespace-pre-line text-[#576a84]"
                                    >
                                        {{
                                            previewDynamicText(
                                                block.placeholder,
                                            )
                                        }}
                                    </p>
                                </div>

                                <div
                                    v-else-if="block.type === 'level'"
                                    :data-testid="`builder-level-${block.id}`"
                                    class="rounded-xl border border-[#e7eaf0] bg-white px-3 py-2.5 text-[#0b1020]"
                                >
                                    <div
                                        class="flex items-end justify-between gap-2"
                                    >
                                        <div class="min-w-0">
                                            <p
                                                v-if="
                                                    block.level_title?.trim()
                                                        .length
                                                "
                                                class="text-lg font-semibold"
                                            >
                                                {{ block.level_title }}
                                            </p>
                                            <p
                                                v-if="
                                                    block.level_subtitle?.trim()
                                                        .length
                                                "
                                                :data-testid="`builder-level-subtitle-${block.id}`"
                                                class="text-[1.35rem] leading-tight text-[#576a84]"
                                            >
                                                {{ block.level_subtitle }}
                                            </p>
                                        </div>
                                        <p
                                            v-if="
                                                block.level_show_progress !==
                                                false
                                            "
                                            class="text-[1.35rem] font-semibold text-[#7a7f8a]"
                                        >
                                            {{ levelProgress(block) }}%
                                        </p>
                                    </div>
                                    <div
                                        class="mt-1.5 h-2.5 overflow-hidden rounded-full bg-[#e3e6ed]"
                                    >
                                        <div
                                            class="relative h-full rounded-full transition-all duration-300"
                                            :class="levelBarColorClass(block)"
                                            :style="{
                                                width: `${levelProgress(block)}%`,
                                            }"
                                        >
                                            <span
                                                v-if="
                                                    block.level_show_meter !==
                                                    false
                                                "
                                                class="absolute top-1/2 right-0 h-4 w-4 translate-x-1/2 -translate-y-1/2 rounded-full border-[4px] border-[#d8dce3] bg-[#f8f9fb]"
                                            />
                                        </div>
                                    </div>
                                    <p
                                        v-if="
                                            block.level_indicator_text?.trim()
                                                .length
                                        "
                                        class="mt-2 text-center text-sm text-[#576a84]"
                                    >
                                        {{ block.level_indicator_text }}
                                    </p>
                                    <p
                                        v-if="levelLegends(block).length"
                                        class="mt-1.5 text-center text-xs text-[#7a7f8a]"
                                    >
                                        {{ levelLegends(block).join(' | ') }}
                                    </p>
                                </div>

                                <div
                                    v-else-if="isMediaContentBlock(block.type)"
                                    class="rounded-xl border border-[#2a4e88] bg-[#0b274f] p-3.5"
                                >
                                    <p class="text-sm font-medium text-white">
                                        {{ block.label }}
                                    </p>
                                    <p class="mt-1 text-xs text-[#9cc1ff]">
                                        Bloco de
                                        {{
                                            blockTitle(block.type).toLowerCase()
                                        }}
                                    </p>
                                </div>

                                <div
                                    v-else
                                    class="rounded-xl border border-[#2a4e88] bg-[#0b274f] px-3.5 py-3 text-sm text-[#cde0ff]"
                                >
                                    {{ block.label }}
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </section>

            <aside
                class="min-h-0 overflow-y-auto bg-[#081633] px-3 py-2 xl:block"
                :class="mobileBuilderPanel === 'settings' ? 'block' : 'hidden'"
                @click.stop
            >
                <div
                    v-if="flashStatus"
                    class="mb-2 rounded-md border border-[#2e588f] bg-[#0a2146] px-2 py-1 text-[11px] text-[#9ebef5]"
                >
                    {{ flashMessage }}
                </div>
                <details
                    v-if="aiGeneration"
                    data-testid="builder-ai-strategy"
                    :open="flashStatus === 'ai-funnel-created'"
                    class="group mb-2 rounded-xl border border-[#2e588f] bg-[#0a2146] text-[#cde0ff]"
                >
                    <summary
                        class="flex cursor-pointer list-none items-center justify-between gap-2 px-3 py-2.5"
                    >
                        <span
                            class="inline-flex items-center gap-2 text-xs font-semibold"
                        >
                            <Sparkles class="size-3.5 text-[#67a2ff]" />
                            Estratégia criada pela IA
                        </span>
                        <span
                            class="rounded-full border px-2 py-0.5 text-[10px] font-semibold"
                            :class="
                                (aiGeneration.quality_score ?? 0) >= 82
                                    ? 'border-[#2b8f70] bg-[#0d3f39] text-[#8ce0c7]'
                                    : 'border-[#9a7134] bg-[#463414] text-[#f3c97e]'
                            "
                        >
                            {{ aiGeneration.quality_score ?? 0 }}/100
                        </span>
                    </summary>
                    <div
                        class="space-y-2 border-t border-[#234879] px-3 py-2.5"
                    >
                        <p
                            v-if="aiGeneration.objective_summary"
                            class="text-[11px] leading-relaxed font-medium text-[#d9e8ff]"
                        >
                            {{ aiGeneration.objective_summary }}
                        </p>
                        <p
                            v-if="aiGeneration.rationale"
                            class="text-[10px] leading-relaxed text-[#9ebef5]"
                        >
                            {{ aiGeneration.rationale }}
                        </p>
                        <ol
                            v-if="aiGeneration.stage_plan?.length"
                            class="space-y-1.5"
                        >
                            <li
                                v-for="(
                                    stage, index
                                ) in aiGeneration.stage_plan"
                                :key="`${stage.name}-${index}`"
                                class="rounded-lg bg-[#0b274f] px-2.5 py-2"
                            >
                                <p
                                    class="text-[10px] font-semibold text-[#d9e8ff]"
                                >
                                    {{ index + 1 }}. {{ stage.name }}
                                </p>
                                <p
                                    class="mt-0.5 text-[10px] leading-relaxed text-[#8fb2eb]"
                                >
                                    {{ stage.purpose }}
                                </p>
                            </li>
                        </ol>
                        <p
                            v-if="aiGeneration.correction_applied"
                            class="text-[10px] text-[#8ce0c7]"
                        >
                            A auditoria encontrou ajustes e a IA fez uma revisão
                            automática.
                        </p>
                        <ul
                            v-if="aiGeneration.quality_notes?.length"
                            class="list-disc space-y-1 pl-4 text-[10px] text-[#f3c97e]"
                        >
                            <li
                                v-for="note in aiGeneration.quality_notes"
                                :key="note"
                            >
                                {{ note }}
                            </li>
                        </ul>
                    </div>
                </details>
                <div
                    data-testid="builder-panel-tabs"
                    class="mb-2"
                    :class="
                        selectedBlock
                            ? 'grid grid-cols-3 gap-2'
                            : 'grid grid-cols-2 gap-2'
                    "
                >
                    <template v-if="selectedBlock">
                        <button
                            data-testid="builder-component-tab-component"
                            class="rounded-lg py-1.5 text-sm"
                            :class="
                                componentPanelTab === 'component'
                                    ? 'bg-[#1a4a99] font-medium text-white'
                                    : 'text-[#9bb9ef]'
                            "
                            @click="componentPanelTab = 'component'"
                        >
                            Componente
                        </button>
                        <button
                            data-testid="builder-component-tab-appearance"
                            class="rounded-lg py-1.5 text-sm"
                            :class="
                                componentPanelTab === 'appearance'
                                    ? 'bg-[#1a4a99] font-medium text-white'
                                    : 'text-[#9bb9ef]'
                            "
                            @click="componentPanelTab = 'appearance'"
                        >
                            Aparencia
                        </button>
                        <button
                            data-testid="builder-component-tab-display"
                            class="rounded-lg py-1.5 text-sm"
                            :class="
                                componentPanelTab === 'display'
                                    ? 'bg-[#1a4a99] font-medium text-white'
                                    : 'text-[#9bb9ef]'
                            "
                            @click="componentPanelTab = 'display'"
                        >
                            Exibicao
                        </button>
                    </template>
                    <template v-else>
                        <button
                            data-testid="builder-step-tab"
                            class="rounded-lg py-1.5 text-sm"
                            :class="
                                activePanelTab === 'step'
                                    ? 'bg-[#1a4a99] font-medium text-white'
                                    : 'text-[#9bb9ef]'
                            "
                            @click="activePanelTab = 'step'"
                        >
                            Etapa
                        </button>
                        <button
                            data-testid="builder-step-appearance-tab"
                            class="rounded-lg py-1.5 text-sm"
                            :class="
                                activePanelTab === 'appearance'
                                    ? 'bg-[#1a4a99] font-medium text-white'
                                    : 'text-[#9bb9ef]'
                            "
                            @click="activePanelTab = 'appearance'"
                        >
                            Aparencia
                        </button>
                    </template>
                </div>

                <div
                    v-if="props.permissions.canEdit"
                    class="mb-2 px-1 text-[11px] text-[#9bb9ef]"
                >
                    Atalhos: Ctrl/Cmd + S salvar, Ctrl/Cmd + Z desfazer,
                    Ctrl/Cmd + Shift + Z refazer, Ctrl/Cmd + D duplicar bloco,
                    Ctrl/Cmd + Shift + ↑/↓ mover bloco.
                </div>

                <div
                    v-if="
                        currentStage &&
                        (selectedBlock || activePanelTab === 'step')
                    "
                    class="space-y-3"
                >
                    <section v-if="!selectedBlock" class="px-1 pb-2">
                        <label class="text-xs text-[#8daee7]" for="stage-name"
                            >Sem titulo</label
                        >
                        <input
                            id="stage-name"
                            v-model="currentStage.name"
                            :disabled="!props.permissions.canEdit"
                            class="mt-1 w-full rounded-md border border-[#2f568f] bg-[#081a3c] px-2 py-2 text-sm text-white outline-none disabled:opacity-60"
                        />
                        <div
                            class="mt-3 rounded-md border border-[#23467f] bg-[#0b2148] px-3 py-2 text-xs leading-relaxed text-[#9bb9ef]"
                        >
                            Titulo, subtitulo e CTA agora sao blocos. Use
                            <span class="font-semibold text-white">Texto</span>
                            e
                            <span class="font-semibold text-white">Botao</span>
                            para montar a etapa no preview.
                        </div>

                        <div class="mt-3 p-0.5">
                            <p class="mb-2 text-xs text-[#8daee7]">Cabeçalho</p>
                            <label
                                class="mb-1.5 flex items-center justify-between text-base text-[#dbe8ff]"
                            >
                                <span>Mostrar logo</span>
                                <input
                                    v-model="currentStage.header.show_logo"
                                    :disabled="!props.permissions.canEdit"
                                    type="checkbox"
                                    class="h-4 w-4 accent-[#4b89ff] disabled:opacity-60"
                                />
                            </label>
                            <label
                                class="mb-1.5 flex items-center justify-between text-base text-[#dbe8ff]"
                            >
                                <span>Mostrar progresso</span>
                                <input
                                    v-model="currentStage.header.show_progress"
                                    :disabled="!props.permissions.canEdit"
                                    type="checkbox"
                                    class="h-4 w-4 accent-[#4b89ff] disabled:opacity-60"
                                />
                            </label>
                            <label
                                class="flex items-center justify-between text-base text-[#dbe8ff]"
                            >
                                <span>Permitir voltar</span>
                                <input
                                    v-model="currentStage.header.allow_back"
                                    :disabled="!props.permissions.canEdit"
                                    type="checkbox"
                                    class="h-4 w-4 accent-[#4b89ff] disabled:opacity-60"
                                />
                            </label>
                        </div>

                        <p
                            v-if="saveForm.errors.name"
                            class="mt-2 text-xs text-rose-300"
                        >
                            {{ saveForm.errors.name }}
                        </p>
                        <p
                            v-if="saveForm.errors.stages"
                            class="mt-1 text-xs text-rose-300"
                        >
                            {{ saveForm.errors.stages }}
                        </p>
                    </section>

                    <section v-if="selectedBlock" class="px-1 pb-2">
                        <template v-if="componentPanelTab === 'component'">
                            <template
                                v-if="
                                    selectedBlock.type !== 'attention' &&
                                    selectedBlock.type !== 'notification' &&
                                    selectedBlock.type !== 'timer' &&
                                    selectedBlock.type !== 'loading' &&
                                    selectedBlock.type !== 'level' &&
                                    selectedBlock.type !== 'testimonials' &&
                                    selectedBlock.type !== 'faq' &&
                                    selectedBlock.type !== 'price' &&
                                    selectedBlock.type !== 'carousel'
                                "
                            >
                                <label class="text-xs text-[#8daee7]"
                                    >ID/Name</label
                                >
                                <input
                                    :value="selectedBlock.id"
                                    disabled
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-[#c9ddff] outline-none disabled:opacity-80"
                                />
                            </template>

                            <template
                                v-if="
                                    selectedBlock.type !== 'button' &&
                                    selectedBlock.type !== 'height' &&
                                    selectedBlock.type !== 'weight' &&
                                    selectedBlock.type !== 'arguments' &&
                                    selectedBlock.type !== 'testimonials' &&
                                    selectedBlock.type !== 'faq' &&
                                    selectedBlock.type !== 'price' &&
                                    selectedBlock.type !== 'carousel' &&
                                    !isOptionsComponentType(
                                        selectedBlock.type,
                                    ) &&
                                    selectedBlock.type !== 'content_text' &&
                                    selectedBlock.type !== 'image' &&
                                    selectedBlock.type !== 'video' &&
                                    selectedBlock.type !== 'audio' &&
                                    selectedBlock.type !== 'attention' &&
                                    selectedBlock.type !== 'notification' &&
                                    selectedBlock.type !== 'timer' &&
                                    selectedBlock.type !== 'loading' &&
                                    selectedBlock.type !== 'level'
                                "
                            >
                                <label class="mt-2 block text-xs text-[#8daee7]"
                                    >Titulo</label
                                >
                                <input
                                    data-testid="block-label-input"
                                    v-model="selectedBlock.label"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                />
                            </template>

                            <label
                                v-if="supportsRequired(selectedBlock.type)"
                                class="mt-2 flex items-center justify-between text-xs text-[#dbe8ff]"
                            >
                                <span>Campo obrigatorio</span>
                                <input
                                    v-model="selectedBlock.required"
                                    :disabled="!props.permissions.canEdit"
                                    type="checkbox"
                                    class="h-4 w-4 accent-[#4b89ff] disabled:opacity-60"
                                />
                            </label>

                            <template
                                v-if="
                                    selectedBlock.type !== 'height' &&
                                    selectedBlock.type !== 'weight' &&
                                    selectedBlock.type !== 'arguments' &&
                                    selectedBlock.type !== 'testimonials' &&
                                    selectedBlock.type !== 'faq' &&
                                    selectedBlock.type !== 'price' &&
                                    selectedBlock.type !== 'carousel' &&
                                    !isOptionsComponentType(
                                        selectedBlock.type,
                                    ) &&
                                    selectedBlock.type !== 'content_text' &&
                                    selectedBlock.type !== 'image' &&
                                    selectedBlock.type !== 'video' &&
                                    selectedBlock.type !== 'audio' &&
                                    selectedBlock.type !== 'attention' &&
                                    selectedBlock.type !== 'notification' &&
                                    selectedBlock.type !== 'timer' &&
                                    selectedBlock.type !== 'loading' &&
                                    selectedBlock.type !== 'level'
                                "
                            >
                                <label class="mt-2 block text-xs text-[#8daee7]"
                                    >Tipo</label
                                >
                                <select
                                    :value="selectedBlock.type"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                    @change="
                                        changeBlockType(
                                            selectedBlock,
                                            ($event.target as HTMLSelectElement)
                                                .value as StageBlockType,
                                        )
                                    "
                                >
                                    <option
                                        v-for="item in blockCatalog"
                                        :key="item.type"
                                        :value="item.type"
                                    >
                                        {{ item.label }}
                                    </option>
                                </select>
                            </template>

                            <template v-if="selectedBlock.type === 'attention'">
                                <label class="block text-xs text-[#8daee7]"
                                    >Descricao</label
                                >
                                <input
                                    data-testid="builder-video-url-input"
                                    v-model="selectedBlock.placeholder"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-2 text-xs text-white outline-none disabled:opacity-60"
                                />
                            </template>

                            <template
                                v-if="selectedBlock.type === 'notification'"
                            >
                                <label class="block text-xs text-[#8daee7]"
                                    >Titulo</label
                                >
                                <input
                                    v-model="selectedBlock.notification_title"
                                    :disabled="!props.permissions.canEdit"
                                    placeholder="@1 acabou de comprar"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-2 text-xs text-white outline-none disabled:opacity-60"
                                />
                                <div class="mt-1 flex flex-wrap gap-1.5">
                                    <button
                                        type="button"
                                        data-testid="notification-token-title-1"
                                        :disabled="!props.permissions.canEdit"
                                        class="rounded-full border border-[#365e99] bg-[#0c254f] px-2 py-1 text-[11px] text-[#d9e7ff] hover:bg-[#153668] disabled:opacity-60"
                                        @click="
                                            insertNotificationToken(
                                                'title',
                                                '@1',
                                            )
                                        "
                                    >
                                        @1
                                    </button>
                                    <button
                                        type="button"
                                        data-testid="notification-token-title-2"
                                        :disabled="!props.permissions.canEdit"
                                        class="rounded-full border border-[#365e99] bg-[#0c254f] px-2 py-1 text-[11px] text-[#d9e7ff] hover:bg-[#153668] disabled:opacity-60"
                                        @click="
                                            insertNotificationToken(
                                                'title',
                                                '@2',
                                            )
                                        "
                                    >
                                        @2
                                    </button>
                                    <button
                                        type="button"
                                        data-testid="notification-token-title-3"
                                        :disabled="!props.permissions.canEdit"
                                        class="rounded-full border border-[#365e99] bg-[#0c254f] px-2 py-1 text-[11px] text-[#d9e7ff] hover:bg-[#153668] disabled:opacity-60"
                                        @click="
                                            insertNotificationToken(
                                                'title',
                                                '@3',
                                            )
                                        "
                                    >
                                        @3
                                    </button>
                                </div>
                                <p
                                    class="mt-1 text-[11px] leading-relaxed text-[#9bb9ef]"
                                >
                                    O usuario final nao ve a lista de variacoes.
                                    Ele ve apenas o titulo final com os tokens
                                    substituidos.
                                </p>

                                <label class="mt-2 block text-xs text-[#8daee7]"
                                    >Descricao</label
                                >
                                <textarea
                                    v-model="
                                        selectedBlock.notification_description
                                    "
                                    :disabled="!props.permissions.canEdit"
                                    rows="2"
                                    placeholder="Comprou pelo @2. Restam @3 vagas."
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-2 text-xs text-white outline-none disabled:opacity-60"
                                />
                                <div class="mt-1 flex flex-wrap gap-1.5">
                                    <button
                                        type="button"
                                        data-testid="notification-token-description-1"
                                        :disabled="!props.permissions.canEdit"
                                        class="rounded-full border border-[#365e99] bg-[#0c254f] px-2 py-1 text-[11px] text-[#d9e7ff] hover:bg-[#153668] disabled:opacity-60"
                                        @click="
                                            insertNotificationToken(
                                                'description',
                                                '@1',
                                            )
                                        "
                                    >
                                        @1
                                    </button>
                                    <button
                                        type="button"
                                        data-testid="notification-token-description-2"
                                        :disabled="!props.permissions.canEdit"
                                        class="rounded-full border border-[#365e99] bg-[#0c254f] px-2 py-1 text-[11px] text-[#d9e7ff] hover:bg-[#153668] disabled:opacity-60"
                                        @click="
                                            insertNotificationToken(
                                                'description',
                                                '@2',
                                            )
                                        "
                                    >
                                        @2
                                    </button>
                                    <button
                                        type="button"
                                        data-testid="notification-token-description-3"
                                        :disabled="!props.permissions.canEdit"
                                        class="rounded-full border border-[#365e99] bg-[#0c254f] px-2 py-1 text-[11px] text-[#d9e7ff] hover:bg-[#153668] disabled:opacity-60"
                                        @click="
                                            insertNotificationToken(
                                                'description',
                                                '@3',
                                            )
                                        "
                                    >
                                        @3
                                    </button>
                                </div>

                                <label class="mt-2 block text-xs text-[#8daee7]"
                                    >Avatar (URL opcional)</label
                                >
                                <input
                                    v-model="
                                        selectedBlock.notification_avatar_url
                                    "
                                    :disabled="!props.permissions.canEdit"
                                    placeholder="https://imagem... ou @4"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-2 text-xs text-white outline-none disabled:opacity-60"
                                />
                                <div class="mt-1 flex flex-wrap gap-1.5">
                                    <button
                                        type="button"
                                        data-testid="notification-token-avatar-4"
                                        :disabled="!props.permissions.canEdit"
                                        class="rounded-full border border-[#365e99] bg-[#0c254f] px-2 py-1 text-[11px] text-[#d9e7ff] hover:bg-[#153668] disabled:opacity-60"
                                        @click="
                                            insertNotificationAvatarToken('@4')
                                        "
                                    >
                                        @4
                                    </button>
                                </div>
                                <p
                                    class="mt-1 text-[11px] leading-relaxed text-[#9bb9ef]"
                                >
                                    O avatar tambem aceita tokens. Use
                                    <span class="font-semibold text-white"
                                        >@4</span
                                    >
                                    para trocar a imagem por variacao. O uso de
                                    <span class="font-semibold text-white"
                                        >@3</span
                                    >
                                    continua funcionando em configuracoes
                                    antigas.
                                </p>

                                <div class="mt-2 grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="text-xs text-[#8daee7]"
                                            >Duracao (segundos)</label
                                        >
                                        <input
                                            v-model.number="
                                                selectedBlock.notification_duration_seconds
                                            "
                                            :disabled="
                                                !props.permissions.canEdit
                                            "
                                            type="number"
                                            min="1"
                                            class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                        />
                                    </div>
                                    <div>
                                        <label class="text-xs text-[#8daee7]"
                                            >Intervalo (segundos)</label
                                        >
                                        <input
                                            v-model.number="
                                                selectedBlock.notification_interval_seconds
                                            "
                                            :disabled="
                                                !props.permissions.canEdit
                                            "
                                            type="number"
                                            min="1"
                                            class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                        />
                                    </div>
                                </div>

                                <p
                                    class="mt-3 text-[11px] tracking-wide text-[#87a8de] uppercase"
                                >
                                    Variacoes
                                </p>
                                <p
                                    class="mt-1 text-[11px] leading-relaxed text-[#9bb9ef]"
                                >
                                    Cada variacao preenche os tokens
                                    <span class="font-semibold text-white"
                                        >@1</span
                                    >,
                                    <span class="font-semibold text-white"
                                        >@2</span
                                    >,
                                    <span class="font-semibold text-white"
                                        >@3</span
                                    >
                                    e
                                    <span class="font-semibold text-white"
                                        >@4</span
                                    >. Recomendado:
                                    <span class="font-semibold text-white"
                                        >@1</span
                                    >
                                    nome,
                                    <span class="font-semibold text-white"
                                        >@2</span
                                    >
                                    origem,
                                    <span class="font-semibold text-white"
                                        >@3</span
                                    >
                                    contador/texto extra e
                                    <span class="font-semibold text-white"
                                        >@4</span
                                    >
                                    avatar. A notificacao alterna entre elas ao
                                    longo do tempo.
                                </p>
                                <div
                                    v-if="
                                        notificationHasFilledVariations(
                                            selectedBlock,
                                        ) &&
                                        !notificationUsesVariationTokens(
                                            selectedBlock,
                                        )
                                    "
                                    data-testid="notification-variation-warning"
                                    class="mt-2 rounded-xl border border-amber-300/35 bg-amber-500/10 px-3 py-2 text-[11px] leading-relaxed text-amber-100"
                                >
                                    As variacoes estao preenchidas, mas nao vao
                                    aparecer na tela enquanto titulo, descricao
                                    ou avatar nao usarem os tokens configurados.
                                </div>
                                <div class="mt-1.5 space-y-1.5">
                                    <div
                                        v-for="variation in selectedBlock.notification_variations ??
                                        []"
                                        :key="variation.id"
                                        class="flex items-center gap-1.5 rounded border border-[#2f568f] bg-[#0b234d] p-1.5"
                                        :class="
                                            dragOverNotificationVariationId ===
                                            variation.id
                                                ? 'ring-1 ring-[#6aa3ff]'
                                                : ''
                                        "
                                        :draggable="props.permissions.canEdit"
                                        @dragstart="
                                            onNotificationVariationDragStart(
                                                variation.id,
                                            )
                                        "
                                        @dragover.prevent="
                                            onNotificationVariationDragOver(
                                                variation.id,
                                            )
                                        "
                                        @drop.prevent="
                                            onNotificationVariationDrop(
                                                selectedBlock,
                                                variation.id,
                                            )
                                        "
                                        @dragend="
                                            onNotificationVariationDragEnd
                                        "
                                    >
                                        <span
                                            class="cursor-grab px-1 text-[#9bb9ef]"
                                            >=</span
                                        >
                                        <input
                                            v-model="variation.value1"
                                            :disabled="
                                                !props.permissions.canEdit
                                            "
                                            placeholder="@1"
                                            class="min-w-0 flex-1 rounded border border-[#385f98] bg-[#081a3a] px-2 py-1 text-xs text-white outline-none disabled:opacity-60"
                                        />
                                        <input
                                            v-model="variation.value2"
                                            :disabled="
                                                !props.permissions.canEdit
                                            "
                                            placeholder="@2"
                                            class="min-w-0 flex-1 rounded border border-[#385f98] bg-[#081a3a] px-2 py-1 text-xs text-white outline-none disabled:opacity-60"
                                        />
                                        <input
                                            v-model="variation.value3"
                                            :disabled="
                                                !props.permissions.canEdit
                                            "
                                            placeholder="@3"
                                            class="w-16 rounded border border-[#385f98] bg-[#081a3a] px-2 py-1 text-xs text-white outline-none disabled:opacity-60"
                                        />
                                        <input
                                            v-model="variation.value4"
                                            :disabled="
                                                !props.permissions.canEdit
                                            "
                                            placeholder="@4"
                                            class="min-w-0 flex-1 rounded border border-[#385f98] bg-[#081a3a] px-2 py-1 text-xs text-white outline-none disabled:opacity-60"
                                        />
                                        <button
                                            type="button"
                                            :disabled="
                                                !props.permissions.canEdit
                                            "
                                            class="rounded p-1 text-rose-200 hover:bg-rose-500/20 disabled:opacity-40"
                                            @click="
                                                removeNotificationVariation(
                                                    selectedBlock,
                                                    variation.id,
                                                )
                                            "
                                        >
                                            <Trash2 class="size-3.5" />
                                        </button>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-2 w-full rounded border border-[#2f568f] bg-[#0b234d] py-2 text-sm text-[#dbe8ff] hover:bg-[#123367] disabled:opacity-60"
                                    @click="
                                        addNotificationVariation(selectedBlock)
                                    "
                                >
                                    + Adicionar
                                </button>
                            </template>

                            <template v-if="selectedBlock.type === 'timer'">
                                <label class="block text-xs text-[#8daee7]"
                                    >Tempo (seg.)</label
                                >
                                <input
                                    v-model.number="selectedBlock.timer_seconds"
                                    :disabled="!props.permissions.canEdit"
                                    type="number"
                                    min="1"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-2 text-center text-base text-white outline-none disabled:opacity-60"
                                />

                                <p
                                    class="mt-2 rounded border border-[#2f568f] bg-[#0b234d] px-2 py-2 text-xs text-[#9bb9ef]"
                                >
                                    Utilize
                                    <span class="font-semibold text-[#cfe1ff]"
                                        >[time]</span
                                    >
                                    no texto abaixo para posicionar a contagem
                                </p>

                                <label class="mt-2 block text-xs text-[#8daee7]"
                                    >Texto</label
                                >
                                <input
                                    v-model="selectedBlock.timer_text"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-2 text-center text-white outline-none disabled:opacity-60"
                                />
                            </template>

                            <template v-if="selectedBlock.type === 'loading'">
                                <label class="text-xs text-[#8daee7]"
                                    >ID/Name</label
                                >
                                <input
                                    :value="selectedBlock.id"
                                    disabled
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-[#c9ddff] outline-none disabled:opacity-80"
                                />

                                <label class="mt-2 block text-xs text-[#8daee7]"
                                    >Titulo</label
                                >
                                <input
                                    v-model="selectedBlock.label"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                />

                                <div class="mt-2 grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="text-xs text-[#8daee7]"
                                            >Comecar em</label
                                        >
                                        <input
                                            v-model.number="
                                                selectedBlock.loading_start_seconds
                                            "
                                            :disabled="
                                                !props.permissions.canEdit
                                            "
                                            type="number"
                                            min="0"
                                            class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                        />
                                    </div>
                                    <div>
                                        <label class="text-xs text-[#8daee7]"
                                            >Durar</label
                                        >
                                        <input
                                            v-model.number="
                                                selectedBlock.loading_duration_seconds
                                            "
                                            :disabled="
                                                !props.permissions.canEdit
                                            "
                                            type="number"
                                            min="1"
                                            class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                        />
                                    </div>
                                </div>

                                <label class="mt-2 block text-xs text-[#8daee7]"
                                    >Tipo de navegacao</label
                                >
                                <select
                                    v-model="
                                        selectedBlock.loading_navigation_action
                                    "
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                    @change="
                                        onLoadingNavigationChange(selectedBlock)
                                    "
                                >
                                    <option value="none">
                                        Nao redirecionar
                                    </option>
                                    <option value="next_stage">
                                        Navegar entre etapas
                                    </option>
                                    <option value="open_link">
                                        Redirecionar
                                    </option>
                                </select>

                                <template
                                    v-if="
                                        selectedBlock.loading_navigation_action ===
                                        'next_stage'
                                    "
                                >
                                    <label
                                        class="mt-2 block text-xs text-[#8daee7]"
                                        >Destino do redirecionamento</label
                                    >
                                    <select
                                        v-model="
                                            selectedBlock.loading_target_stage_order
                                        "
                                        :disabled="!props.permissions.canEdit"
                                        class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                    >
                                        <option
                                            v-for="option in stageDestinationOptions"
                                            :key="option.value"
                                            :value="option.value"
                                        >
                                            {{ option.label }}
                                        </option>
                                    </select>
                                </template>
                                <template
                                    v-else-if="
                                        selectedBlock.loading_navigation_action ===
                                        'open_link'
                                    "
                                >
                                    <label
                                        class="mt-2 block text-xs text-[#8daee7]"
                                        >Destino do redirecionamento</label
                                    >
                                    <input
                                        v-model="selectedBlock.loading_link"
                                        :disabled="!props.permissions.canEdit"
                                        placeholder="https://..."
                                        class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                    />
                                </template>

                                <label class="mt-2 block text-xs text-[#8daee7]"
                                    >Descricao</label
                                >
                                <input
                                    v-model="selectedBlock.placeholder"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                />

                                <div
                                    class="mt-2 rounded border border-[#2f568f] bg-[#0b234d] p-2"
                                >
                                    <p class="mb-1 text-xs text-[#8daee7]">
                                        Opcoes
                                    </p>
                                    <label
                                        class="mb-1 flex items-center justify-between text-xs text-[#dbe8ff]"
                                    >
                                        <span>Mostrar titulo</span>
                                        <input
                                            v-model="
                                                selectedBlock.loading_show_title
                                            "
                                            :disabled="
                                                !props.permissions.canEdit
                                            "
                                            type="checkbox"
                                            class="h-4 w-4 accent-[#4b89ff] disabled:opacity-60"
                                        />
                                    </label>
                                    <label
                                        class="flex items-center justify-between text-xs text-[#dbe8ff]"
                                    >
                                        <span>Mostrar progresso</span>
                                        <input
                                            v-model="
                                                selectedBlock.loading_show_progress
                                            "
                                            :disabled="
                                                !props.permissions.canEdit
                                            "
                                            type="checkbox"
                                            class="h-4 w-4 accent-[#4b89ff] disabled:opacity-60"
                                        />
                                    </label>
                                </div>
                            </template>

                            <template v-if="selectedBlock.type === 'level'">
                                <label class="block text-xs text-[#8daee7]"
                                    >Titulo</label
                                >
                                <input
                                    v-model="selectedBlock.level_title"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                />

                                <label class="mt-2 block text-xs text-[#8daee7]"
                                    >Subtitulo</label
                                >
                                <input
                                    v-model="selectedBlock.level_subtitle"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                />

                                <label class="mt-2 block text-xs text-[#8daee7]"
                                    >Porcentagem</label
                                >
                                <input
                                    v-model.number="
                                        selectedBlock.level_percentage
                                    "
                                    :disabled="!props.permissions.canEdit"
                                    type="number"
                                    min="0"
                                    max="100"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                />

                                <label class="mt-2 block text-xs text-[#8daee7]"
                                    >Texto do indicador</label
                                >
                                <input
                                    v-model="selectedBlock.level_indicator_text"
                                    :disabled="!props.permissions.canEdit"
                                    placeholder="Ex: Voce esta aqui"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                />

                                <label class="mt-2 block text-xs text-[#8daee7]"
                                    >Legendas (separe por virgula)</label
                                >
                                <input
                                    v-model="selectedBlock.level_legends"
                                    :disabled="!props.permissions.canEdit"
                                    placeholder="Ex: Normal, Medio, Muito"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                />

                                <div
                                    class="mt-2 rounded border border-[#2f568f] bg-[#0b234d] p-2"
                                >
                                    <label
                                        class="mb-1 flex items-center gap-2 text-xs text-[#dbe8ff]"
                                    >
                                        <input
                                            v-model="
                                                selectedBlock.level_show_meter
                                            "
                                            :disabled="
                                                !props.permissions.canEdit
                                            "
                                            type="checkbox"
                                            class="h-4 w-4 accent-[#4b89ff] disabled:opacity-60"
                                        />
                                        <span>Mostrar Medidor?</span>
                                    </label>
                                    <label
                                        class="flex items-center gap-2 text-xs text-[#dbe8ff]"
                                    >
                                        <input
                                            v-model="
                                                selectedBlock.level_show_progress
                                            "
                                            :disabled="
                                                !props.permissions.canEdit
                                            "
                                            type="checkbox"
                                            class="h-4 w-4 accent-[#4b89ff] disabled:opacity-60"
                                        />
                                        <span>Mostrar progresso?</span>
                                    </label>
                                </div>
                            </template>

                            <template v-if="selectedBlock.type === 'button'">
                                <label class="mt-2 block text-xs text-[#8daee7]"
                                    >Texto do botao</label
                                >
                                <input
                                    v-model="selectedBlock.label"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                />

                                <label class="mt-2 block text-xs text-[#8daee7]"
                                    >Tipo de navegacao</label
                                >
                                <select
                                    v-model="selectedBlock.button_action"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                    @change="
                                        onButtonActionChange(selectedBlock)
                                    "
                                >
                                    <option value="next_stage">
                                        Navegar entre etapas
                                    </option>
                                    <option value="open_link">
                                        Redirecionar
                                    </option>
                                </select>

                                <template
                                    v-if="
                                        selectedBlock.button_action ===
                                        'next_stage'
                                    "
                                >
                                    <label
                                        class="mt-2 block text-xs text-[#8daee7]"
                                        >Destino do redirecionamento</label
                                    >
                                    <select
                                        v-model="
                                            selectedBlock.button_target_stage_order
                                        "
                                        :disabled="!props.permissions.canEdit"
                                        class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                    >
                                        <option
                                            v-for="option in stageDestinationOptions"
                                            :key="option.value"
                                            :value="option.value"
                                        >
                                            {{ option.label }}
                                        </option>
                                    </select>
                                </template>
                                <template v-else>
                                    <label
                                        class="mt-2 block text-xs text-[#8daee7]"
                                        >Destino do redirecionamento</label
                                    >
                                    <input
                                        v-model="selectedBlock.button_link"
                                        :disabled="!props.permissions.canEdit"
                                        placeholder="https://..."
                                        class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                    />

                                    <label
                                        class="mt-2 flex items-center justify-between text-xs text-[#dbe8ff]"
                                    >
                                        <span>Abrir em nova aba</span>
                                        <input
                                            v-model="
                                                selectedBlock.button_open_new_tab
                                            "
                                            :disabled="
                                                !props.permissions.canEdit
                                            "
                                            type="checkbox"
                                            class="h-4 w-4 accent-[#4b89ff] disabled:opacity-60"
                                        />
                                    </label>
                                </template>
                            </template>

                            <template v-if="selectedBlock.type === 'spacer'">
                                <label class="mt-2 block text-xs text-[#8daee7]"
                                    >Altura do espaco (px)</label
                                >
                                <input
                                    v-model="selectedBlock.placeholder"
                                    :disabled="!props.permissions.canEdit"
                                    type="number"
                                    min="8"
                                    max="120"
                                    placeholder="28"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                />
                            </template>

                            <template
                                v-if="
                                    supportsPlaceholder(selectedBlock.type) &&
                                    selectedBlock.type !== 'content_text' &&
                                    selectedBlock.type !== 'image' &&
                                    !(
                                        selectedBlock.type === 'height' &&
                                        (selectedBlock.height_mode ??
                                            'ruler') === 'ruler'
                                    ) &&
                                    !(
                                        selectedBlock.type === 'weight' &&
                                        (selectedBlock.weight_mode ??
                                            'ruler') === 'ruler'
                                    )
                                "
                            >
                                <label
                                    class="mt-2 block text-xs text-[#8daee7]"
                                    >{{
                                        placeholderLabel(selectedBlock.type)
                                    }}</label
                                >
                                <input
                                    v-model="selectedBlock.placeholder"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                />
                            </template>

                            <template
                                v-if="selectedBlock.type === 'content_text'"
                            >
                                <div
                                    class="mt-1 rounded border border-[#2f568f] bg-[#0b234d]"
                                    @click.stop
                                >
                                    <div
                                        class="flex flex-wrap items-center gap-1 border-b border-[#2f568f] bg-[#0d2a57] p-2"
                                    >
                                        <select
                                            class="h-8 min-w-[116px] rounded border border-[#2f568f] bg-[#081a3a] px-2 text-[11px] text-white outline-none"
                                            @change="
                                                applyContentTextHeading(
                                                    (
                                                        $event.target as HTMLSelectElement
                                                    ).value as
                                                        | 'h1'
                                                        | 'h2'
                                                        | 'h3'
                                                        | 'p',
                                                )
                                            "
                                        >
                                            <option value="h1">
                                                Heading 1
                                            </option>
                                            <option value="h2" selected>
                                                Heading 2
                                            </option>
                                            <option value="h3">
                                                Heading 3
                                            </option>
                                            <option value="p">Paragrafo</option>
                                        </select>
                                        <select
                                            class="h-8 min-w-[96px] rounded border border-[#2f568f] bg-[#081a3a] px-2 text-[11px] text-white outline-none"
                                            @change="
                                                applyContentTextEditorValueCommand(
                                                    'fontName',
                                                    (
                                                        $event.target as HTMLSelectElement
                                                    ).value,
                                                )
                                            "
                                        >
                                            <option value="inherit" selected>
                                                Instrument Sans
                                            </option>
                                            <option value="Sora">Sora</option>
                                            <option value="'Playfair Display'">
                                                Playfair
                                            </option>
                                            <option value="'IBM Plex Mono'">
                                                IBM Plex Mono
                                            </option>
                                            <option value="serif">Serif</option>
                                            <option value="monospace">
                                                Mono
                                            </option>
                                        </select>
                                        <div
                                            class="ml-auto flex items-center gap-0.5"
                                        >
                                            <button
                                                type="button"
                                                class="rounded px-1.5 py-1 text-sm font-semibold text-[#dbe8ff] hover:bg-[#1a4a99]"
                                                @click="
                                                    applyContentTextEditorCommand(
                                                        'bold',
                                                    )
                                                "
                                            >
                                                B
                                            </button>
                                            <button
                                                type="button"
                                                class="rounded px-1.5 py-1 text-sm text-[#dbe8ff] italic hover:bg-[#1a4a99]"
                                                @click="
                                                    applyContentTextEditorCommand(
                                                        'italic',
                                                    )
                                                "
                                            >
                                                I
                                            </button>
                                            <button
                                                type="button"
                                                class="rounded px-1.5 py-1 text-sm text-[#dbe8ff] underline hover:bg-[#1a4a99]"
                                                @click="
                                                    applyContentTextEditorCommand(
                                                        'underline',
                                                    )
                                                "
                                            >
                                                U
                                            </button>
                                            <button
                                                type="button"
                                                class="rounded px-1.5 py-1 text-sm text-[#dbe8ff] line-through hover:bg-[#1a4a99]"
                                                @click="
                                                    applyContentTextEditorCommand(
                                                        'strikeThrough',
                                                    )
                                                "
                                            >
                                                S
                                            </button>
                                            <button
                                                type="button"
                                                class="rounded px-1.5 py-1 text-[11px] text-[#dbe8ff] hover:bg-[#1a4a99]"
                                                @click="insertContentTextLink"
                                            >
                                                Link
                                            </button>
                                            <button
                                                type="button"
                                                class="rounded px-1.5 py-1 text-[11px] text-[#dbe8ff] hover:bg-[#1a4a99]"
                                                @click="
                                                    applyContentTextEditorCommand(
                                                        'insertUnorderedList',
                                                    )
                                                "
                                            >
                                                Lista
                                            </button>
                                            <button
                                                type="button"
                                                class="rounded px-1.5 py-1 text-[11px] text-[#dbe8ff] hover:bg-[#1a4a99]"
                                                @click="
                                                    applyContentTextAlignment(
                                                        'left',
                                                    )
                                                "
                                            >
                                                Esq
                                            </button>
                                            <button
                                                type="button"
                                                class="rounded px-1.5 py-1 text-[11px] text-[#dbe8ff] hover:bg-[#1a4a99]"
                                                @click="
                                                    applyContentTextAlignment(
                                                        'center',
                                                    )
                                                "
                                            >
                                                Centro
                                            </button>
                                            <button
                                                type="button"
                                                class="rounded px-1.5 py-1 text-[11px] text-[#dbe8ff] hover:bg-[#1a4a99]"
                                                @click="
                                                    applyContentTextAlignment(
                                                        'right',
                                                    )
                                                "
                                            >
                                                Dir
                                            </button>
                                        </div>
                                    </div>
                                    <div class="p-2">
                                        <div
                                            :ref="bindContentTextEditorElement"
                                            data-testid="content-text-editor"
                                            class="min-h-[220px] cursor-text rounded-lg border border-[#23497f] bg-[#0c2853] px-4 py-4 text-[#dce8ff] outline-none empty:before:pointer-events-none empty:before:text-[#6f8fbf] empty:before:content-['Escreva_o_conteudo_aqui...'] [&_a]:text-[#9fc2ff] [&_a]:underline [&_h1]:text-3xl [&_h1]:leading-tight [&_h1]:font-bold [&_h2]:text-2xl [&_h2]:leading-tight [&_h2]:font-bold [&_h3]:text-xl [&_h3]:leading-tight [&_h3]:font-semibold [&_li]:text-left [&_p]:mt-2 [&_p]:text-sm [&_p]:leading-relaxed [&_p]:text-[#9cc1ff] [&_ul]:mt-2 [&_ul]:list-disc [&_ul]:space-y-1 [&_ul]:pl-5"
                                            contenteditable="true"
                                            spellcheck="false"
                                            @focus="
                                                activateContentTextEditor(
                                                    $event,
                                                )
                                            "
                                            @paste="handleContentTextPaste"
                                            @input="
                                                syncContentTextMarkupFromEditor
                                            "
                                            @blur="
                                                syncContentTextMarkupFromEditor
                                            "
                                        ></div>
                                    </div>
                                </div>
                            </template>

                            <template v-if="selectedBlock.type === 'image'">
                                <label class="mt-2 block text-xs text-[#8daee7]"
                                    >Imagem</label
                                >
                                <div
                                    class="mt-1 rounded border border-[#2f568f] bg-[#0b234d] p-1.5"
                                >
                                    <div
                                        class="grid grid-cols-2 gap-1 rounded bg-[#091f43] p-1"
                                    >
                                        <button
                                            type="button"
                                            class="rounded py-1.5 text-sm"
                                            :class="
                                                imageComponentTab === 'image'
                                                    ? 'bg-[#e8f0ff] text-[#0a2146]'
                                                    : 'text-[#b7cff5]'
                                            "
                                            @click="
                                                setImageComponentTab('image')
                                            "
                                        >
                                            Imagem
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded py-1.5 text-sm"
                                            :class="
                                                imageComponentTab === 'url'
                                                    ? 'bg-[#e8f0ff] text-[#0a2146]'
                                                    : 'text-[#b7cff5]'
                                            "
                                            @click="setImageComponentTab('url')"
                                        >
                                            URL
                                        </button>
                                    </div>

                                    <div
                                        class="mt-1.5 rounded border border-[#2f568f] bg-[#091f43] p-2"
                                    >
                                        <template
                                            v-if="imageComponentTab === 'image'"
                                        >
                                            <button
                                                type="button"
                                                class="w-full rounded border border-[#2f568f] bg-[#0b234d] py-4 text-sm text-[#dbe8ff] hover:bg-[#123367]"
                                                :disabled="
                                                    !props.permissions
                                                        .canEdit ||
                                                    uploadingImageBlockId !==
                                                        null
                                                "
                                                @click="triggerImageFilePicker"
                                            >
                                                {{
                                                    uploadingImageBlockId ===
                                                    selectedBlock.id
                                                        ? 'Carregando imagem...'
                                                        : 'Selecionar imagem'
                                                }}
                                            </button>
                                            <input
                                                ref="imagePickerInput"
                                                data-testid="builder-image-file-input"
                                                type="file"
                                                accept="image/*"
                                                class="hidden"
                                                @change="handleImageFileChange"
                                            />
                                            <img
                                                v-if="
                                                    sanitizeStoredAssetUrl(
                                                        selectedBlock.placeholder,
                                                    )
                                                "
                                                :src="
                                                    sanitizeStoredAssetUrl(
                                                        selectedBlock.placeholder,
                                                    ) ?? undefined
                                                "
                                                alt="Preview da imagem"
                                                class="mt-2 h-28 w-full rounded border border-[#2f568f] object-cover"
                                            />
                                            <p
                                                v-if="
                                                    uploadingImageBlockId ===
                                                    selectedBlock.id
                                                "
                                                data-testid="builder-image-uploading-status"
                                                role="status"
                                                class="mt-2 animate-pulse text-center text-xs font-medium text-[#b9d2ff]"
                                            >
                                                Carregando imagem...
                                            </p>
                                        </template>
                                        <template v-else>
                                            <input
                                                v-model="
                                                    selectedBlock.placeholder
                                                "
                                                :disabled="
                                                    !props.permissions.canEdit
                                                "
                                                placeholder="https://..."
                                                class="w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-2 text-xs text-white outline-none disabled:opacity-60"
                                            />
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <template v-if="selectedBlock.type === 'video'">
                                <label class="mt-2 block text-xs text-[#8daee7]"
                                    >URL de embed</label
                                >
                                <input
                                    v-model="selectedBlock.placeholder"
                                    :disabled="!props.permissions.canEdit"
                                    placeholder="https://www.youtube.com/watch?v=..."
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                />
                            </template>

                            <template v-if="selectedBlock.type === 'audio'">
                                <label class="mt-2 block text-xs text-[#8daee7]"
                                    >Enviado por</label
                                >
                                <input
                                    v-model="selectedBlock.audio_sender"
                                    :disabled="!props.permissions.canEdit"
                                    placeholder="Ex: Joao Silva, Maria Ferreira"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                />

                                <button
                                    type="button"
                                    class="mt-2 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-3 text-sm text-[#dbe8ff] hover:bg-[#123367] disabled:opacity-60"
                                    :disabled="!props.permissions.canEdit"
                                    @click="triggerAudioFilePicker"
                                >
                                    Selecionar audio (.mp3)
                                </button>
                                <input
                                    ref="audioFileInput"
                                    data-testid="builder-audio-file-input"
                                    type="file"
                                    accept=".mp3,audio/mpeg,audio/mp3"
                                    class="hidden"
                                    @change="handleAudioFileChange"
                                />

                                <label class="mt-2 block text-xs text-[#8daee7]"
                                    >URL do audio (.mp3)</label
                                >
                                <input
                                    data-testid="builder-audio-src-input"
                                    v-model="selectedBlock.audio_src"
                                    :disabled="!props.permissions.canEdit"
                                    placeholder="https://..."
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                />
                            </template>

                            <template v-if="selectedBlock.type === 'phone'">
                                <label class="mt-2 block text-xs text-[#8daee7]"
                                    >Mascara</label
                                >
                                <select
                                    v-model="selectedBlock.phone_mask"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                >
                                    <option
                                        v-for="mask in phoneMaskOptions"
                                        :key="mask.value"
                                        :value="mask.value"
                                    >
                                        {{ mask.label }}
                                    </option>
                                </select>
                            </template>

                            <template v-if="selectedBlock.type === 'number'">
                                <label class="mt-2 block text-xs text-[#8daee7]"
                                    >Mascara</label
                                >
                                <select
                                    v-model="selectedBlock.number_mask"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                >
                                    <option
                                        v-for="mask in numberMaskOptions"
                                        :key="mask.value"
                                        :value="mask.value"
                                    >
                                        {{ mask.label }}
                                    </option>
                                </select>
                            </template>

                            <template v-if="selectedBlock.type === 'height'">
                                <label class="mt-2 block text-xs text-[#8daee7]"
                                    >Tipo</label
                                >
                                <select
                                    v-model="selectedBlock.height_mode"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                >
                                    <option value="ruler">Regua</option>
                                    <option value="input">Input</option>
                                </select>
                            </template>

                            <template v-if="selectedBlock.type === 'weight'">
                                <label class="mt-2 block text-xs text-[#8daee7]"
                                    >Tipo</label
                                >
                                <select
                                    v-model="selectedBlock.weight_mode"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                >
                                    <option value="ruler">Regua</option>
                                    <option value="input">Input</option>
                                </select>
                            </template>

                            <template
                                v-if="
                                    isOptionsComponentType(selectedBlock.type)
                                "
                            >
                                <label class="mt-2 block text-xs text-[#8daee7]"
                                    >Introducao</label
                                >
                                <select
                                    v-model="selectedBlock.options_intro_type"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                >
                                    <option value="text">Texto</option>
                                    <option value="none">Sem introducao</option>
                                </select>

                                <template
                                    v-if="
                                        selectedBlock.options_intro_type !==
                                        'none'
                                    "
                                >
                                    <label
                                        class="mt-2 block text-xs text-[#8daee7]"
                                        >Titulo da introducao</label
                                    >
                                    <input
                                        data-testid="options-intro-title"
                                        v-model="
                                            selectedBlock.options_intro_title
                                        "
                                        :disabled="!props.permissions.canEdit"
                                        class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                    />

                                    <label
                                        class="mt-2 block text-xs text-[#8daee7]"
                                        >Descricao da introducao</label
                                    >
                                    <textarea
                                        data-testid="options-intro-description"
                                        v-model="
                                            selectedBlock.options_intro_description
                                        "
                                        :disabled="!props.permissions.canEdit"
                                        rows="3"
                                        class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-2 text-xs text-white outline-none disabled:opacity-60"
                                    />
                                </template>

                                <div
                                    v-if="
                                        selectedBlock.options_intro_type !==
                                        'none'
                                    "
                                    class="mt-2 rounded border border-[#2f568f] bg-[#0b234d]"
                                    @click.stop
                                >
                                    <div
                                        v-if="introEditorTarget !== null"
                                        class="space-y-1 border-b border-[#2f568f] bg-[#0d2a57] p-1.5"
                                    >
                                        <div class="flex items-center gap-1">
                                            <select
                                                v-if="
                                                    introEditorTarget ===
                                                    'title'
                                                "
                                                class="h-7 min-w-[110px] rounded border border-[#2f568f] bg-[#081a3a] px-1.5 text-[11px] text-white outline-none"
                                                @change="
                                                    applyIntroHeading(
                                                        (
                                                            $event.target as HTMLSelectElement
                                                        ).value as
                                                            | 'h1'
                                                            | 'h2'
                                                            | 'h3'
                                                            | 'p',
                                                    )
                                                "
                                            >
                                                <option value="h1">
                                                    Heading 1
                                                </option>
                                                <option value="h2" selected>
                                                    Heading 2
                                                </option>
                                                <option value="h3">
                                                    Heading 3
                                                </option>
                                                <option value="p">
                                                    Paragrafo
                                                </option>
                                            </select>
                                            <select
                                                v-else
                                                class="h-7 min-w-[110px] rounded border border-[#2f568f] bg-[#081a3a] px-1.5 text-[11px] text-white outline-none"
                                                @change="
                                                    applyIntroEditorValueCommand(
                                                        'fontName',
                                                        (
                                                            $event.target as HTMLSelectElement
                                                        ).value,
                                                    )
                                                "
                                            >
                                                <option
                                                    value="inherit"
                                                    selected
                                                >
                                                    Instrument Sans
                                                </option>
                                                <option value="Sora">
                                                    Sora
                                                </option>
                                                <option
                                                    value="'Playfair Display'"
                                                >
                                                    Playfair
                                                </option>
                                                <option value="'IBM Plex Mono'">
                                                    IBM Plex Mono
                                                </option>
                                                <option value="serif">
                                                    Serif
                                                </option>
                                                <option value="monospace">
                                                    Monospace
                                                </option>
                                            </select>
                                            <select
                                                v-if="
                                                    introEditorTarget ===
                                                    'title'
                                                "
                                                class="h-7 min-w-[90px] rounded border border-[#2f568f] bg-[#081a3a] px-1.5 text-[11px] text-white outline-none"
                                                @change="
                                                    applyIntroEditorValueCommand(
                                                        'fontName',
                                                        (
                                                            $event.target as HTMLSelectElement
                                                        ).value,
                                                    )
                                                "
                                            >
                                                <option
                                                    value="inherit"
                                                    selected
                                                >
                                                    Instrument Sans
                                                </option>
                                                <option value="Sora">
                                                    Sora
                                                </option>
                                                <option
                                                    value="'Playfair Display'"
                                                >
                                                    Playfair
                                                </option>
                                                <option value="'IBM Plex Mono'">
                                                    IBM Plex Mono
                                                </option>
                                                <option value="serif">
                                                    Serif
                                                </option>
                                                <option value="monospace">
                                                    Mono
                                                </option>
                                            </select>
                                            <button
                                                type="button"
                                                class="ml-auto rounded px-1.5 py-1 text-[11px] text-[#dbe8ff] hover:bg-[#1a4a99]"
                                                @click="closeIntroInlineEditor"
                                            >
                                                Fechar
                                            </button>
                                        </div>
                                        <div class="flex items-center gap-0.5">
                                            <button
                                                type="button"
                                                class="rounded px-1.5 py-1 text-sm font-semibold text-[#dbe8ff] hover:bg-[#1a4a99]"
                                                @click="
                                                    applyIntroEditorCommand(
                                                        'bold',
                                                    )
                                                "
                                            >
                                                B
                                            </button>
                                            <button
                                                type="button"
                                                class="rounded px-1.5 py-1 text-sm text-[#dbe8ff] italic hover:bg-[#1a4a99]"
                                                @click="
                                                    applyIntroEditorCommand(
                                                        'italic',
                                                    )
                                                "
                                            >
                                                I
                                            </button>
                                            <button
                                                type="button"
                                                class="rounded px-1.5 py-1 text-sm text-[#dbe8ff] underline hover:bg-[#1a4a99]"
                                                @click="
                                                    applyIntroEditorCommand(
                                                        'underline',
                                                    )
                                                "
                                            >
                                                U
                                            </button>
                                            <button
                                                type="button"
                                                class="rounded px-1.5 py-1 text-sm text-[#dbe8ff] line-through hover:bg-[#1a4a99]"
                                                @click="
                                                    applyIntroEditorCommand(
                                                        'strikeThrough',
                                                    )
                                                "
                                            >
                                                S
                                            </button>
                                            <button
                                                type="button"
                                                class="rounded px-1.5 py-1 text-sm text-[#dbe8ff] hover:bg-[#1a4a99]"
                                                @click="insertIntroLink"
                                            >
                                                🔗
                                            </button>
                                            <button
                                                type="button"
                                                class="rounded px-1.5 py-1 text-sm text-[#dbe8ff] hover:bg-[#1a4a99]"
                                                @click="
                                                    applyIntroEditorCommand(
                                                        'insertUnorderedList',
                                                    )
                                                "
                                            >
                                                ≣
                                            </button>
                                            <button
                                                type="button"
                                                class="rounded px-1.5 py-1 text-sm text-[#dbe8ff] hover:bg-[#1a4a99]"
                                                @click="
                                                    applyIntroAlignment('left')
                                                "
                                            >
                                                ≡
                                            </button>
                                            <button
                                                type="button"
                                                class="rounded px-1.5 py-1 text-sm text-[#dbe8ff] hover:bg-[#1a4a99]"
                                                @click="
                                                    applyIntroAlignment(
                                                        'center',
                                                    )
                                                "
                                            >
                                                ☰
                                            </button>
                                            <button
                                                type="button"
                                                class="rounded px-1.5 py-1 text-sm text-[#dbe8ff] hover:bg-[#1a4a99]"
                                                @click="
                                                    applyIntroAlignment('right')
                                                "
                                            >
                                                ≡
                                            </button>
                                        </div>
                                    </div>
                                    <div class="p-2">
                                        <h4
                                            class="cursor-text text-center text-2xl leading-tight font-bold text-white outline-none"
                                            contenteditable="true"
                                            spellcheck="false"
                                            @focus="
                                                openIntroInlineEditor(
                                                    'title',
                                                    $event,
                                                )
                                            "
                                            @input="
                                                syncIntroInlineText(
                                                    $event,
                                                    'title',
                                                )
                                            "
                                            @blur="
                                                syncIntroInlineText(
                                                    $event,
                                                    'title',
                                                )
                                            "
                                        >
                                            {{
                                                selectedBlock.options_intro_title
                                            }}
                                        </h4>
                                        <p
                                            class="mt-2 cursor-text text-center text-sm text-[#cfe1ff] outline-none"
                                            contenteditable="true"
                                            spellcheck="false"
                                            @focus="
                                                openIntroInlineEditor(
                                                    'description',
                                                    $event,
                                                )
                                            "
                                            @input="
                                                syncIntroInlineText(
                                                    $event,
                                                    'description',
                                                )
                                            "
                                            @blur="
                                                syncIntroInlineText(
                                                    $event,
                                                    'description',
                                                )
                                            "
                                        >
                                            {{
                                                selectedBlock.options_intro_description
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <p
                                    class="mt-2 text-[11px] tracking-wide text-[#87a8de] uppercase"
                                >
                                    Opcoes
                                </p>
                                <div class="mt-2 space-y-2">
                                    <div
                                        v-for="(
                                            item, itemIndex
                                        ) in selectedBlock.option_items ?? []"
                                        :key="`${selectedBlock.id}-opt-item-${item.id}`"
                                        :data-testid="`option-item-${item.id}`"
                                        class="rounded border bg-[#0b234d] p-2"
                                        :class="[
                                            dragOverOptionItemId === item.id
                                                ? 'border-[#6aa3ff]'
                                                : 'border-[#2f568f]',
                                            reorderedOptionItemId === item.id
                                                ? 'bg-[#103265] ring-1 ring-[#6aa3ff] transition-colors duration-300'
                                                : '',
                                        ]"
                                        :draggable="props.permissions.canEdit"
                                        @dragstart="
                                            onOptionItemDragStart(item.id)
                                        "
                                        @dragover.prevent="
                                            onOptionItemDragOver(item.id)
                                        "
                                        @drop.prevent="
                                            onOptionItemDrop(
                                                selectedBlock,
                                                item.id,
                                            )
                                        "
                                        @dragend="onOptionItemDragEnd"
                                    >
                                        <div class="flex items-start gap-2">
                                            <button
                                                type="button"
                                                class="mt-1 w-4 cursor-grab text-[#97b7ea] hover:text-white active:cursor-grabbing"
                                                :disabled="
                                                    !props.permissions.canEdit
                                                "
                                                title="Arraste para ordenar"
                                            >
                                                ::
                                            </button>
                                            <input
                                                :data-testid="`option-item-label-${item.id}`"
                                                v-model="item.label"
                                                :disabled="
                                                    !props.permissions.canEdit
                                                "
                                                class="mt-0.5 w-full border-0 bg-transparent px-0 py-1 text-sm text-white outline-none disabled:opacity-60"
                                                @input="
                                                    selectedBlock.options = (
                                                        selectedBlock.option_items ??
                                                        []
                                                    ).map(
                                                        (entry) => entry.label,
                                                    )
                                                "
                                            />
                                            <button
                                                :data-testid="`option-item-settings-${item.id}`"
                                                @click="
                                                    toggleOptionItemSettings(
                                                        item.id,
                                                    )
                                                "
                                                :disabled="
                                                    !props.permissions.canEdit
                                                "
                                                class="mt-1 rounded p-1 text-[#8fb0e6] hover:bg-[#1a4a99] hover:text-white disabled:opacity-40"
                                            >
                                                <Settings class="size-3.5" />
                                            </button>
                                        </div>
                                        <div
                                            v-if="
                                                expandedOptionItemId === item.id
                                            "
                                            class="mt-2 border-t border-[#2f568f] pt-2"
                                        >
                                            <div
                                                class="mb-2 flex justify-start"
                                            >
                                                <button
                                                    @click="
                                                        removeBlockOption(
                                                            selectedBlock,
                                                            itemIndex,
                                                        )
                                                    "
                                                    :disabled="
                                                        !props.permissions
                                                            .canEdit ||
                                                        (selectedBlock
                                                            .option_items
                                                            ?.length ?? 0) <=
                                                            minimumOptions(
                                                                selectedBlock.type,
                                                            )
                                                    "
                                                    class="rounded p-1 text-rose-200 hover:bg-rose-500/20 disabled:opacity-40"
                                                >
                                                    <Trash2 class="size-3.5" />
                                                </button>
                                            </div>
                                            <div
                                                v-if="
                                                    selectedBlock.options_disposition !==
                                                    'text'
                                                "
                                                class="mb-2"
                                            >
                                                <label
                                                    class="mb-1 block text-[11px] text-[#8daee7]"
                                                    >Imagem (URL)</label
                                                >
                                                <input
                                                    :data-testid="`option-item-image-url-${item.id}`"
                                                    v-model="item.image_url"
                                                    :disabled="
                                                        !props.permissions
                                                            .canEdit
                                                    "
                                                    placeholder="https://..."
                                                    class="w-full rounded border border-[#385f98] bg-[#081a3a] px-2 py-1 text-xs text-white outline-none disabled:opacity-60"
                                                />
                                            </div>
                                            <div class="grid grid-cols-3 gap-2">
                                                <div>
                                                    <label
                                                        class="mb-1 block text-[11px] text-[#8daee7]"
                                                        >Pontos:</label
                                                    >
                                                    <input
                                                        v-model.number="
                                                            item.points
                                                        "
                                                        :disabled="
                                                            !props.permissions
                                                                .canEdit
                                                        "
                                                        type="number"
                                                        placeholder="1"
                                                        class="w-full rounded border border-[#385f98] bg-[#081a3a] px-2 py-1 text-xs text-white outline-none disabled:opacity-60"
                                                    />
                                                </div>
                                                <div>
                                                    <label
                                                        class="mb-1 block text-[11px] text-[#8daee7]"
                                                        >Valor:</label
                                                    >
                                                    <input
                                                        v-model="item.value"
                                                        :disabled="
                                                            !props.permissions
                                                                .canEdit
                                                        "
                                                        placeholder="A"
                                                        class="w-full rounded border border-[#385f98] bg-[#081a3a] px-2 py-1 text-xs text-white outline-none disabled:opacity-60"
                                                    />
                                                </div>
                                                <div>
                                                    <label
                                                        class="mb-1 block text-[11px] text-[#8daee7]"
                                                        >Destino:</label
                                                    >
                                                    <input
                                                        v-model="
                                                            item.destination
                                                        "
                                                        :disabled="
                                                            !props.permissions
                                                                .canEdit
                                                        "
                                                        placeholder="Proxima etapa"
                                                        class="w-full rounded border border-[#385f98] bg-[#081a3a] px-2 py-1 text-xs text-white outline-none disabled:opacity-60"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <button
                                    @click="addBlockOption(selectedBlock)"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-2 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-2 text-sm text-[#cfe1ff] disabled:opacity-40"
                                >
                                    + adicionar opcao
                                </button>

                                <div class="mt-2 space-y-2 text-[#dbe8ff]">
                                    <label class="flex items-start gap-2">
                                        <input
                                            v-model="
                                                selectedBlock.options_required_selection
                                            "
                                            :disabled="
                                                !props.permissions.canEdit
                                            "
                                            type="checkbox"
                                            class="mt-0.5 h-4 w-4 accent-[#4b89ff] disabled:opacity-60"
                                        />
                                        <span class="block">
                                            <span
                                                class="block text-sm leading-5 font-semibold text-[#dfeaff]"
                                                >Selecao obrigatoria</span
                                            >
                                            <span
                                                class="mt-0.5 block text-[13px] leading-5 text-[#98a7c2]"
                                                >O usuario e obrigado a
                                                selecionar alguma opcao para
                                                prosseguir.</span
                                            >
                                        </span>
                                    </label>
                                    <label class="flex items-start gap-2">
                                        <input
                                            v-model="
                                                selectedBlock.options_allow_multiple
                                            "
                                            :disabled="
                                                !props.permissions.canEdit ||
                                                selectedBlock.type ===
                                                    'single_choice'
                                            "
                                            type="checkbox"
                                            class="mt-0.5 h-4 w-4 accent-[#4b89ff] disabled:opacity-60"
                                        />
                                        <span class="block">
                                            <span
                                                class="block text-sm leading-5 font-semibold text-[#dfeaff]"
                                                >Permitir multipla escolha</span
                                            >
                                            <span
                                                class="mt-0.5 block text-[13px] leading-5 text-[#98a7c2]"
                                                >O usuario podera selecionar
                                                mais de uma opcao, porem, a
                                                proxima etapa tera que ser
                                                definida atraves de um
                                                componente do tipo
                                                "botao".</span
                                            >
                                        </span>
                                    </label>
                                    <label class="flex items-start gap-2">
                                        <input
                                            v-model="
                                                selectedBlock.options_disable_auto_follow
                                            "
                                            :disabled="
                                                !props.permissions.canEdit
                                            "
                                            type="checkbox"
                                            class="mt-0.5 h-4 w-4 accent-[#4b89ff] disabled:opacity-60"
                                        />
                                        <span class="block">
                                            <span
                                                class="block text-sm leading-5 font-semibold text-[#dfeaff]"
                                                >Nao seguir
                                                automaticamente</span
                                            >
                                            <span
                                                class="mt-0.5 block text-[13px] leading-5 text-[#98a7c2]"
                                                >O usuario tera que clicar em um
                                                componente do tipo "botao" para
                                                avancar. A proxima etapa
                                                configurada nas opcoes tera
                                                prioridade maior que a definida
                                                no botao.</span
                                            >
                                        </span>
                                    </label>
                                </div>
                            </template>

                            <template v-else-if="selectedBlock.type === 'faq'">
                                <p
                                    class="mt-2 text-[11px] tracking-wide text-[#87a8de] uppercase"
                                >
                                    Perguntas
                                </p>
                                <div class="mt-2 space-y-2">
                                    <div
                                        v-for="(
                                            item, itemIndex
                                        ) in selectedBlock.option_items ?? []"
                                        :key="`${selectedBlock.id}-faq-${item.id}`"
                                        class="rounded border border-[#2f568f] bg-[#0b234d] p-2"
                                    >
                                        <div
                                            class="grid grid-cols-[16px_1fr_auto] items-start gap-2"
                                        >
                                            <button
                                                type="button"
                                                class="mt-2 w-4 cursor-grab text-[#97b7ea] active:cursor-grabbing"
                                                :disabled="
                                                    !props.permissions.canEdit
                                                "
                                            >
                                                ::
                                            </button>
                                            <div>
                                                <input
                                                    :data-testid="`option-item-label-${item.id}`"
                                                    v-model="item.label"
                                                    :disabled="
                                                        !props.permissions
                                                            .canEdit
                                                    "
                                                    class="w-full rounded border border-[#385f98] bg-[#081a3a] px-2 py-1.5 text-sm text-white outline-none disabled:opacity-60"
                                                />
                                                <textarea
                                                    :data-testid="`builder-faq-answer-${item.id}`"
                                                    v-model="item.description"
                                                    :disabled="
                                                        !props.permissions
                                                            .canEdit
                                                    "
                                                    rows="3"
                                                    class="mt-2 w-full rounded border border-[#385f98] bg-[#081a3a] px-2 py-1.5 text-sm text-[#dbe8ff] outline-none disabled:opacity-60"
                                                />
                                            </div>
                                            <button
                                                @click="
                                                    removeBlockOption(
                                                        selectedBlock,
                                                        itemIndex,
                                                    )
                                                "
                                                :disabled="
                                                    !props.permissions
                                                        .canEdit ||
                                                    (selectedBlock.option_items
                                                        ?.length ?? 0) <= 1
                                                "
                                                class="rounded border border-rose-400/50 p-1 text-rose-200 disabled:opacity-40"
                                            >
                                                <Trash2 class="size-3" />
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <button
                                    @click="addBlockOption(selectedBlock)"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-2 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-2 text-sm text-[#cfe1ff] disabled:opacity-40"
                                >
                                    + adicionar pergunta
                                </button>

                                <div
                                    class="mt-2 rounded border border-[#2f568f] bg-[#0b234d] p-2"
                                >
                                    <label
                                        class="flex items-center gap-2 text-sm text-[#dbe8ff]"
                                    >
                                        <input
                                            v-model="
                                                selectedBlock.faq_first_active
                                            "
                                            :disabled="
                                                !props.permissions.canEdit
                                            "
                                            type="checkbox"
                                            class="h-4 w-4 accent-[#4b89ff] disabled:opacity-60"
                                        />
                                        <span>Primeira pergunta ativa</span>
                                    </label>
                                </div>
                            </template>

                            <template
                                v-else-if="
                                    selectedBlock.type === 'testimonials'
                                "
                            >
                                <label class="mt-2 block text-xs text-[#8daee7]"
                                    >Tipo</label
                                >
                                <select
                                    v-model="selectedBlock.testimonials_layout"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                >
                                    <option value="list">Lista</option>
                                    <option value="slide">Slide</option>
                                    <option value="grid">Grade</option>
                                </select>

                                <p
                                    class="mt-2 text-[11px] tracking-wide text-[#87a8de] uppercase"
                                >
                                    Depoimentos
                                </p>
                                <div class="mt-2 space-y-2">
                                    <div
                                        v-for="(
                                            item, itemIndex
                                        ) in selectedBlock.option_items ?? []"
                                        :key="`${selectedBlock.id}-testimonial-${item.id}`"
                                        class="rounded border border-[#2f568f] bg-[#0b234d] p-2"
                                    >
                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <label
                                                    class="mb-1 block text-[11px] text-[#8daee7]"
                                                    >Nome</label
                                                >
                                                <input
                                                    :data-testid="`builder-testimonial-name-${item.id}`"
                                                    v-model="item.label"
                                                    :disabled="
                                                        !props.permissions
                                                            .canEdit
                                                    "
                                                    class="w-full rounded border border-[#385f98] bg-[#081a3a] px-2 py-1 text-xs text-white outline-none disabled:opacity-60"
                                                />
                                            </div>
                                            <div>
                                                <label
                                                    class="mb-1 block text-[11px] text-[#8daee7]"
                                                    >@usuario</label
                                                >
                                                <input
                                                    :data-testid="`builder-testimonial-handle-${item.id}`"
                                                    v-model="item.subtitle"
                                                    :disabled="
                                                        !props.permissions
                                                            .canEdit
                                                    "
                                                    class="w-full rounded border border-[#385f98] bg-[#081a3a] px-2 py-1 text-xs text-white outline-none disabled:opacity-60"
                                                />
                                            </div>
                                        </div>
                                        <div
                                            class="mt-2 grid grid-cols-[1fr_86px] gap-2"
                                        >
                                            <div>
                                                <label
                                                    class="mb-1 block text-[11px] text-[#8daee7]"
                                                    >Depoimento</label
                                                >
                                                <textarea
                                                    :data-testid="`builder-testimonial-description-${item.id}`"
                                                    v-model="item.description"
                                                    :disabled="
                                                        !props.permissions
                                                            .canEdit
                                                    "
                                                    rows="3"
                                                    class="w-full rounded border border-[#385f98] bg-[#081a3a] px-2 py-1 text-xs text-white outline-none disabled:opacity-60"
                                                />
                                            </div>
                                            <div>
                                                <label
                                                    class="mb-1 block text-[11px] text-[#8daee7]"
                                                    >Nota</label
                                                >
                                                <input
                                                    v-model.number="item.rating"
                                                    :disabled="
                                                        !props.permissions
                                                            .canEdit
                                                    "
                                                    type="number"
                                                    min="1"
                                                    max="5"
                                                    class="w-full rounded border border-[#385f98] bg-[#081a3a] px-2 py-1 text-xs text-white outline-none disabled:opacity-60"
                                                />
                                            </div>
                                        </div>
                                        <div class="mt-2 flex justify-end">
                                            <button
                                                @click="
                                                    removeBlockOption(
                                                        selectedBlock,
                                                        itemIndex,
                                                    )
                                                "
                                                :disabled="
                                                    !props.permissions
                                                        .canEdit ||
                                                    (selectedBlock.option_items
                                                        ?.length ?? 0) <= 1
                                                "
                                                class="rounded border border-rose-400/50 p-1 text-rose-200 disabled:opacity-40"
                                            >
                                                <Trash2 class="size-3" />
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <button
                                    @click="addBlockOption(selectedBlock)"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-2 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-2 text-sm text-[#cfe1ff] disabled:opacity-40"
                                >
                                    + adicionar depoimento
                                </button>
                            </template>

                            <template
                                v-else-if="selectedBlock.type === 'price'"
                            >
                                <label class="block text-xs text-[#8daee7]"
                                    >Titulo</label
                                >
                                <input
                                    v-model="selectedBlock.price_title"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-2 text-sm text-white outline-none disabled:opacity-60"
                                />

                                <div class="mt-2 grid grid-cols-3 gap-2">
                                    <div>
                                        <label
                                            class="block text-xs text-[#8daee7]"
                                            >Prefixo</label
                                        >
                                        <input
                                            v-model="selectedBlock.price_prefix"
                                            :disabled="
                                                !props.permissions.canEdit ||
                                                uploadingCarouselItemId !== null
                                            "
                                            class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-2 text-sm text-white outline-none disabled:opacity-60"
                                        />
                                    </div>
                                    <div>
                                        <label
                                            class="block text-xs text-[#8daee7]"
                                            >Valor</label
                                        >
                                        <input
                                            v-model="selectedBlock.price_value"
                                            :disabled="
                                                !props.permissions.canEdit
                                            "
                                            class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-2 text-sm text-white outline-none disabled:opacity-60"
                                        />
                                    </div>
                                    <div>
                                        <label
                                            class="block text-xs text-[#8daee7]"
                                            >Sufixo</label
                                        >
                                        <input
                                            v-model="selectedBlock.price_suffix"
                                            :disabled="
                                                !props.permissions.canEdit
                                            "
                                            class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-2 text-sm text-white outline-none disabled:opacity-60"
                                        />
                                    </div>
                                </div>

                                <label class="mt-2 block text-xs text-[#8daee7]"
                                    >Texto destaque</label
                                >
                                <input
                                    v-model="selectedBlock.price_badge_text"
                                    :disabled="!props.permissions.canEdit"
                                    placeholder="Ex: popular, destaque..."
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-2 text-sm text-white outline-none disabled:opacity-60"
                                />

                                <label class="mt-2 block text-xs text-[#8daee7]"
                                    >Tipo de preco</label
                                >
                                <select
                                    v-model="selectedBlock.price_mode"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-2 text-sm text-white outline-none disabled:opacity-60"
                                >
                                    <option value="illustrative">
                                        Ilustrativo
                                    </option>
                                    <option value="redirect">
                                        Redirecionar
                                    </option>
                                </select>

                                <template
                                    v-if="
                                        selectedBlock.price_mode === 'redirect'
                                    "
                                >
                                    <label
                                        class="mt-2 block text-xs text-[#8daee7]"
                                        >URL de redirecionamento</label
                                    >
                                    <input
                                        v-model="selectedBlock.price_link"
                                        :disabled="!props.permissions.canEdit"
                                        placeholder="https://..."
                                        class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-2 text-sm text-white outline-none disabled:opacity-60"
                                    />
                                </template>
                            </template>

                            <template
                                v-else-if="selectedBlock.type === 'carousel'"
                            >
                                <label class="block text-xs text-[#8daee7]"
                                    >Layout</label
                                >
                                <select
                                    v-model="selectedBlock.carousel_layout"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-2 text-sm text-white outline-none disabled:opacity-60"
                                >
                                    <option value="image_text">
                                        Imagem e Texto
                                    </option>
                                    <option value="image_only">
                                        Somente imagem
                                    </option>
                                    <option value="text_only">
                                        Somente texto
                                    </option>
                                </select>

                                <div
                                    class="mt-2 rounded border border-[#2f568f] bg-[#0b234d] p-2"
                                >
                                    <label
                                        class="mb-1 flex items-center gap-2 text-sm text-[#dbe8ff]"
                                    >
                                        <input
                                            v-model="
                                                selectedBlock.carousel_pagination
                                            "
                                            :disabled="
                                                !props.permissions.canEdit
                                            "
                                            type="checkbox"
                                            class="h-4 w-4 accent-[#4b89ff] disabled:opacity-60"
                                        />
                                        <span>Paginacao</span>
                                    </label>
                                    <label
                                        class="flex items-center gap-2 text-sm text-[#dbe8ff]"
                                    >
                                        <input
                                            v-model="
                                                selectedBlock.carousel_autoplay
                                            "
                                            data-testid="builder-carousel-autoplay"
                                            :disabled="
                                                !props.permissions.canEdit
                                            "
                                            type="checkbox"
                                            class="h-4 w-4 accent-[#4b89ff] disabled:opacity-60"
                                        />
                                        <span>Autoplay</span>
                                    </label>
                                    <label
                                        v-if="selectedBlock.carousel_autoplay"
                                        class="mt-2 block text-xs text-[#8daee7]"
                                    >
                                        Velocidade por item (segundos)
                                        <input
                                            v-model.number="
                                                selectedBlock.carousel_autoplay_seconds
                                            "
                                            data-testid="builder-carousel-autoplay-seconds"
                                            :disabled="
                                                !props.permissions.canEdit
                                            "
                                            type="number"
                                            min="1"
                                            max="60"
                                            step="1"
                                            class="mt-1 w-full rounded border border-[#385f98] bg-[#081a3a] px-2 py-1.5 text-sm text-white outline-none disabled:opacity-60"
                                        />
                                    </label>
                                </div>

                                <p
                                    class="mt-2 text-[11px] tracking-wide text-[#87a8de] uppercase"
                                >
                                    Itens
                                </p>
                                <div class="mt-2 space-y-2">
                                    <div
                                        v-for="(
                                            item, itemIndex
                                        ) in selectedBlock.option_items ?? []"
                                        :key="`${selectedBlock.id}-carousel-${item.id}`"
                                        class="rounded border border-[#2f568f] bg-[#0b234d] p-2"
                                    >
                                        <div
                                            class="mb-2 text-center text-[#8daee7]"
                                        >
                                            ::
                                        </div>
                                        <button
                                            type="button"
                                            :data-testid="`builder-carousel-select-image-${item.id}`"
                                            class="w-full rounded border border-[#385f98] bg-[#081a3a] px-2 py-2 text-xs text-[#dbe8ff] hover:bg-[#123367]"
                                            :disabled="
                                                !props.permissions.canEdit
                                            "
                                            @click="
                                                triggerCarouselImagePicker(
                                                    item.id,
                                                )
                                            "
                                        >
                                            {{
                                                uploadingCarouselItemId ===
                                                item.id
                                                    ? 'Carregando imagem...'
                                                    : 'Selecionar imagem'
                                            }}
                                        </button>
                                        <img
                                            v-if="carouselItemImageUrl(item)"
                                            :src="
                                                carouselItemImageUrl(item) ??
                                                undefined
                                            "
                                            alt="Imagem do item"
                                            class="mt-2 h-24 w-full rounded border border-[#385f98] object-cover"
                                        />
                                        <input
                                            :data-testid="`builder-carousel-image-url-${item.id}`"
                                            v-model="item.value"
                                            :disabled="
                                                !props.permissions.canEdit
                                            "
                                            placeholder="URL da imagem (opcional)"
                                            class="mt-2 w-full rounded border border-[#385f98] bg-[#081a3a] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                            @input="item.image_url = item.value"
                                        />
                                        <textarea
                                            :data-testid="`builder-carousel-description-${item.id}`"
                                            v-model="item.description"
                                            :disabled="
                                                !props.permissions.canEdit
                                            "
                                            rows="2"
                                            placeholder=""
                                            class="mt-2 w-full rounded border border-[#385f98] bg-[#081a3a] px-2 py-1.5 text-sm text-[#dbe8ff] outline-none disabled:opacity-60"
                                        />
                                        <div class="mt-2 flex justify-end">
                                            <button
                                                @click="
                                                    removeBlockOption(
                                                        selectedBlock,
                                                        itemIndex,
                                                    )
                                                "
                                                :disabled="
                                                    !props.permissions
                                                        .canEdit ||
                                                    (selectedBlock.option_items
                                                        ?.length ?? 0) <= 1
                                                "
                                                class="rounded border border-rose-400/50 p-1 text-rose-200 disabled:opacity-40"
                                            >
                                                <Trash2 class="size-3" />
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <button
                                    @click="addBlockOption(selectedBlock)"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-2 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-2 text-sm text-[#cfe1ff] disabled:opacity-40"
                                >
                                    + adicionar item
                                </button>
                                <input
                                    ref="carouselImageInput"
                                    data-testid="builder-carousel-file-input"
                                    type="file"
                                    accept="image/*"
                                    class="hidden"
                                    @change="handleCarouselImageFileChange"
                                />
                            </template>

                            <template
                                v-else-if="selectedBlock.type === 'metrics'"
                            >
                                <p
                                    class="mt-2 text-[11px] tracking-wide text-[#87a8de] uppercase"
                                >
                                    Metricas
                                </p>
                                <div class="mt-2 space-y-2">
                                    <div
                                        v-for="(
                                            item, itemIndex
                                        ) in selectedBlock.option_items ?? []"
                                        :key="`${selectedBlock.id}-metric-${item.id}`"
                                        class="rounded border border-[#2f568f] bg-[#0b234d] p-2"
                                    >
                                        <div class="grid gap-2">
                                            <div>
                                                <label
                                                    class="mb-1 block text-[11px] text-[#8daee7]"
                                                    >Nome da metrica</label
                                                >
                                                <input
                                                    :data-testid="`builder-metric-label-${item.id}`"
                                                    v-model="item.label"
                                                    :disabled="
                                                        !props.permissions
                                                            .canEdit
                                                    "
                                                    class="w-full rounded border border-[#385f98] bg-[#081a3a] px-2 py-1.5 text-sm text-white outline-none disabled:opacity-60"
                                                />
                                            </div>
                                            <div>
                                                <label
                                                    class="mb-1 block text-[11px] text-[#8daee7]"
                                                    >Valor</label
                                                >
                                                <input
                                                    :data-testid="`builder-metric-value-${item.id}`"
                                                    v-model="item.value"
                                                    :disabled="
                                                        !props.permissions
                                                            .canEdit
                                                    "
                                                    class="w-full rounded border border-[#385f98] bg-[#081a3a] px-2 py-1.5 text-sm text-white outline-none disabled:opacity-60"
                                                />
                                            </div>
                                            <div>
                                                <label
                                                    class="mb-1 block text-[11px] text-[#8daee7]"
                                                    >Descricao</label
                                                >
                                                <input
                                                    :data-testid="`builder-metric-description-${item.id}`"
                                                    v-model="item.description"
                                                    :disabled="
                                                        !props.permissions
                                                            .canEdit
                                                    "
                                                    class="w-full rounded border border-[#385f98] bg-[#081a3a] px-2 py-1.5 text-sm text-white outline-none disabled:opacity-60"
                                                />
                                            </div>
                                        </div>
                                        <div class="mt-2 flex justify-end">
                                            <button
                                                @click="
                                                    removeBlockOption(
                                                        selectedBlock,
                                                        itemIndex,
                                                    )
                                                "
                                                :disabled="
                                                    !props.permissions
                                                        .canEdit ||
                                                    (selectedBlock.option_items
                                                        ?.length ?? 0) <= 1
                                                "
                                                class="rounded border border-rose-400/50 p-1 text-rose-200 disabled:opacity-40"
                                            >
                                                <Trash2 class="size-3" />
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <button
                                    @click="addBlockOption(selectedBlock)"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-2 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-2 text-sm text-[#cfe1ff] disabled:opacity-40"
                                >
                                    + adicionar metrica
                                </button>
                            </template>

                            <template
                                v-else-if="
                                    selectedBlock.type === 'arguments' ||
                                    selectedBlock.type === 'before_after'
                                "
                            >
                                <p
                                    class="mt-2 text-[11px] tracking-wide text-[#87a8de] uppercase"
                                >
                                    {{ blockOptionsTitle(selectedBlock.type) }}
                                </p>
                                <div class="mt-2 space-y-1">
                                    <div
                                        v-for="(
                                            option, optionIndex
                                        ) in selectedBlock.options ?? []"
                                        :key="`${selectedBlock.id}-opt-${optionIndex}`"
                                        class="flex items-center gap-1"
                                    >
                                        <span
                                            v-if="
                                                selectedBlock.type ===
                                                'before_after'
                                            "
                                            class="min-w-20 text-[11px] text-[#8daee7]"
                                        >
                                            {{
                                                optionIndex === 0
                                                    ? 'Antes'
                                                    : optionIndex === 1
                                                      ? 'Depois'
                                                      : `Comparativo ${optionIndex + 1}`
                                            }}
                                        </span>
                                        <span
                                            v-else
                                            class="min-w-20 text-[11px] text-[#8daee7]"
                                        >
                                            {{ `Argumento ${optionIndex + 1}` }}
                                        </span>
                                        <input
                                            :data-testid="`${selectedBlock.type}-option-${optionIndex}`"
                                            v-model="
                                                selectedBlock.options![
                                                    optionIndex
                                                ]
                                            "
                                            :disabled="
                                                !props.permissions.canEdit
                                            "
                                            :placeholder="
                                                selectedBlock.type ===
                                                'before_after'
                                                    ? optionIndex === 0
                                                        ? 'Situacao atual'
                                                        : optionIndex === 1
                                                          ? 'Resultado esperado'
                                                          : ''
                                                    : 'Descreva o argumento'
                                            "
                                            class="w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1 text-xs text-white outline-none disabled:opacity-60"
                                        />
                                        <button
                                            @click="
                                                removeBlockOption(
                                                    selectedBlock,
                                                    optionIndex,
                                                )
                                            "
                                            :disabled="
                                                !props.permissions.canEdit ||
                                                (selectedBlock.options
                                                    ?.length ?? 0) <=
                                                    minimumOptions(
                                                        selectedBlock.type,
                                                    )
                                            "
                                            class="rounded border border-rose-400/50 p-1 text-rose-200 disabled:opacity-40"
                                        >
                                            <Trash2 class="size-3" />
                                        </button>
                                    </div>
                                </div>
                                <button
                                    :data-testid="`add-${selectedBlock.type}-option`"
                                    @click="addBlockOption(selectedBlock)"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-2 rounded border border-[#2f568f] px-2 py-1 text-xs text-[#cfe1ff] disabled:opacity-40"
                                >
                                    {{ addOptionLabel(selectedBlock.type) }}
                                </button>
                            </template>

                            <div class="mt-3 border-t border-[#2f568f] pt-2">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 text-xs font-medium text-[#9bb9ef]"
                                >
                                    <Plus class="size-3.5" /> AVANCADO
                                </button>
                            </div>
                        </template>

                        <template
                            v-else-if="
                                selectedBlock &&
                                componentPanelTab === 'appearance'
                            "
                        >
                            <template v-if="selectedBlock.type === 'button'">
                                <label class="text-xs text-[#8daee7]"
                                    >Cor</label
                                >
                                <select
                                    v-model="selectedBlock.button_color_style"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                >
                                    <option value="theme">Cor do tema</option>
                                    <option value="dark">Escuro</option>
                                    <option value="light">Claro</option>
                                </select>

                                <div
                                    class="mt-2 rounded border border-[#2f568f] bg-[#0b234d] p-2"
                                >
                                    <label
                                        class="mb-1 flex items-center gap-2 text-xs text-[#dbe8ff]"
                                    >
                                        <input
                                            v-model="
                                                selectedBlock.button_animated
                                            "
                                            :disabled="
                                                !props.permissions.canEdit
                                            "
                                            type="checkbox"
                                            class="h-4 w-4 accent-[#4b89ff] disabled:opacity-60"
                                        />
                                        <span>Com animacao</span>
                                    </label>
                                    <label
                                        class="mb-1 flex items-center gap-2 text-xs text-[#dbe8ff]"
                                    >
                                        <input
                                            v-model="
                                                selectedBlock.button_elevated
                                            "
                                            :disabled="
                                                !props.permissions.canEdit
                                            "
                                            type="checkbox"
                                            class="h-4 w-4 accent-[#4b89ff] disabled:opacity-60"
                                        />
                                        <span>Alto relevo</span>
                                    </label>
                                    <label
                                        class="flex items-center gap-2 text-xs text-[#dbe8ff]"
                                    >
                                        <input
                                            v-model="
                                                selectedBlock.button_sticky_footer
                                            "
                                            :disabled="
                                                !props.permissions.canEdit
                                            "
                                            type="checkbox"
                                            class="h-4 w-4 accent-[#4b89ff] disabled:opacity-60"
                                        />
                                        <span>Fixar no rodape</span>
                                    </label>
                                </div>
                            </template>
                            <template
                                v-else-if="
                                    isOptionsComponentType(selectedBlock.type)
                                "
                            >
                                <label class="text-xs text-[#8daee7]"
                                    >Estilo</label
                                >
                                <select
                                    v-model="selectedBlock.options_style"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                >
                                    <option value="simple">Simples</option>
                                    <option value="highlight">Destacar</option>
                                    <option value="relief">Relevo</option>
                                    <option value="contrast">Contraste</option>
                                </select>

                                <label
                                    class="mt-2 flex items-center gap-2 text-xs text-[#dbe8ff]"
                                >
                                    <input
                                        v-model="
                                            selectedBlock.options_transparent_image
                                        "
                                        :disabled="!props.permissions.canEdit"
                                        type="checkbox"
                                        class="h-4 w-4 accent-[#4b89ff] disabled:opacity-60"
                                    />
                                    <span>Imagem com fundo transparente</span>
                                </label>

                                <div class="mt-2 grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="text-xs text-[#8daee7]"
                                            >Layout</label
                                        >
                                        <select
                                            v-model="
                                                selectedBlock.options_layout
                                            "
                                            :disabled="
                                                !props.permissions.canEdit
                                            "
                                            class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                        >
                                            <option value="grid_2">
                                                Grade de 2 colunas
                                            </option>
                                            <option value="grid_1">
                                                Lista
                                            </option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-xs text-[#8daee7]"
                                            >Orientacao</label
                                        >
                                        <select
                                            v-model="
                                                selectedBlock.options_orientation
                                            "
                                            :disabled="
                                                !props.permissions.canEdit ||
                                                selectedBlock.options_layout ===
                                                    'grid_2'
                                            "
                                            class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                        >
                                            <option value="vertical">
                                                Vertical
                                            </option>
                                            <option value="horizontal">
                                                Horizontal
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <label class="mt-2 block text-xs text-[#8daee7]"
                                    >Proporcao de imagens</label
                                >
                                <select
                                    v-model="selectedBlock.options_image_ratio"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                >
                                    <option value="1:1">1:1 (Quadrado)</option>
                                    <option value="4:3">4:3</option>
                                    <option value="16:9">16:9</option>
                                </select>

                                <label class="mt-2 block text-xs text-[#8daee7]"
                                    >Disposicao</label
                                >
                                <select
                                    v-model="selectedBlock.options_disposition"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                >
                                    <option value="image_text">
                                        Imagem | Texto
                                    </option>
                                    <option value="text_image">
                                        Texto | Imagem
                                    </option>
                                    <option value="text">Apenas texto</option>
                                </select>

                                <div class="mt-2 grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="text-xs text-[#8daee7]"
                                            >Detalhe</label
                                        >
                                        <select
                                            v-model="
                                                selectedBlock.options_detail
                                            "
                                            :disabled="
                                                !props.permissions.canEdit
                                            "
                                            class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                        >
                                            <option value="none">Nenhum</option>
                                            <option value="checkout">
                                                Checkout
                                            </option>
                                            <option value="arrow">Seta</option>
                                            <option value="points">
                                                Pontos
                                            </option>
                                            <option value="value">Valor</option>
                                        </select>
                                    </div>
                                </div>

                                <label class="mt-2 block text-xs text-[#8daee7]"
                                    >Bordas</label
                                >
                                <select
                                    v-model="selectedBlock.options_border_size"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                >
                                    <option value="small">Pequeno</option>
                                    <option value="medium">Medio</option>
                                    <option value="large">Grande</option>
                                </select>

                                <label class="mt-2 block text-xs text-[#8daee7]"
                                    >Sombra</label
                                >
                                <select
                                    v-model="selectedBlock.options_shadow"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                >
                                    <option value="none">Sem sombra</option>
                                    <option value="soft">Suave</option>
                                    <option value="strong">Forte</option>
                                </select>

                                <label class="mt-2 block text-xs text-[#8daee7]"
                                    >Espacamento</label
                                >
                                <select
                                    v-model="selectedBlock.options_spacing"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                >
                                    <option value="simple">Simples</option>
                                    <option value="comfortable">
                                        Confortavel
                                    </option>
                                    <option value="compact">Compacto</option>
                                </select>
                            </template>
                            <template
                                v-else-if="
                                    selectedBlock.type === 'content_text'
                                "
                            />
                            <template
                                v-else-if="selectedBlock.type === 'image'"
                            >
                                <label class="text-xs text-[#8daee7]"
                                    >Formato</label
                                >
                                <select
                                    v-model="selectedBlock.image_ratio"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                >
                                    <option value="auto">Original</option>
                                    <option value="16:9">16:9</option>
                                    <option value="4:3">4:3</option>
                                    <option value="1:1">Quadrado</option>
                                </select>

                                <label class="mt-2 block text-xs text-[#8daee7]"
                                    >Encaixe</label
                                >
                                <select
                                    v-model="selectedBlock.image_fit"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                >
                                    <option value="cover">Preencher</option>
                                    <option value="contain">Conter</option>
                                </select>

                                <label class="mt-2 block text-xs text-[#8daee7]"
                                    >Cantos</label
                                >
                                <select
                                    v-model="selectedBlock.image_radius"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                >
                                    <option value="none">Reto</option>
                                    <option value="small">Pequeno</option>
                                    <option value="medium">Medio</option>
                                    <option value="large">Grande</option>
                                    <option value="full">Circular</option>
                                </select>

                                <label class="mt-2 block text-xs text-[#8daee7]"
                                    >Moldura</label
                                >
                                <select
                                    v-model="selectedBlock.image_frame"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                >
                                    <option value="none">Sem moldura</option>
                                    <option value="subtle">Suave</option>
                                    <option value="strong">Forte</option>
                                </select>
                            </template>
                            <template
                                v-else-if="selectedBlock.type === 'price'"
                            >
                                <label class="text-xs text-[#8daee7]"
                                    >Layout</label
                                >
                                <select
                                    v-model="selectedBlock.price_layout"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                >
                                    <option value="horizontal">
                                        Horizontal
                                    </option>
                                    <option value="vertical">Vertical</option>
                                </select>

                                <label class="mt-2 block text-xs text-[#8daee7]"
                                    >Estilo</label
                                >
                                <select
                                    v-model="selectedBlock.price_style"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                >
                                    <option value="theme">Tema</option>
                                    <option value="light">Claro</option>
                                    <option value="dark">Escuro</option>
                                </select>
                            </template>
                            <template
                                v-else-if="selectedBlock.type === 'carousel'"
                            >
                                <label class="text-xs text-[#8daee7]"
                                    >Tipo</label
                                >
                                <select
                                    v-model="selectedBlock.carousel_border_type"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                >
                                    <option value="none">Sem borda</option>
                                    <option value="subtle">Borda suave</option>
                                    <option value="strong">Borda forte</option>
                                </select>
                            </template>
                            <template v-else-if="selectedBlock.type === 'faq'">
                                <label class="text-xs text-[#8daee7]"
                                    >Detalhe</label
                                >
                                <select
                                    v-model="selectedBlock.faq_detail"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                >
                                    <option value="arrow">Seta cima</option>
                                    <option value="chevron">
                                        Seta direita
                                    </option>
                                    <option value="plus_minus">
                                        Mais/Menos
                                    </option>
                                    <option value="none">Nenhum</option>
                                </select>
                            </template>
                            <template
                                v-else-if="
                                    selectedBlock.type === 'testimonials'
                                "
                            >
                                <label class="text-xs text-[#8daee7]"
                                    >Bordas</label
                                >
                                <select
                                    v-model="selectedBlock.options_border_size"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                >
                                    <option value="small">Pequeno</option>
                                    <option value="medium">Medio</option>
                                    <option value="large">Grande</option>
                                </select>

                                <label class="mt-2 block text-xs text-[#8daee7]"
                                    >Sombra</label
                                >
                                <select
                                    v-model="selectedBlock.options_shadow"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                >
                                    <option value="none">Sem sombra</option>
                                    <option value="soft">Suave</option>
                                    <option value="strong">Forte</option>
                                </select>

                                <label class="mt-2 block text-xs text-[#8daee7]"
                                    >Espacamento</label
                                >
                                <select
                                    v-model="selectedBlock.options_spacing"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                >
                                    <option value="simple">Simples</option>
                                    <option value="comfortable">
                                        Confortavel
                                    </option>
                                    <option value="compact">Compacto</option>
                                </select>
                            </template>
                            <template
                                v-else-if="selectedBlock.type === 'video'"
                            >
                                <label class="text-xs text-[#8daee7]"
                                    >Disposicao</label
                                >
                                <select
                                    data-testid="builder-video-ratio-select"
                                    v-model="selectedBlock.video_ratio"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                >
                                    <option
                                        v-for="ratio in videoRatioOptions"
                                        :key="ratio.value"
                                        :value="ratio.value"
                                    >
                                        {{ ratio.label }}
                                    </option>
                                </select>
                            </template>
                            <template
                                v-else-if="selectedBlock.type === 'audio'"
                            >
                                <label class="text-xs text-[#8daee7]"
                                    >Modelo</label
                                >
                                <select
                                    v-model="selectedBlock.audio_model"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                >
                                    <option
                                        v-for="model in audioModelOptions"
                                        :key="model.value"
                                        :value="model.value"
                                    >
                                        {{ model.label }}
                                    </option>
                                </select>

                                <label class="mt-2 block text-xs text-[#8daee7]"
                                    >Tema</label
                                >
                                <select
                                    v-model="selectedBlock.audio_theme"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                >
                                    <option
                                        v-for="themeOption in audioThemeOptions"
                                        :key="themeOption.value"
                                        :value="themeOption.value"
                                    >
                                        {{ themeOption.label }}
                                    </option>
                                </select>
                            </template>
                            <template
                                v-else-if="selectedBlock.type === 'attention'"
                            >
                                <label class="text-xs text-[#8daee7]"
                                    >Estilo</label
                                >
                                <select
                                    v-model="selectedBlock.attention_style"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                >
                                    <option
                                        v-for="styleOption in attentionStyleOptions"
                                        :key="styleOption.value"
                                        :value="styleOption.value"
                                    >
                                        {{ styleOption.label }}
                                    </option>
                                </select>

                                <label
                                    class="mt-2 flex items-center gap-2 text-xs text-[#dbe8ff]"
                                >
                                    <input
                                        v-model="
                                            selectedBlock.attention_emphasis
                                        "
                                        :disabled="!props.permissions.canEdit"
                                        type="checkbox"
                                        class="h-4 w-4 accent-[#4b89ff] disabled:opacity-60"
                                    />
                                    <span>Destacar</span>
                                </label>

                                <label class="mt-2 block text-xs text-[#8daee7]"
                                    >Margem interna</label
                                >
                                <select
                                    v-model="selectedBlock.attention_padding"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                >
                                    <option
                                        v-for="paddingOption in attentionPaddingOptions"
                                        :key="paddingOption.value"
                                        :value="paddingOption.value"
                                    >
                                        {{ paddingOption.label }}
                                    </option>
                                </select>
                            </template>
                            <template
                                v-else-if="
                                    selectedBlock.type === 'notification'
                                "
                            >
                                <label class="text-xs text-[#8daee7]"
                                    >Posicao na tela</label
                                >
                                <select
                                    v-model="
                                        selectedBlock.notification_position
                                    "
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                >
                                    <option value="default">No fluxo</option>
                                    <option value="top_left">
                                        Topo esquerdo
                                    </option>
                                    <option value="top_center">
                                        Topo centro
                                    </option>
                                    <option value="top_right">
                                        Topo direito
                                    </option>
                                    <option value="bottom_left">
                                        Base esquerda
                                    </option>
                                    <option value="bottom_center">
                                        Base centro
                                    </option>
                                    <option value="bottom_right">
                                        Base direita
                                    </option>
                                </select>

                                <label class="mt-2 block text-xs text-[#8daee7]"
                                    >Tamanho</label
                                >
                                <select
                                    v-model="selectedBlock.notification_size"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                >
                                    <option value="compact">Compacta</option>
                                    <option value="default">Padrao</option>
                                    <option value="large">Grande</option>
                                </select>

                                <label class="text-xs text-[#8daee7]"
                                    >Tipo</label
                                >
                                <select
                                    v-model="selectedBlock.notification_variant"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                >
                                    <option value="social">Prova social</option>
                                    <option value="offer">Oferta</option>
                                    <option value="message">Mensagem</option>
                                </select>

                                <label class="text-xs text-[#8daee7]"
                                    >Estilo</label
                                >
                                <select
                                    v-model="selectedBlock.notification_style"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                >
                                    <option
                                        v-for="styleOption in notificationStyleOptions"
                                        :key="styleOption.value"
                                        :value="styleOption.value"
                                    >
                                        {{ styleOption.label }}
                                    </option>
                                </select>
                            </template>
                            <template
                                v-else-if="selectedBlock.type === 'timer'"
                            >
                                <label class="text-xs text-[#8daee7]"
                                    >Estilo</label
                                >
                                <select
                                    v-model="selectedBlock.timer_style"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                >
                                    <option
                                        v-for="styleOption in timerStyleOptions"
                                        :key="styleOption.value"
                                        :value="styleOption.value"
                                    >
                                        {{ styleOption.label }}
                                    </option>
                                </select>
                            </template>
                            <template
                                v-else-if="selectedBlock.type === 'level'"
                            >
                                <label class="text-xs text-[#8daee7]"
                                    >Tipo</label
                                >
                                <select
                                    v-model="selectedBlock.level_type"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                >
                                    <option value="line">Linha</option>
                                </select>

                                <label class="mt-2 block text-xs text-[#8daee7]"
                                    >Cor</label
                                >
                                <select
                                    v-model="selectedBlock.level_color"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                >
                                    <option
                                        v-for="colorOption in levelColorOptions"
                                        :key="colorOption.value"
                                        :value="colorOption.value"
                                    >
                                        {{ colorOption.label }}
                                    </option>
                                </select>
                            </template>
                            <template
                                v-else-if="selectedBlock.type === 'loading'"
                            />
                            <template v-else>
                                <label class="text-xs text-[#8daee7]"
                                    >Label</label
                                >
                                <select
                                    v-model="selectedBlock.label_style"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                >
                                    <option value="default">Padrao</option>
                                    <option value="muted">Suave</option>
                                    <option value="hidden">Oculto</option>
                                </select>
                            </template>

                            <template
                                v-if="
                                    selectedBlock.type !== 'content_text' &&
                                    selectedBlock.type !== 'video' &&
                                    selectedBlock.type !== 'audio' &&
                                    selectedBlock.type !== 'attention' &&
                                    selectedBlock.type !== 'notification' &&
                                    selectedBlock.type !== 'timer' &&
                                    selectedBlock.type !== 'loading' &&
                                    selectedBlock.type !== 'level' &&
                                    selectedBlock.type !== 'testimonials' &&
                                    selectedBlock.type !== 'faq' &&
                                    selectedBlock.type !== 'price' &&
                                    selectedBlock.type !== 'carousel'
                                "
                            >
                                <label class="mt-2 block text-xs text-[#8daee7]"
                                    >Alinhamento do texto</label
                                >
                                <select
                                    v-model="selectedBlock.text_align"
                                    :disabled="!props.permissions.canEdit"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                >
                                    <option value="text-left">text-left</option>
                                    <option value="text-center">
                                        text-center
                                    </option>
                                    <option value="text-right">
                                        text-right
                                    </option>
                                </select>
                            </template>

                            <label class="mt-2 block text-xs text-[#8daee7]"
                                >Largura</label
                            >
                            <div class="mt-1 flex items-center gap-2">
                                <input
                                    v-model.number="selectedBlock.width_percent"
                                    :disabled="!props.permissions.canEdit"
                                    type="range"
                                    min="25"
                                    max="100"
                                    class="w-full accent-[#4b89ff] disabled:opacity-60"
                                />
                                <span class="text-xs text-[#cfe1ff]"
                                    >{{
                                        Math.round(
                                            Number(
                                                selectedBlock.width_percent ??
                                                    100,
                                            ),
                                        )
                                    }}%</span
                                >
                            </div>

                            <div class="mt-2 grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-xs text-[#8daee7]"
                                        >Alinhamento horizontal</label
                                    >
                                    <select
                                        v-model="selectedBlock.align_horizontal"
                                        :disabled="!props.permissions.canEdit"
                                        class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                    >
                                        <option value="start">Comeco</option>
                                        <option value="center">Centro</option>
                                        <option value="end">Fim</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs text-[#8daee7]"
                                        >Alinhamento vertical</label
                                    >
                                    <select
                                        v-model="selectedBlock.align_vertical"
                                        :disabled="!props.permissions.canEdit"
                                        class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                    >
                                        <option value="start">Comeco</option>
                                        <option value="center">Centro</option>
                                        <option value="end">Fim</option>
                                    </select>
                                </div>
                            </div>
                        </template>

                        <template
                            v-else-if="
                                selectedBlock && componentPanelTab === 'display'
                            "
                        >
                            <template
                                v-if="
                                    selectedBlock.type === 'email' ||
                                    selectedBlock.type === 'button'
                                "
                            >
                                <p class="text-xs text-[#9bb9ef]">
                                    Exibicao: padrao para componente de
                                    {{
                                        selectedBlock.type === 'button'
                                            ? 'botao'
                                            : 'e-mail'
                                    }}.
                                </p>
                            </template>
                            <template v-else>
                                <label class="text-xs text-[#8daee7]"
                                    >Mostrar apos:</label
                                >
                                <input
                                    v-model.number="
                                        selectedBlock.show_after_seconds
                                    "
                                    :disabled="!props.permissions.canEdit"
                                    type="number"
                                    min="0"
                                    placeholder="Segundos"
                                    class="mt-1 w-full rounded border border-[#2f568f] bg-[#0b234d] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                />

                                <div
                                    class="mt-3 border-t border-[#2f568f] pt-2"
                                >
                                    <p class="text-xs text-[#8daee7]">
                                        Regras de exibicao
                                    </p>
                                    <p
                                        class="mt-1 text-[11px] leading-5 text-[#89a9db]"
                                    >
                                        Monte grupos de regras. O bloco aparece
                                        quando os grupos atenderem a combinacao
                                        acima. Para varios valores, separe com
                                        <code>|</code>.
                                    </p>
                                    <select
                                        v-model="
                                            selectedBlock.display_rule_mode
                                        "
                                        :disabled="!props.permissions.canEdit"
                                        class="mt-2 w-full rounded border border-[#2f568f] bg-[#081d40] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                    >
                                        <option value="all">
                                            Todos os grupos (AND)
                                        </option>
                                        <option value="any">
                                            Qualquer grupo (OR)
                                        </option>
                                    </select>
                                    <button
                                        data-testid="display-rule-add-button"
                                        @click="
                                            addDisplayRuleGroup(selectedBlock)
                                        "
                                        :disabled="!props.permissions.canEdit"
                                        class="mt-2 w-full rounded border border-[#2f568f] bg-[#0b234d] px-3 py-2 text-sm text-white disabled:opacity-60"
                                    >
                                        + adicionar grupo
                                    </button>

                                    <div
                                        v-if="
                                            (selectedBlock.display_rule_groups
                                                ?.length ?? 0) > 0
                                        "
                                        class="mt-2 space-y-2"
                                    >
                                        <div
                                            v-for="(
                                                group, groupIndex
                                            ) in selectedBlock.display_rule_groups ??
                                            []"
                                            :key="
                                                group.id ||
                                                `${selectedBlock.id}-group-${groupIndex}`
                                            "
                                            class="space-y-2 rounded border border-[#2f568f] bg-[#0b234d] p-2"
                                        >
                                            <div
                                                class="grid grid-cols-[minmax(0,1fr)_auto] gap-1"
                                            >
                                                <select
                                                    v-model="
                                                        selectedBlock
                                                            .display_rule_groups![
                                                            groupIndex
                                                        ].mode
                                                    "
                                                    :disabled="
                                                        !props.permissions
                                                            .canEdit
                                                    "
                                                    class="rounded border border-[#2f568f] bg-[#081d40] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                                >
                                                    <option value="all">
                                                        Todas as regras no grupo
                                                    </option>
                                                    <option value="any">
                                                        Qualquer regra no grupo
                                                    </option>
                                                </select>
                                                <button
                                                    @click="
                                                        removeDisplayRuleGroup(
                                                            selectedBlock,
                                                            groupIndex,
                                                        )
                                                    "
                                                    :disabled="
                                                        !props.permissions
                                                            .canEdit
                                                    "
                                                    class="rounded border border-rose-400/50 p-1 text-rose-200 disabled:opacity-40"
                                                >
                                                    <Trash2 class="size-3" />
                                                </button>
                                            </div>
                                            <button
                                                @click="
                                                    addDisplayRule(
                                                        selectedBlock,
                                                        groupIndex,
                                                    )
                                                "
                                                :disabled="
                                                    !props.permissions.canEdit
                                                "
                                                class="w-full rounded border border-[#2f568f] bg-[#081d40] px-2 py-1.5 text-xs text-white disabled:opacity-60"
                                            >
                                                + adicionar regra
                                            </button>
                                            <div
                                                v-for="(
                                                    rule, ruleIndex
                                                ) in group.rules"
                                                :key="
                                                    rule.id ||
                                                    `${group.id}-rule-${ruleIndex}`
                                                "
                                                class="space-y-2 rounded border border-[#234679] bg-[#081933] p-2"
                                            >
                                                <select
                                                    v-model="
                                                        selectedBlock
                                                            .display_rule_groups![
                                                            groupIndex
                                                        ].rules[ruleIndex]
                                                            .source_block_id
                                                    "
                                                    :disabled="
                                                        !props.permissions
                                                            .canEdit
                                                    "
                                                    :data-testid="`display-rule-source-${groupIndex}-${ruleIndex}`"
                                                    class="w-full rounded border border-[#2f568f] bg-[#081d40] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                                >
                                                    <option value="">
                                                        Selecione um componente
                                                    </option>
                                                    <option
                                                        v-for="option in displayRuleBlockOptions(
                                                            selectedBlock,
                                                        )"
                                                        :key="option.value"
                                                        :value="option.value"
                                                    >
                                                        {{ option.label }}
                                                    </option>
                                                </select>
                                                <div
                                                    class="grid grid-cols-[minmax(0,1fr)_auto] gap-1"
                                                >
                                                    <select
                                                        v-model="
                                                            selectedBlock
                                                                .display_rule_groups![
                                                                groupIndex
                                                            ].rules[ruleIndex]
                                                                .operator
                                                        "
                                                        :disabled="
                                                            !props.permissions
                                                                .canEdit
                                                        "
                                                        :data-testid="`display-rule-operator-${groupIndex}-${ruleIndex}`"
                                                        class="rounded border border-[#2f568f] bg-[#081d40] px-2 py-1.5 text-xs text-white outline-none disabled:opacity-60"
                                                    >
                                                        <option value="filled">
                                                            Preenchido
                                                        </option>
                                                        <option value="empty">
                                                            Vazio
                                                        </option>
                                                        <option value="equals">
                                                            Igual a
                                                        </option>
                                                        <option
                                                            value="not_equals"
                                                        >
                                                            Diferente de
                                                        </option>
                                                        <option
                                                            value="contains_any"
                                                        >
                                                            Contem qualquer
                                                            valor
                                                        </option>
                                                        <option
                                                            value="contains_all"
                                                        >
                                                            Contem todos os
                                                            valores
                                                        </option>
                                                    </select>
                                                    <button
                                                        @click="
                                                            removeDisplayRule(
                                                                selectedBlock,
                                                                groupIndex,
                                                                ruleIndex,
                                                            )
                                                        "
                                                        :disabled="
                                                            !props.permissions
                                                                .canEdit
                                                        "
                                                        class="rounded border border-rose-400/50 p-1 text-rose-200 disabled:opacity-40"
                                                    >
                                                        <Trash2
                                                            class="size-3"
                                                        />
                                                    </button>
                                                </div>
                                                <input
                                                    v-if="
                                                        displayRuleOperatorNeedsValue(
                                                            rule.operator,
                                                        )
                                                    "
                                                    v-model="
                                                        selectedBlock
                                                            .display_rule_groups![
                                                            groupIndex
                                                        ].rules[ruleIndex].value
                                                    "
                                                    :data-testid="`display-rule-value-${groupIndex}-${ruleIndex}`"
                                                    :disabled="
                                                        !props.permissions
                                                            .canEdit
                                                    "
                                                    placeholder="Valor esperado"
                                                    class="w-full rounded border border-[#2f568f] bg-[#081d40] px-2 py-1 text-xs text-white outline-none disabled:opacity-60"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </template>
                    </section>
                </div>

                <div v-else class="space-y-3">
                    <section class="px-1 pb-2">
                        <p class="mb-2 text-xs text-[#8daee7]">
                            Imagem de fundo
                        </p>
                        <div
                            class="mb-2 grid grid-cols-2 gap-2 rounded-lg bg-[#0b234d] p-1"
                        >
                            <button
                                class="rounded-md py-1.5 text-xs"
                                :class="
                                    appearanceBackgroundTab === 'image'
                                        ? 'bg-[#1f60d4] text-white'
                                        : 'text-[#a8c5f6]'
                                "
                                @click="appearanceBackgroundTab = 'image'"
                            >
                                Imagem
                            </button>
                            <button
                                class="rounded-md py-1.5 text-xs"
                                :class="
                                    appearanceBackgroundTab === 'url'
                                        ? 'bg-[#1f60d4] text-white'
                                        : 'text-[#a8c5f6]'
                                "
                                @click="appearanceBackgroundTab = 'url'"
                            >
                                URL
                            </button>
                        </div>

                        <button
                            v-if="appearanceBackgroundTab === 'image'"
                            class="mb-2 w-full rounded-lg border border-[#2f568f] bg-[#0b234d] px-3 py-3 text-sm text-white"
                        >
                            Selecionar imagem
                        </button>

                        <input
                            v-else
                            v-model="appearanceBackgroundUrl"
                            placeholder="https://imagem..."
                            class="mb-2 w-full rounded-lg border border-[#2f568f] bg-[#0b234d] px-3 py-2 text-sm text-white outline-none"
                        />

                        <div class="grid grid-cols-3 gap-2">
                            <select
                                v-model="appearanceSize"
                                class="rounded-lg border border-[#2f568f] bg-[#0b234d] px-2 py-2 text-xs text-white outline-none"
                            >
                                <option>Tela inteira</option>
                                <option>Contido</option>
                            </select>
                            <select
                                v-model="appearancePosition"
                                class="rounded-lg border border-[#2f568f] bg-[#0b234d] px-2 py-2 text-xs text-white outline-none"
                            >
                                <option>Centro</option>
                                <option>Topo</option>
                                <option>Base</option>
                            </select>
                            <select
                                v-model="appearanceScale"
                                class="rounded-lg border border-[#2f568f] bg-[#0b234d] px-2 py-2 text-xs text-white outline-none"
                            >
                                <option>100%</option>
                                <option>80%</option>
                                <option>120%</option>
                            </select>
                        </div>
                    </section>
                </div>
            </aside>
        </main>

        <Dialog
            :open="isSaveTemplateModalOpen"
            @update:open="
                $event
                    ? (isSaveTemplateModalOpen = true)
                    : closeSaveTemplateModal()
            "
        >
            <DialogContent
                :show-close-button="false"
                class="max-w-md border-[#315993] bg-[#071633] p-0 text-[#dceaff]"
                data-testid="builder-save-template-dialog"
            >
                <div class="p-5">
                    <DialogTitle class="text-lg font-semibold text-white">
                        Salvar como template
                    </DialogTitle>
                    <DialogDescription
                        class="mt-1 text-sm leading-relaxed text-[#91acd9]"
                    >
                        Crie um modelo reutilizável a partir da versão salva
                        deste funil. O funil original continuará independente.
                    </DialogDescription>

                    <div class="mt-4 grid gap-3">
                        <label class="grid gap-1 text-xs text-[#9bb8e8]">
                            Nome do template
                            <input
                                v-model="templateForm.name"
                                data-testid="builder-template-name"
                                maxlength="120"
                                class="rounded-xl border border-[#2d4f89] bg-[#0a1e45] px-3 py-2.5 text-sm text-white outline-none focus:border-[#4f8fff]"
                            />
                            <span
                                v-if="templateForm.errors.name"
                                class="text-rose-300"
                                >{{ templateForm.errors.name }}</span
                            >
                        </label>
                        <label class="grid gap-1 text-xs text-[#9bb8e8]">
                            Descrição
                            <textarea
                                v-model="templateForm.description"
                                maxlength="500"
                                rows="3"
                                class="resize-none rounded-xl border border-[#2d4f89] bg-[#0a1e45] px-3 py-2.5 text-sm text-white outline-none focus:border-[#4f8fff]"
                                placeholder="Explique quando usar este template"
                            />
                        </label>
                        <label class="grid gap-1 text-xs text-[#9bb8e8]">
                            Categoria
                            <input
                                v-model="templateForm.category"
                                maxlength="60"
                                class="rounded-xl border border-[#2d4f89] bg-[#0a1e45] px-3 py-2.5 text-sm text-white outline-none focus:border-[#4f8fff]"
                                placeholder="Ex.: qualificação"
                            />
                        </label>
                    </div>

                    <div class="mt-5 grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            class="rounded-lg border border-[#315993] px-3 py-2 text-xs font-medium text-[#bcd2fa] transition hover:bg-[#0a2148] disabled:opacity-60"
                            :disabled="templateForm.processing"
                            @click="closeSaveTemplateModal"
                        >
                            Cancelar
                        </button>
                        <button
                            type="button"
                            data-testid="builder-confirm-save-template"
                            class="rounded-lg bg-linear-to-r from-[#1d5fd2] to-[#3f8dff] px-3 py-2 text-xs font-semibold text-white disabled:opacity-60"
                            :disabled="templateForm.processing"
                            @click="submitTemplate"
                        >
                            {{
                                templateForm.processing
                                    ? 'Salvando...'
                                    : 'Salvar template'
                            }}
                        </button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>

<style scoped>
.form-scroll-area {
    scrollbar-width: thin;
    scrollbar-color: transparent transparent;
}

.form-scroll-area::-webkit-scrollbar {
    width: 4px;
}

.form-scroll-area::-webkit-scrollbar-track {
    background: transparent;
}

.form-scroll-area::-webkit-scrollbar-thumb {
    border-radius: 9999px;
    background: transparent;
}

.form-scroll-area:hover {
    scrollbar-color: #3f78cf transparent;
}

.form-scroll-area:hover::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, #4d87ff 0%, #1d5fd2 100%);
}
</style>
