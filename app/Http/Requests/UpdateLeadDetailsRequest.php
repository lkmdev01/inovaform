<?php

namespace App\Http\Requests;

use App\Models\FunnelSubmission;
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
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
}
