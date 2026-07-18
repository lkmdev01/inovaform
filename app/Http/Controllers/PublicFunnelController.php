<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitPublicFunnelRequest;
use App\Jobs\ProcessFunnelSubmissionJob;
use App\Models\Funnel;
use App\Models\FunnelStage;
use App\Models\FunnelSubmission;
use App\Support\FunnelDesignTokens;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

class PublicFunnelController extends Controller
{
    public function home(Request $request): BaseResponse
    {
        $appHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        $currentHost = strtolower($request->getHost());

        if ($currentHost !== '' && $currentHost !== $appHost) {
            return $this->showForDomain($request);
        }

        return Inertia::render('Welcome', [
            'canRegister' => Features::enabled(Features::registration()),
        ])->toResponse($request);
    }

    public function show(Request $request, string $slug): BaseResponse
    {
        $funnel = $this->findPublicFunnelBySlug($slug);

        if (! $funnel instanceof Funnel) {
            return $this->renderUnavailable($request, null, 404, 'Funil nao encontrado', 'O funil solicitado nao existe ou nao esta mais disponivel.');
        }

        return $this->renderPublicFunnel($request, $funnel, false);
    }

    public function showForDomain(Request $request): BaseResponse
    {
        $appHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        $currentHost = strtolower($request->getHost());

        if ($currentHost === '' || $currentHost === $appHost) {
            abort(404);
        }

        $funnel = $this->findPublicFunnelByCustomDomain($currentHost);

        if (! $funnel instanceof Funnel) {
            return $this->renderUnavailable($request, null, 404, 'Dominio nao encontrado', 'Nenhum funil foi publicado para este dominio.');
        }

        return $this->renderPublicFunnel($request, $funnel, true);
    }

