<?php

namespace App\Http\Requests\Bluesky;

use App\Modules\Bluesky\DTO\Request\BlueskyParserStartDTO;
use Illuminate\Foundation\Http\FormRequest;

final class BlueskyParserStartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'actor' => ['required', 'string', 'max:255'],
        ];
    }

    public function toStartDTO(): BlueskyParserStartDTO
    {
        return new BlueskyParserStartDTO(
            userId: (int) $this->user()->id,
            actor: $this->normalizedActor(),
        );
    }

    private function normalizedActor(): string
    {
        return ltrim(trim((string) $this->validated('actor')), '@');
    }
}
