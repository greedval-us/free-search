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
            'target' => ['nullable', 'string', 'max:' . $this->maxTargetLength(), 'required_without:site'],
            'site' => ['nullable', 'string', 'max:180', 'required_without:target'],
            'scope' => ['nullable', 'string', Rule::in($this->scopeKeys())],
            'goal' => ['nullable', 'string', Rule::in($this->goalKeys())],
            'locale' => $this->localeRule(),
        ];
    }

    public function target(): string
    {
        return trim((string) ($this->validated('target') ?? ''));
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

    public function site(): ?string
    {
        $raw = trim((string) ($this->validated('site') ?? ''));
        if ($raw === '') {
            return null;
        }

        $candidate = str_contains($raw, '://') ? $raw : 'https://' . $raw;
        $host = parse_url($candidate, PHP_URL_HOST);

        if (is_string($host) && $host !== '') {
            return strtolower($host);
        }

        $clean = strtolower(trim((string) preg_replace('~^https?://~i', '', $raw)));
        $clean = trim((string) preg_replace('~/.*$~', '', $clean));

        return $clean !== '' ? $clean : null;
    }

    public function scope(): string
    {
        $scope = strtolower(trim((string) ($this->validated('scope') ?? '')));

        return $scope !== '' ? $scope : 'all';
    }

    public function toQueryDTO(): DorkSearchQueryDTO
    {
        return new DorkSearchQueryDTO(
            target: $this->target(),
            goal: $this->goal(),
            site: $this->site(),
            scope: $this->scope(),
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

    /**
     * @return array<int, string>
     */
    private function scopeKeys(): array
    {
        $types = config('osint.dorks.scope.types', []);

        return array_values(array_unique([
            'all',
            ...array_keys(is_array($types) ? $types : []),
        ]));
    }

    private function defaultGoal(): string
    {
        $goal = strtolower(trim((string) config('osint.dorks.search.default_goal', 'all')));

        return in_array($goal, $this->goalKeys(), true) ? $goal : 'all';
    }
}
