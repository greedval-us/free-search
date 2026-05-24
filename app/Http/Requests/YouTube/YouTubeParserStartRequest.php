<?php

namespace App\Http\Requests\YouTube;

use App\Modules\YouTube\DTO\Request\YouTubeParserStartDTO;
use App\Modules\YouTube\Support\YouTubeInputNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class YouTubeParserStartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'videoId' => ['required', 'string', 'max:2048'],
        ];
    }

    public function toStartDTO(): YouTubeParserStartDTO
    {
        $validated = $this->validated();

        return new YouTubeParserStartDTO(
            userId: (int) $this->user()->id,
            videoId: YouTubeInputNormalizer::normalizeVideoId(trim((string) $validated['videoId'])),
        );
    }
}
