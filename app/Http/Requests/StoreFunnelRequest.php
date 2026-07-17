<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFunnelRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:120'],
            'template_id' => [
                'nullable',
                'integer',
                Rule::exists('funnel_templates', 'id')->where(function ($query): void {
                    $query
                        ->where('is_active', true)
                        ->where(function ($nestedQuery): void {
                            $nestedQuery
                                ->where('is_system', true)
                                ->orWhere('user_id', $this->user()?->id);
                        });
                }),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'target_leads' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
            'stages' => ['required_without:template_id', 'array', 'min:2'],
            'stages.*.name' => ['required_with:stages', 'string', 'max:120'],
            'stages.*.conversion_rate' => ['nullable', 'numeric', 'between:0,100'],
            'stages.*.expected_volume' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'stages.required' => 'Defina ao menos duas etapas para o funil.',
            'stages.min' => 'Defina ao menos duas etapas para o funil.',
            'stages.*.name.required' => 'Cada etapa precisa de um nome.',
            'template_id.exists' => 'O modelo selecionado nao esta disponivel.',
        ];
    }
}
