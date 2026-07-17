<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFunnelDesignRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $funnelId = (int) $this->route('funnel')->id;

        return [
            'is_active' => ['sometimes', 'boolean'],
            'custom_domain' => ['nullable', 'string', 'max:255', 'regex:/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\\.)+[a-z]{2,}$/i', 'unique:funnels,custom_domain,' . $funnelId],
            'design_settings' => ['required', 'array'],
            'design_settings.alignment' => ['required', 'string', 'in:left,center'],
            'design_settings.width' => ['required', 'string', 'in:small,medium,large'],
            'design_settings.elementSize' => ['required', 'string', 'in:compact,default,large'],
            'design_settings.spacing' => ['required', 'string', 'in:compact,default,large'],
            'design_settings.radius' => ['required', 'string', 'in:small,medium,large'],
            'design_settings.showLogo' => ['required', 'boolean'],
            'design_settings.showProgress' => ['required', 'boolean'],
            'design_settings.allowBack' => ['required', 'boolean'],
            'design_settings.accentColor' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'design_settings.pageColor' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'design_settings.cardColor' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'design_settings.headingColor' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'design_settings.textColor' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'design_settings.buttonColor' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'design_settings.buttonTextColor' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'design_settings.fontStyle' => ['required', 'string', 'in:modern,clean,serif'],
            'design_settings.logoUrl' => ['nullable', 'string', 'max:1500000'],
            'design_settings.faviconUrl' => ['nullable', 'string', 'max:1500000'],
            'design_settings.seoTitle' => ['nullable', 'string', 'max:120'],
            'design_settings.seoDescription' => ['nullable', 'string', 'max:180'],
            'design_settings.seoImageUrl' => ['nullable', 'string', 'max:1500000'],
            'design_settings.unavailableTitle' => ['nullable', 'string', 'max:120'],
            'design_settings.unavailableDescription' => ['nullable', 'string', 'max:300'],
            'design_settings.expiresAt' => ['nullable', 'date'],
            'design_settings.tokens' => ['sometimes', 'array'],
            'design_settings.tokens.colors' => ['sometimes', 'array'],
            'design_settings.tokens.colors.*' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'design_settings.tokens.typography' => ['sometimes', 'array'],
            'design_settings.tokens.typography.family' => ['sometimes', 'string', 'in:modern,clean,serif'],
            'design_settings.tokens.brand' => ['sometimes', 'array'],
            'design_settings.tokens.brand.logoUrl' => ['sometimes', 'nullable', 'string', 'max:1500000'],
            'design_settings.tokens.brand.showLogo' => ['sometimes', 'boolean'],
            'design_settings.tokens.surfaces' => ['sometimes', 'array'],
            'design_settings.tokens.surfaces.*' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'design_settings.tokens.borders' => ['sometimes', 'array'],
            'design_settings.tokens.borders.*' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'design_settings.tokens.states' => ['sometimes', 'array'],
            'design_settings.tokens.states.success' => ['sometimes', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'design_settings.tokens.states.warning' => ['sometimes', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'design_settings.tokens.states.danger' => ['sometimes', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'design_settings.tokens.states.disabledOpacity' => ['sometimes', 'numeric', 'between:0.1,1'],
            'design_settings.tokens.components' => ['sometimes', 'array'],
            'design_settings.tokens.components.*' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ];
    }
}
