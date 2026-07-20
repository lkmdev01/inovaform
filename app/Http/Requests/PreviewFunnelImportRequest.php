<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PreviewFunnelImportRequest extends FormRequest
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
            'file' => [
                'required',
                'file',
                'max:10240',
                'mimes:json,zip',
                'mimetypes:application/json,text/plain,application/zip,application/x-zip-compressed,multipart/x-zip',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Selecione um arquivo JSON ou ZIP para importar.',
            'file.max' => 'O arquivo de importação pode ter no máximo 10 MB.',
            'file.mimes' => 'Use um arquivo JSON do InovaForm ou um pacote ZIP compatível.',
            'file.mimetypes' => 'O formato do arquivo enviado não é compatível.',
        ];
    }
}