    private function renderPublicFunnel(Request $request, Funnel $funnel, bool $usingCustomDomain): BaseResponse
    {
        $design = $this->resolveDesignSettings($funnel->design_settings);
        $expiresAt = trim((string) ($design['expiresAt'] ?? ''));

        if (! $funnel->is_active) {
            return $this->renderUnavailable(
                $request,
                $funnel,
                404,
                trim((string) ($design['unavailableTitle'] ?? '')) ?: 'Funil nao publicado',
                trim((string) ($design['unavailableDescription'] ?? '')) ?: 'Este funil ainda nao foi publicado.'
            );
        }

        if ($expiresAt !== '' && now()->greaterThan($expiresAt)) {
            return $this->renderUnavailable(
                $request,
                $funnel,
                410,
                trim((string) ($design['unavailableTitle'] ?? '')) ?: 'Funil expirado',
                trim((string) ($design['unavailableDescription'] ?? '')) ?: 'Este funil expirou e nao esta mais disponivel.'
            );
        }

        return Inertia::render('funnels/PublicShow', [
            'funnel' => [
                'id' => $funnel->id,
                'slug' => $funnel->slug,
                'name' => $funnel->name,
                'description' => $funnel->description,
                'submit_url' => $usingCustomDomain ? '/submit' : "/f/{$funnel->slug}/submit",
                'public_url' => $request->fullUrl(),
                'using_custom_domain' => $usingCustomDomain,
                'custom_domain' => $funnel->custom_domain,
                'design' => $design,
                'stages' => $funnel->stages->map(function (FunnelStage $stage): array {
                    $meta = is_array($stage->meta) ? $stage->meta : [];
                    $builder = is_array($meta['builder'] ?? null) ? $meta['builder'] : [];
                    $blocks = collect($this->mergeLegacyStageFieldsIntoBlocks($builder))
                        ->filter(static fn ($block) => is_array($block) && isset($block['id'], $block['type']) && ($block['type'] ?? null) !== 'charts')
                        ->values()
                        ->map(function (array $block): array {
                            $defaultOptionsDetail = (($block['type'] ?? null) === 'yes_no') ? 'none' : 'checkout';
                            $rawOptionsDetail = isset($block['options_detail']) ? (string) $block['options_detail'] : $defaultOptionsDetail;
                            $optionsDetail = in_array($rawOptionsDetail, ['none', 'checkout', 'arrow', 'points', 'value'], true)
                                ? $rawOptionsDetail
                                : $defaultOptionsDetail;

                            return [
                                'id' => (string) $block['id'],
                                'type' => (string) $block['type'],
                                'label' => isset($block['label']) ? trim((string) $block['label']) : '',
                                'placeholder' => isset($block['placeholder']) ? (string) $block['placeholder'] : null,
                                'required' => (bool) ($block['required'] ?? false),
                                'options' => collect($block['options'] ?? [])
                                    ->map(static fn ($option): string => (string) $option)
                                    ->values()
                                    ->all(),
                                'option_items' => collect($block['option_items'] ?? [])
                                    ->filter(static fn ($item): bool => is_array($item))
                                    ->map(static function (array $item): array {
                                        return [
                                            'id' => (string) ($item['id'] ?? ''),
                                            'label' => isset($item['label']) ? trim((string) $item['label']) : '',
                                            'subtitle' => isset($item['subtitle']) ? trim((string) $item['subtitle']) : '',
                                            'description' => isset($item['description']) ? trim((string) $item['description']) : '',
                                            'rating' => isset($item['rating']) ? (float) $item['rating'] : null,
                                            'points' => (float) ($item['points'] ?? 0),
                                            'value' => (string) ($item['value'] ?? ''),
                                            'destination' => (string) ($item['destination'] ?? 'next_stage'),
                                            'image_url' => (string) ($item['image_url'] ?? ''),
                                        ];
                                    })
                                    ->values()
                                    ->all(),
                                'options_intro_type' => isset($block['options_intro_type']) ? (string) $block['options_intro_type'] : 'text',
                                'options_intro_title' => isset($block['options_intro_title']) ? (string) $block['options_intro_title'] : null,
                                'options_intro_description' => isset($block['options_intro_description']) ? (string) $block['options_intro_description'] : null,
                                'options_required_selection' => isset($block['options_required_selection']) ? (bool) $block['options_required_selection'] : true,
                                'options_allow_multiple' => isset($block['options_allow_multiple'])
                                    ? (bool) $block['options_allow_multiple']
                                    : (($block['type'] ?? null) === 'multiple_choice'),
                                'options_disable_auto_follow' => isset($block['options_disable_auto_follow']) ? (bool) $block['options_disable_auto_follow'] : false,
                                'options_style' => isset($block['options_style']) ? (string) $block['options_style'] : 'simple',
                                'options_transparent_image' => isset($block['options_transparent_image']) ? (bool) $block['options_transparent_image'] : true,
                                'options_layout' => isset($block['options_layout']) ? (string) $block['options_layout'] : 'grid_2',
                                'options_orientation' => isset($block['options_orientation']) ? (string) $block['options_orientation'] : 'vertical',
                                'options_image_ratio' => isset($block['options_image_ratio']) ? (string) $block['options_image_ratio'] : '1:1',
                                'options_disposition' => isset($block['options_disposition']) ? (string) $block['options_disposition'] : 'image_text',
                                'options_detail' => $optionsDetail,
                                'options_detail_position' => isset($block['options_detail_position']) ? (string) $block['options_detail_position'] : 'start',
                                'options_border_size' => isset($block['options_border_size']) ? (string) $block['options_border_size'] : 'small',
                                'options_shadow' => isset($block['options_shadow']) ? (string) $block['options_shadow'] : 'none',
                                'options_spacing' => isset($block['options_spacing']) ? (string) $block['options_spacing'] : 'simple',
                                'testimonials_layout' => ($block['type'] ?? null) === 'testimonials'
                                    ? (string) ($block['testimonials_layout'] ?? 'list')
                                    : null,
                                'price_title' => ($block['type'] ?? null) === 'price'
                                    ? (string) ($block['price_title'] ?? '')
                                    : null,
                                'price_prefix' => ($block['type'] ?? null) === 'price'
                                    ? (string) ($block['price_prefix'] ?? '')
                                    : null,
                                'price_value' => ($block['type'] ?? null) === 'price'
                                    ? (string) ($block['price_value'] ?? '')
                                    : null,
                                'price_suffix' => ($block['type'] ?? null) === 'price'
                                    ? (string) ($block['price_suffix'] ?? '')
                                    : null,
                                'price_badge_text' => ($block['type'] ?? null) === 'price'
                                    ? (string) ($block['price_badge_text'] ?? '')
                                    : null,
                                'price_mode' => ($block['type'] ?? null) === 'price'
                                    ? (string) ($block['price_mode'] ?? 'illustrative')
                                    : null,
                                'price_layout' => ($block['type'] ?? null) === 'price'
                                    ? (string) ($block['price_layout'] ?? 'horizontal')
                                    : null,
                                'price_style' => ($block['type'] ?? null) === 'price'
                                    ? (string) ($block['price_style'] ?? 'theme')
                                    : null,
                                'price_link' => ($block['type'] ?? null) === 'price'
                                    ? (string) ($block['price_link'] ?? '')
                                    : null,
                                'carousel_layout' => ($block['type'] ?? null) === 'carousel'
                                    ? (string) ($block['carousel_layout'] ?? 'image_text')
                                    : null,
                                'carousel_pagination' => ($block['type'] ?? null) === 'carousel'
                                    ? (bool) ($block['carousel_pagination'] ?? true)
                                    : null,
                                'carousel_autoplay' => ($block['type'] ?? null) === 'carousel'
                                    ? (bool) ($block['carousel_autoplay'] ?? false)
                                    : null,
                                'carousel_autoplay_seconds' => ($block['type'] ?? null) === 'carousel'
                                    ? max(1, min(60, (int) ($block['carousel_autoplay_seconds'] ?? 3)))
                                    : null,
                                'carousel_border_type' => ($block['type'] ?? null) === 'carousel'
                                    ? (string) ($block['carousel_border_type'] ?? 'none')
                                    : null,
                                'video_ratio' => ($block['type'] ?? null) === 'video'
                                    ? (string) ($block['video_ratio'] ?? '16:9')
                                    : null,
                                'image_ratio' => ($block['type'] ?? null) === 'image'
                                    ? (string) ($block['image_ratio'] ?? 'auto')
                                    : null,
                                'image_fit' => ($block['type'] ?? null) === 'image'
                                    ? (string) ($block['image_fit'] ?? 'cover')
                                    : null,
                                'image_radius' => ($block['type'] ?? null) === 'image'
                                    ? (string) ($block['image_radius'] ?? 'medium')
                                    : null,
                                'image_frame' => ($block['type'] ?? null) === 'image'
                                    ? (string) ($block['image_frame'] ?? 'subtle')
                                    : null,
                                'audio_sender' => ($block['type'] ?? null) === 'audio'
                                    ? (string) ($block['audio_sender'] ?? '')
                                    : null,
                                'audio_src' => ($block['type'] ?? null) === 'audio'
                                    ? (string) ($block['audio_src'] ?? '')
                                    : null,
                                'audio_avatar_url' => ($block['type'] ?? null) === 'audio'
                                    ? (string) ($block['audio_avatar_url'] ?? '')
                                    : null,
                                'audio_model' => ($block['type'] ?? null) === 'audio'
                                    ? (string) ($block['audio_model'] ?? 'whatsapp')
                                    : null,
                                'audio_theme' => ($block['type'] ?? null) === 'audio'
                                    ? (string) ($block['audio_theme'] ?? 'light')
                                    : null,
                                'attention_style' => ($block['type'] ?? null) === 'attention'
                                    ? (string) ($block['attention_style'] ?? 'red')
                                    : null,
                                'attention_emphasis' => ($block['type'] ?? null) === 'attention'
                                    ? (bool) ($block['attention_emphasis'] ?? false)
                                    : null,
                                'attention_padding' => ($block['type'] ?? null) === 'attention'
                                    ? (string) ($block['attention_padding'] ?? 'default')
                                    : null,
                                'notification_title' => ($block['type'] ?? null) === 'notification'
                                    ? (string) ($block['notification_title'] ?? '')
                                    : null,
                                'notification_description' => ($block['type'] ?? null) === 'notification'
                                    ? (string) ($block['notification_description'] ?? '')
                                    : null,
                                'notification_avatar_url' => ($block['type'] ?? null) === 'notification'
                                    ? (string) ($block['notification_avatar_url'] ?? '')
                                    : null,
                                'notification_position' => ($block['type'] ?? null) === 'notification'
                                    ? (string) ($block['notification_position'] ?? 'default')
                                    : null,
                                'notification_duration_seconds' => ($block['type'] ?? null) === 'notification'
                                    ? (int) ($block['notification_duration_seconds'] ?? 5)
                                    : null,
                                'notification_interval_seconds' => ($block['type'] ?? null) === 'notification'
                                    ? (int) ($block['notification_interval_seconds'] ?? 2)
                                    : null,
                                'notification_style' => ($block['type'] ?? null) === 'notification'
                                    ? (string) ($block['notification_style'] ?? 'white')
                                    : null,
                                'notification_size' => ($block['type'] ?? null) === 'notification'
                                    ? (string) ($block['notification_size'] ?? 'default')
                                    : null,
                                'notification_variant' => ($block['type'] ?? null) === 'notification'
                                    ? (string) ($block['notification_variant'] ?? 'social')
                                    : null,
                                'faq_first_active' => ($block['type'] ?? null) === 'faq'
                                    ? (bool) ($block['faq_first_active'] ?? true)
                                    : null,
                                'faq_detail' => ($block['type'] ?? null) === 'faq'
                                    ? (string) ($block['faq_detail'] ?? 'arrow')
                                    : null,
                                'notification_variations' => ($block['type'] ?? null) === 'notification'
                                    ? collect($block['notification_variations'] ?? [])
                                        ->filter(static fn ($variation): bool => is_array($variation))
                                        ->map(static fn (array $variation): array => [
                                            'id' => (string) ($variation['id'] ?? ''),
                                            'value1' => (string) ($variation['value1'] ?? $variation['first'] ?? ''),
                                            'value2' => (string) ($variation['value2'] ?? $variation['second'] ?? ''),
                                            'value3' => (string) ($variation['value3'] ?? $variation['third'] ?? ''),
                                            'value4' => (string) ($variation['value4'] ?? $variation['fourth'] ?? ''),
                                        ])
                                        ->values()
                                        ->all()
                                    : null,
                                'phone_mask' => ($block['type'] ?? null) === 'phone'
                                    ? (string) ($block['phone_mask'] ?? 'br')
                                    : null,
                                'number_mask' => ($block['type'] ?? null) === 'number'
                                    ? (string) ($block['number_mask'] ?? 'decimal')
                                    : null,
                                'height_mode' => ($block['type'] ?? null) === 'height'
                                    ? (string) ($block['height_mode'] ?? 'ruler')
                                    : null,
                                'weight_mode' => ($block['type'] ?? null) === 'weight'
                                    ? (string) ($block['weight_mode'] ?? 'ruler')
                                    : null,
                                'button_action' => isset($block['button_action']) ? (string) $block['button_action'] : 'next_stage',
                                'button_target_stage_order' => isset($block['button_target_stage_order']) ? (string) $block['button_target_stage_order'] : 'next',
                                'button_link' => isset($block['button_link']) ? (string) $block['button_link'] : null,
                                'button_open_new_tab' => isset($block['button_open_new_tab']) ? (bool) $block['button_open_new_tab'] : true,
                                'button_color_style' => isset($block['button_color_style']) ? (string) $block['button_color_style'] : 'theme',
                                'button_animated' => isset($block['button_animated']) ? (bool) $block['button_animated'] : false,
                                'button_elevated' => isset($block['button_elevated']) ? (bool) $block['button_elevated'] : false,
                                'button_sticky_footer' => isset($block['button_sticky_footer']) ? (bool) $block['button_sticky_footer'] : false,
                                'label_style' => isset($block['label_style']) ? (string) $block['label_style'] : 'default',
                                'text_align' => isset($block['text_align']) ? (string) $block['text_align'] : 'text-left',
                                'width_percent' => isset($block['width_percent']) ? (float) $block['width_percent'] : 100,
                                'align_horizontal' => isset($block['align_horizontal']) ? (string) $block['align_horizontal'] : 'start',
                                'align_vertical' => isset($block['align_vertical']) ? (string) $block['align_vertical'] : 'start',
                                'show_after_seconds' => isset($block['show_after_seconds']) ? (int) $block['show_after_seconds'] : 0,
                                'display_rule_mode' => isset($block['display_rule_mode']) ? (string) $block['display_rule_mode'] : 'all',
                                'display_rules' => $this->normalizeDisplayRules($block['display_rules'] ?? []),
                                'display_rule_groups' => $this->normalizeDisplayRuleGroups(
                                    $block['display_rule_groups'] ?? [],
                                    $block['display_rules'] ?? [],
                                    isset($block['display_rule_mode']) ? (string) $block['display_rule_mode'] : 'all',
                                ),
                                'loading_start_seconds' => ($block['type'] ?? null) === 'loading'
                                    ? (int) ($block['loading_start_seconds'] ?? 0)
                                    : null,
                                'loading_duration_seconds' => ($block['type'] ?? null) === 'loading'
                                    ? (int) ($block['loading_duration_seconds'] ?? 5)
                                    : null,
                                'loading_navigation_action' => ($block['type'] ?? null) === 'loading'
                                    ? (string) ($block['loading_navigation_action'] ?? 'none')
                                    : null,
                                'loading_target_stage_order' => ($block['type'] ?? null) === 'loading'
                                    ? (string) ($block['loading_target_stage_order'] ?? 'next')
                                    : null,
                                'loading_link' => ($block['type'] ?? null) === 'loading'
                                    ? (string) ($block['loading_link'] ?? '')
                                    : null,
                                'loading_show_title' => ($block['type'] ?? null) === 'loading'
                                    ? (bool) ($block['loading_show_title'] ?? true)
                                    : null,
                                'loading_show_progress' => ($block['type'] ?? null) === 'loading'
                                    ? (bool) ($block['loading_show_progress'] ?? true)
                                    : null,
                                'timer_seconds' => ($block['type'] ?? null) === 'timer'
                                    ? (int) ($block['timer_seconds'] ?? 20)
                                    : null,
                                'timer_text' => ($block['type'] ?? null) === 'timer'
                                    ? (string) ($block['timer_text'] ?? '')
                                    : null,
                                'timer_style' => ($block['type'] ?? null) === 'timer'
                                    ? (string) ($block['timer_style'] ?? 'red')
                                    : null,
                                'level_title' => ($block['type'] ?? null) === 'level'
                                    ? (string) ($block['level_title'] ?? '')
                                    : null,
                                'level_subtitle' => ($block['type'] ?? null) === 'level'
                                    ? (string) ($block['level_subtitle'] ?? '')
                                    : null,
                                'level_percentage' => ($block['type'] ?? null) === 'level'
                                    ? (float) ($block['level_percentage'] ?? 0)
                                    : null,
                                'level_indicator_text' => ($block['type'] ?? null) === 'level'
                                    ? (string) ($block['level_indicator_text'] ?? '')
                                    : null,
                                'level_legends' => ($block['type'] ?? null) === 'level'
                                    ? (string) ($block['level_legends'] ?? '')
                                    : null,
                                'level_show_meter' => ($block['type'] ?? null) === 'level'
                                    ? (bool) ($block['level_show_meter'] ?? true)
                                    : null,
                                'level_show_progress' => ($block['type'] ?? null) === 'level'
                                    ? (bool) ($block['level_show_progress'] ?? true)
                                    : null,
                                'level_type' => ($block['type'] ?? null) === 'level'
                                    ? (string) ($block['level_type'] ?? 'line')
                                    : null,
                                'level_color' => ($block['type'] ?? null) === 'level'
                                    ? (string) ($block['level_color'] ?? 'theme')
                                    : null,
                            ];
                        })
                        ->all();

                    return [
                        'id' => $stage->id,
                        'name' => $stage->name,
                        'header' => [
                            'show_logo' => (bool) ($meta['header']['show_logo'] ?? true),
                            'show_progress' => (bool) ($meta['header']['show_progress'] ?? true),
                            'allow_back' => (bool) ($meta['header']['allow_back'] ?? true),
                        ],
                        'blocks' => $blocks,
                    ];
                })->all(),
            ],
        ])->toResponse($request);
    }

