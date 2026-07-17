<?php

namespace App\Http\Requests;

use App\Models\Funnel;
use Illuminate\Foundation\Http\FormRequest;

class ShareFunnelRequest extends FormRequest
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
            'email' => ['required', 'email', 'exists:users,email'],
            'role' => ['required', 'in:' . Funnel::SHARE_ROLE_VIEWER . ',' . Funnel::SHARE_ROLE_EDITOR],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.exists' => 'Nenhum usuario encontrado com este e-mail.',
            'role.required' => 'Selecione o nivel de acesso para o compartilhamento.',
        ];
    }
}
