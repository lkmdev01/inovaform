<?php

namespace App\Http\Requests;

use App\Models\Funnel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadFunnelMediaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $funnel = $this->route('funnel');

        return $user !== null
            && $funnel instanceof Funnel
            && $funnel->canEdit($user);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'kind' => ['required', Rule::in(['image', 'audio'])],
            'file' => ['required', 'file', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.max' => 'O arquivo precisa ter no maximo 10MB.',
        ];
    }

    public function after(): array
    {
        return [
            function (\Illuminate\Validation\Validator $validator): void {
                $file = $this->file('file');
                $kind = (string) $this->input('kind');

                if (!$file) {
                    return;
                }

                if ($kind === 'image' && !str_starts_with((string) $file->getMimeType(), 'image/')) {
                    $validator->errors()->add('file', 'Envie um arquivo de imagem valido.');
                }

                if ($kind === 'audio' && !in_array((string) $file->getMimeType(), ['audio/mpeg', 'audio/mp3'], true)) {
                    $validator->errors()->add('file', 'Envie um audio MP3 valido.');
                }
            },
        ];
    }
}