    /**
     * @param  array<string, mixed>  $builder
     * @return array<int, array<string, mixed>>
     */
    protected function mergeLegacyStageFieldsIntoBlocks(array $builder): array
    {
        $blocks = collect($builder['blocks'] ?? [])
            ->filter(static fn ($block): bool => is_array($block))
            ->values()
            ->all();

        $title = trim((string) ($builder['title'] ?? ''));
        $subtitle = trim((string) ($builder['subtitle'] ?? ''));
        $buttonText = trim((string) ($builder['button_text'] ?? ''));

        if ($title !== '' || $subtitle !== '') {
            $contentParts = [];

            if ($title !== '') {
                $contentParts[] = '<h1>'.e($title).'</h1>';
            }

            if ($subtitle !== '') {
                $contentParts[] = '<p>'.e($subtitle).'</p>';
            }

            array_unshift($blocks, [
                'id' => 'legacy-stage-copy',
                'type' => 'content_text',
                'label' => null,
                'placeholder' => implode('', $contentParts),
                'required' => false,
            ]);
        }

        if ($buttonText !== '') {
            $blocks[] = [
                'id' => 'legacy-stage-button',
                'type' => 'button',
                'label' => $buttonText,
                'required' => false,
                'button_action' => (string) ($builder['stage_button_action'] ?? 'next_stage'),
                'button_target_stage_order' => isset($builder['stage_button_target_stage_order']) ? (string) $builder['stage_button_target_stage_order'] : 'next',
                'button_link' => isset($builder['stage_button_link']) ? (string) $builder['stage_button_link'] : '',
                'button_open_new_tab' => (bool) ($builder['stage_button_open_new_tab'] ?? false),
                'button_color_style' => (string) ($builder['stage_button_color_style'] ?? 'theme'),
                'button_animated' => (bool) ($builder['stage_button_animated'] ?? false),
                'button_elevated' => (bool) ($builder['stage_button_elevated'] ?? false),
                'button_sticky_footer' => (bool) ($builder['stage_button_sticky_footer'] ?? false),
            ];
        }

        return $blocks;
    }

