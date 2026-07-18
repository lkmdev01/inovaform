<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFunnelSettingsRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $funnelId = (int) $this->route('funnel')->id;

        return [
            'is_active' => ['required', 'boolean'],
            'custom_domain' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\\.)+[a-z]{2,}$/i',
                Rule::unique('funnels', 'custom_domain')->ignore($funnelId),
            ],
            'logo_url' => ['nullable', 'string', 'max:1500000'],
            'favicon_url' => ['nullable', 'string', 'max:1500000'],
            'seo_title' => ['nullable', 'string', 'max:120'],
            'seo_description' => ['nullable', 'string', 'max:180'],
            'seo_image_url' => ['nullable', 'string', 'max:1500000'],
            'expires_at' => ['nullable', 'date'],
            'unavailable_title' => ['nullable', 'string', 'max:120'],
            'unavailable_description' => ['nullable', 'string', 'max:300'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'custom_domain.regex' => 'Informe somente o domínio, sem http, https ou caminhos.',
            'custom_domain.unique' => 'Este domínio já está vinculado a outro funil.',
            'seo_title.max' => 'O título SEO pode ter no máximo 120 caracteres.',
            'seo_description.max' => 'A descrição SEO pode ter no máximo 180 caracteres.',
            'expires_at.date' => 'Informe uma data de expiração válida.',
        ];
    }
}
