<?php

namespace App\Http\Requests;

use App\Models\FunnelSubmission;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLeadDetailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $lead = $this->route('lead');

        return $user !== null
            && $lead instanceof FunnelSubmission
            && $lead->funnel !== null
            && $lead->funnel->canManageLeads($user);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', 'in:new,contacted,qualified,lost'],
            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
            'priority' => ['nullable', 'in:low,normal,high,urgent'],
            'next_follow_up_at' => ['nullable', 'date'],
            'tags' => ['nullable', 'array', 'max:12'],
            'tags.*' => ['required', 'string', 'max:32'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.in' => 'Selecione um status válido.',
            'assignee_id.exists' => 'Selecione um responsável válido.',
            'priority.in' => 'Selecione uma prioridade válida.',
            'next_follow_up_at.date' => 'Informe uma data válida para o próximo contato.',
            'tags.max' => 'Informe no máximo 12 tags.',
            'tags.*.max' => 'Cada tag deve ter no máximo 32 caracteres.',
            'notes.max' => 'As observações devem ter no máximo 2000 caracteres.',
        ];
    }
}