    public function submit(SubmitPublicFunnelRequest $request, string $slug): RedirectResponse
    {
        $funnel = $this->findPublicFunnelBySlug($slug);

        abort_unless($funnel instanceof Funnel && $this->isFunnelCurrentlyAvailable($funnel), 404);

        return $this->handleSubmission($request, $funnel);
    }

    public function submitForDomain(SubmitPublicFunnelRequest $request): RedirectResponse
    {
        $funnel = $this->findPublicFunnelByCustomDomain(strtolower($request->getHost()));

        abort_unless($funnel instanceof Funnel && $this->isFunnelCurrentlyAvailable($funnel), 404);

        return $this->handleSubmission($request, $funnel);
    }

    private function handleSubmission(SubmitPublicFunnelRequest $request, Funnel $funnel): RedirectResponse
    {
        $funnel->load([
            'stages' => static fn ($query) => $query->orderBy('stage_order'),
        ]);

        $validated = $request->validated();
        $answersByStage = collect($validated['answers'] ?? [])
            ->keyBy(static fn (array $stageAnswer): int => (int) ($stageAnswer['stage_id'] ?? 0));
        $storedAnswers = [];
        $answersByBlockId = [];
        $score = 0.0;
        $scoreBreakdown = [];

        $leadName = null;
        $leadEmail = null;
        $leadPhone = null;

        foreach ($funnel->stages as $stage) {
            $stageId = $stage->id;
            $stageAnswer = $answersByStage->get($stageId, ['blocks' => []]);

            $meta = is_array($stage->meta) ? $stage->meta : [];
            $builder = is_array($meta['builder'] ?? null) ? $meta['builder'] : [];
            $stageBlocks = collect($builder['blocks'] ?? [])->filter(static fn ($block) => is_array($block));
            $stageBlocksById = $stageBlocks->keyBy(static fn (array $block): string => (string) ($block['id'] ?? ''));
            $inputBlocks = collect($stageAnswer['blocks'] ?? [])->filter(static fn ($block) => is_array($block));
            $inputByBlockId = $inputBlocks->keyBy(static fn (array $block): string => (string) ($block['block_id'] ?? ''));

            foreach ($stageBlocks as $block) {
                $blockId = (string) ($block['id'] ?? '');
                $blockType = (string) ($block['type'] ?? '');

                if ($blockId === '') {
                    continue;
                }

                if (! $this->isAnswerableBlock($blockType)) {
                    continue;
                }

                if (! $this->isBlockVisibleForSubmission($block, $answersByBlockId)) {
                    continue;
                }

                $inputBlock = $inputByBlockId->get($blockId, []);
                $value = $inputBlock['value'] ?? null;
                $isRequired = $this->isOptionsComponentType($blockType)
                    ? (bool) ($block['options_required_selection'] ?? true)
                    : (bool) ($block['required'] ?? false);

                if ($isRequired && $this->isEmptyValue($value)) {
                    $blockLabel = trim((string) ($block['label'] ?? '')) ?: 'obrigatório';

                    return back()
                        ->withErrors([
                            'answers' => "Preencha o campo obrigatório: {$blockLabel}.",
                        ])
                        ->withInput();
                }

                if ($this->isEmptyValue($value)) {
                    continue;
                }

                if ($blockType === 'email' && ! $this->isValidEmailAnswer($value)) {
                    return back()
                        ->withErrors([
                            'answers' => 'Informe um endereço de e-mail válido.',
                        ])
                        ->withInput();
                }

                if ($blockType === 'email' && is_string($value)) {
                    $leadEmail = trim($value);
                }

                if (($block['type'] ?? null) === 'phone' && is_string($value) && $value !== '') {
                    $leadPhone = $value;
                }

                if (
                    $leadName === null
                    && ($block['type'] ?? null) === 'text'
                    && is_string($value)
                    && $value !== ''
                    && str_contains(strtolower((string) ($block['label'] ?? '')), 'nome')
                ) {
                    $leadName = $value;
                }

                $storedAnswers[] = [
                    'funnel_stage_id' => $stageId,
                    'block_id' => $blockId,
                    'block_type' => $blockType,
                    'block_label' => isset($stageBlocksById[$blockId]['label']) ? trim((string) $stageBlocksById[$blockId]['label']) : '',
                    'value' => $this->normalizeValue($value),
                ];

                $blockScore = $this->scoreForAnswer($block, $value);

                if ($blockScore !== 0.0) {
                    $score += $blockScore;
                    $scoreBreakdown[$blockId] = $blockScore;
                }

                $answersByBlockId[$blockId] = $value;
            }
        }

        $submission = DB::transaction(function () use ($request, $funnel, $storedAnswers, $leadName, $leadEmail, $leadPhone, $score, $scoreBreakdown): FunnelSubmission {
            $submission = FunnelSubmission::query()->create([
                'funnel_id' => $funnel->id,
                'status' => 'new',
                'lead_name' => $leadName,
                'lead_email' => $leadEmail,
                'lead_phone' => $leadPhone,
                'session_id' => $request->session()->getId(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'meta' => [
                    'score' => $score,
                    'score_breakdown' => $scoreBreakdown,
                    'timeline' => [[
                        'id' => (string) str()->uuid(),
                        'type' => 'submitted',
                        'source' => 'public_funnel',
                        'actor_name' => 'Sistema',
                        'title' => 'Lead recebido',
                        'description' => 'Envio inicial do funil.',
                        'created_at' => now()->toISOString(),
                    ]],
                ],
                'submitted_at' => now(),
            ]);

            $submission->answers()->createMany($storedAnswers);

            return $submission;
        });

        ProcessFunnelSubmissionJob::dispatch($submission->id);

        Log::info('funnel_submission.created', [
            'submission_id' => $submission->id,
            'funnel_id' => $funnel->id,
            'answers_count' => count($storedAnswers),
            'score' => $score,
        ]);

        return back()->with([
            'status' => 'funnel-submitted',
            'completion_lead' => [
                'name' => $submission->lead_name,
                'email' => $submission->lead_email,
                'phone' => $submission->lead_phone,
            ],
        ]);
    }

    private function isValidEmailAnswer(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        $email = trim($value);

        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false
            && preg_match('/^[^@\s]+@[^@\s]+\.[a-z]{2,}$/i', $email) === 1;
    }

    /**
     * @param  array<string, mixed>  $block
     */
    private function scoreForAnswer(array $block, mixed $value): float
    {
        if (! $this->isOptionsComponentType((string) ($block['type'] ?? ''))) {
            return 0.0;
        }

        $selectedLabels = collect(is_array($value) ? $value : [$value])
            ->map(static fn (mixed $selected): string => trim((string) $selected))
            ->filter(static fn (string $selected): bool => $selected !== '')
            ->unique()
            ->values();

        $pointsByLabel = collect(is_array($block['option_items'] ?? null) ? $block['option_items'] : [])
            ->filter(static fn (mixed $item): bool => is_array($item))
            ->mapWithKeys(static fn (array $item): array => [
                trim((string) ($item['label'] ?? '')) => is_numeric($item['points'] ?? null) ? (float) $item['points'] : 0.0,
            ]);

        return $selectedLabels->sum(static fn (string $label): float => (float) $pointsByLabel->get($label, 0.0));
    }

    /**
     * @return array<int, array{id:string, source_block_id:string, operator:string, value:string}>
     */
    private function normalizeDisplayRules(mixed $rules): array
    {
        return collect(is_array($rules) ? $rules : [])
            ->map(function (mixed $rule): ?array {
                if (is_string($rule) && trim($rule) !== '') {
                    $trimmed = trim($rule);

                    if (preg_match('/^(filled|empty):(.+)$/i', $trimmed, $presenceMatch) === 1) {
                        return [
                            'id' => (string) str()->uuid(),
                            'source_block_id' => trim((string) $presenceMatch[2]),
                            'operator' => strtolower((string) $presenceMatch[1]),
                            'value' => '',
                        ];
                    }

                    if (preg_match('/^([^!=:]+)\s*(=|!=)\s*(.+)$/', $trimmed, $comparisonMatch) === 1) {
                        return [
                            'id' => (string) str()->uuid(),
                            'source_block_id' => trim((string) $comparisonMatch[1]),
                            'operator' => $comparisonMatch[2] === '!=' ? 'not_equals' : 'equals',
                            'value' => trim((string) $comparisonMatch[3]),
                        ];
                    }

                    return null;
                }

                if (! is_array($rule)) {
                    return null;
                }

                $sourceBlockId = trim((string) ($rule['source_block_id'] ?? ''));
                $operator = trim((string) ($rule['operator'] ?? ''));
                $value = trim((string) ($rule['value'] ?? ''));

                if ($sourceBlockId === '' || ! in_array($operator, ['filled', 'empty', 'equals', 'not_equals', 'contains_any', 'contains_all'], true)) {
                    return null;
                }

                if (in_array($operator, ['equals', 'not_equals', 'contains_any', 'contains_all'], true) && $value === '') {
                    return null;
                }

                return [
                    'id' => trim((string) ($rule['id'] ?? '')) ?: (string) str()->uuid(),
                    'source_block_id' => $sourceBlockId,
                    'operator' => $operator,
                    'value' => $value,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id:string, mode:string, rules:array<int, array{id:string, source_block_id:string, operator:string, value:string}>}>
     */
    private function normalizeDisplayRuleGroups(mixed $groups, mixed $legacyRules, string $fallbackMode): array
    {
        $normalizedGroups = collect(is_array($groups) ? $groups : [])
            ->filter(static fn ($group): bool => is_array($group))
            ->map(function (array $group): ?array {
                $rules = $this->normalizeDisplayRules($group['rules'] ?? []);

                if ($rules === []) {
                    return null;
                }

                $mode = trim((string) ($group['mode'] ?? 'all'));

                return [
                    'id' => trim((string) ($group['id'] ?? '')) ?: (string) str()->uuid(),
                    'mode' => in_array($mode, ['all', 'any'], true) ? $mode : 'all',
                    'rules' => $rules,
                ];
            })
            ->filter()
            ->values()
            ->all();

        if ($normalizedGroups !== []) {
            return $normalizedGroups;
        }

        $legacyNormalized = $this->normalizeDisplayRules($legacyRules);

        if ($legacyNormalized === []) {
            return [];
        }

        return [[
            'id' => (string) str()->uuid(),
            'mode' => in_array($fallbackMode, ['all', 'any'], true) ? $fallbackMode : 'all',
            'rules' => $legacyNormalized,
        ]];
    }

    private function normalizeValue(mixed $value): array
    {
        if (is_array($value)) {
            return array_values($value);
        }

        if ($value === null) {
            return [];
        }

        return [(string) $value];
    }

    /**
     * @param  array<string, mixed>  $block
     * @param  array<string, mixed>  $answersByBlockId
     */
    private function isBlockVisibleForSubmission(array $block, array $answersByBlockId): bool
    {
        $groups = $this->normalizeDisplayRuleGroups(
            $block['display_rule_groups'] ?? [],
            $block['display_rules'] ?? [],
            isset($block['display_rule_mode']) ? (string) $block['display_rule_mode'] : 'all',
        );

        if ($groups === []) {
            return true;
        }

        $blockMode = isset($block['display_rule_mode']) && (string) $block['display_rule_mode'] === 'any' ? 'any' : 'all';

        if ($blockMode === 'any') {
            foreach ($groups as $group) {
                $groupMode = (($group['mode'] ?? 'all') === 'any') ? 'any' : 'all';

                if ($groupMode === 'any') {
                    foreach (($group['rules'] ?? []) as $rule) {
                        if ($this->evaluateSubmissionDisplayRule($rule, $answersByBlockId)) {
                            return true;
                        }
                    }

                    continue;
                }

                $allMatched = true;

                foreach (($group['rules'] ?? []) as $rule) {
                    if (! $this->evaluateSubmissionDisplayRule($rule, $answersByBlockId)) {
                        $allMatched = false;
                        break;
                    }
                }

                if ($allMatched) {
                    return true;
                }
            }

            return false;
        }

        foreach ($groups as $group) {
            $groupMode = (($group['mode'] ?? 'all') === 'any') ? 'any' : 'all';

            if ($groupMode === 'any') {
                $matched = false;

                foreach (($group['rules'] ?? []) as $rule) {
                    if ($this->evaluateSubmissionDisplayRule($rule, $answersByBlockId)) {
                        $matched = true;
                        break;
                    }
                }

                if (! $matched) {
                    return false;
                }

                continue;
            }

            foreach (($group['rules'] ?? []) as $rule) {
                if (! $this->evaluateSubmissionDisplayRule($rule, $answersByBlockId)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param  array{id?:string, source_block_id:string, operator:string, value:string}  $rule
     * @param  array<string, mixed>  $answersByBlockId
     */
    private function evaluateSubmissionDisplayRule(array $rule, array $answersByBlockId): bool
    {
        $blockId = trim((string) ($rule['source_block_id'] ?? ''));

        if ($blockId === '') {
            return true;
        }

        $value = $answersByBlockId[$blockId] ?? null;

        if (($rule['operator'] ?? '') === 'filled' || ($rule['operator'] ?? '') === 'empty') {
            $filled = ! $this->isEmptyValue($value);

            return ($rule['operator'] ?? '') === 'filled' ? $filled : ! $filled;
        }

        $expectedValues = collect(explode('|', trim((string) ($rule['value'] ?? ''))))
            ->map(static fn (string $item): string => trim($item))
            ->filter(static fn (string $item): bool => $item !== '')
            ->values()
            ->all();

        if ($expectedValues === []) {
            return true;
        }

        $actualValues = collect($this->normalizeValue($value))
            ->map(static fn (mixed $item): string => trim((string) $item))
            ->filter(static fn (string $item): bool => $item !== '')
            ->values()
            ->all();

        $anyMatch = collect($expectedValues)->contains(static fn (string $item): bool => in_array($item, $actualValues, true));
        $allMatch = collect($expectedValues)->every(static fn (string $item): bool => in_array($item, $actualValues, true));

        return match ((string) ($rule['operator'] ?? '')) {
            'contains_any' => $anyMatch,
            'contains_all' => $allMatch,
            'not_equals' => ! $anyMatch,
            'equals' => $anyMatch,
            default => false,
        };
    }

    private function isOptionsComponentType(string $type): bool
    {
        return in_array($type, ['options', 'multiple_choice', 'single_choice', 'yes_no'], true);
    }

    private function isAnswerableBlock(string $type): bool
    {
        return in_array($type, [
            'text',
            'email',
            'phone',
            'number',
            'textarea',
            'date',
            'height',
            'address',
            'weight',
            'options',
            'multiple_choice',
            'single_choice',
            'yes_no',
            'video_response',
        ], true);
    }

    private function isEmptyValue(mixed $value): bool
    {
        if (is_array($value)) {
            return count($value) === 0;
        }

        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        return false;
    }

    private function findPublicFunnelBySlug(string $slug): ?Funnel
    {
        return Funnel::query()
            ->where('slug', $slug)
            ->with([
                'stages' => static fn ($query) => $query->orderBy('stage_order'),
            ])
            ->first();
    }

    private function findPublicFunnelByCustomDomain(string $host): ?Funnel
    {
        return Funnel::query()
            ->where('custom_domain', $host)
            ->with([
                'stages' => static fn ($query) => $query->orderBy('stage_order'),
            ])
            ->first();
    }

    private function isFunnelCurrentlyAvailable(Funnel $funnel): bool
    {
        if (! $funnel->is_active) {
            return false;
        }

        $design = $this->resolveDesignSettings($funnel->design_settings);
        $expiresAt = trim((string) ($design['expiresAt'] ?? ''));

        return ! ($expiresAt !== '' && now()->greaterThan($expiresAt));
    }

    private function renderUnavailable(Request $request, ?Funnel $funnel, int $status, string $title, string $description): BaseResponse
    {
        $design = $this->resolveDesignSettings($funnel?->design_settings);

        return Inertia::render('funnels/PublicUnavailable', [
            'statusCode' => $status,
            'title' => $title,
            'description' => $description,
            'homeUrl' => '/',
            'design' => $design,
            'funnel' => $funnel !== null ? [
                'name' => $funnel->name,
                'slug' => $funnel->slug,
                'custom_domain' => $funnel->custom_domain,
            ] : null,
        ])->toResponse($request)->setStatusCode($status);
    }

    /**
     * @param  array<string, mixed>|null  $settings
     * @return array<string, mixed>
     */
    private function resolveDesignSettings(?array $settings): array
    {
        $resolved = array_merge([
            'alignment' => 'center',
            'width' => 'small',
            'elementSize' => 'default',
            'spacing' => 'default',
            'radius' => 'medium',
            'showLogo' => true,
            'showProgress' => true,
            'allowBack' => true,
            'accentColor' => '#3d8bff',
            'pageColor' => '#050d22',
            'cardColor' => '#0b1a3a',
            'headingColor' => '#f8fbff',
            'textColor' => '#a8bfeb',
            'buttonColor' => '#12356f',
            'buttonTextColor' => '#e8f2ff',
            'fontStyle' => 'modern',
            'logoUrl' => '',
            'faviconUrl' => '',
            'seoTitle' => '',
            'seoDescription' => '',
            'seoImageUrl' => '',
            'unavailableTitle' => 'Funil indisponivel',
            'unavailableDescription' => 'Este funil nao esta disponivel no momento.',
            'expiresAt' => null,
            'completion_page' => [
                'enabled' => false,
                'title' => 'Resposta enviada',
                'description' => 'Obrigado. Recebemos suas respostas e em breve entraremos em contato.',
                'image_url' => '',
                'primary_button_text' => 'Voltar ao inicio',
                'primary_button_url' => '/',
                'primary_button_new_tab' => false,
                'secondary_button_text' => '',
                'secondary_button_url' => '',
                'secondary_button_new_tab' => false,
                'auto_redirect_url' => '',
                'auto_redirect_delay_seconds' => 0,
            ],
        ], is_array($settings) ? $settings : []);

        $resolved['tokens'] = FunnelDesignTokens::resolve($resolved);

        return $resolved;
    }
}
