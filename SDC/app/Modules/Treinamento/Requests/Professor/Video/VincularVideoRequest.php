<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests\Professor\Video;

use App\Modules\Treinamento\Models\Video;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VincularVideoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'video_id' => ['required', 'integer', Rule::exists(Video::class, 'id')->whereNull('deleted_at')],
        ];
    }
}
