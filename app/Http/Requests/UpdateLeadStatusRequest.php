<?php

namespace App\Http\Requests;

use App\Models\FunnelSubmission;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLeadStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
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
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'in:new,contacted,qualified,lost'],
        ];
    }
}
