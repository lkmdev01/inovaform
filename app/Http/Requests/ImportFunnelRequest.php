<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportFunnelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'file' => ['required_without:token', 'nullable', 'file', 'max:2048', 'mimetypes:application/json,text/plain'],
            'token' => ['required_without:file', 'nullable', 'string', 'size:64'],
            'language' => ['nullable', 'string', 'max:40'],
            'name' => ['nullable', 'string', 'max:120'],
            'copy_media' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required_without' => 'Envie um arquivo JSON ou faça a pré-visualização do pacote novamente.',
            'file.mimetypes' => 'O arquivo de importacao deve estar em formato JSON.',
            'token.required_without' => 'A pré-visualização da importação é obrigatória para pacotes ZIP.',
        ];
    }
}
