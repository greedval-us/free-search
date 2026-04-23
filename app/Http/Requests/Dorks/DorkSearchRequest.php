<?php

namespace App\Http\Requests\Dorks;

use App\Http\Requests\LocalizedFormRequest;
use App\Modules\Dorks\Application\DTO\DorkSearchQueryDTO;
use Illuminate\Validation\Rule;

class DorkSearchRequest extends LocalizedFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target' => ['required', 'string', 'max:' . $this->maxTargetLength()],
            'goal' => ['nullable', 'string', Rule::in($this->goalKeys())],
            'locale' => $this->localeRule(),
        ];
    }

    public function target(): string
    {
        return trim((string) $this->validated('target'));
    }

    public function goal(): string
    {
        $goal = strtolower(trim((string) ($this->validated('goal') ?? '')));

        return $goal !== '' ? $goal : $this->defaultGoal();
    }

    public function locale(): string
    {
        return $this->resolveLocale();
    }

    public function toQueryDTO(): DorkSearchQueryDTO
    {
        return new DorkSearchQueryDTO(
            target: $this->target(),
            goal: $this->goal(),
        );
    }

    /**
     * @return array<int, string>
     */
    private function goalKeys(): array
    {
        $goals = config('osint.dorks.goals', []);

        return array_values(array_unique([
            'all',
            ...array_keys(is_array($goals) ? $goals : []),
        ]));
    }

    private function maxTargetLength(): int
    {
        return max(32, (int) config('osint.dorks.search.max_target_length', 120));
    }

    private function defaultGoal(): string
    {
        $goal = strtolower(trim((string) config('osint.dorks.search.default_goal', 'all')));

        return in_array($goal, $this->goalKeys(), true) ? $goal : 'all';
    }
}

