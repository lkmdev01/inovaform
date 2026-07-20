<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateAiFunnelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:120'],
            'goal_type' => ['required', 'string', Rule::in(['lead_capture', 'qualification', 'diagnosis', 'quote', 'application', 'quiz'])],
            'offer' => ['required', 'string', 'min:3', 'max:240'],
            'pain_point' => ['nullable', 'string', 'max:300'],
            'desired_action' => ['required', 'string', Rule::in(['contact', 'whatsapp', 'schedule', 'purchase', 'receive_result'])],
            'prompt' => ['nullable', 'string', 'max:1000'],
            'audience' => ['nullable', 'string', 'max:240'],
            'tone' => ['required', 'string', Rule::in(['direto', 'consultivo', 'educativo', 'premium'])],
            'target_leads' => ['nullable', 'integer', 'between:1,1000000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'goal_type.required' => 'Selecione o objetivo principal do funil.',
            'goal_type.in' => 'Selecione um objetivo de funil válido.',
            'offer.required' => 'Informe o que você oferece neste funil.',
            'offer.min' => 'Descreva a oferta com pelo menos 3 caracteres.',
            'desired_action.required' => 'Selecione a ação esperada ao final do funil.',
            'desired_action.in' => 'Selecione uma ação final válida.',
            'prompt.max' => 'A descrição para a IA pode ter no máximo 1000 caracteres.',
            'tone.in' => 'Selecione um tom de comunicação válido.',
        ];
    }
}
