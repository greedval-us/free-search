<?php

namespace App\Http\Requests\Telegram;

use App\Modules\Telegram\Tracking\TrackingConfig;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class TelegramTrackingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && ! $this->user()->isBlocked() && $this->user()->hasVerifiedEmail();
    }

    protected function prepareForValidation(): void
    {
        $groups = $this->input('groups');
        if (is_array($groups)) {
            $this->merge(['groups' => array_map(static function ($value) {
                if (! is_string($value)) {
                    return $value;
                }

                return strtolower(preg_replace('~^(?:https?://)?t\.me/(?:s/)?|^@~i', '', rtrim(trim($value), '/')));
            }, $groups)]);
        }
        foreach (['name', 'query'] as $key) {
            if (is_string($this->input($key))) {
                $this->merge([$key => trim($this->input($key))]);
            }
        }
    }

    public function rules(TrackingConfig $config): array
    {
        if ($this->routeIs('telegram.tracking.change')) {
            return ['action' => ['required', Rule::in(['pause', 'resume', 'stop', 'renew', 'preferences'])],
                'notify_bot' => ['required_if:action,preferences', 'boolean']];
        }
        $rules = ['groups' => ['required', 'array', 'list', 'min:1', 'max:'.$config->integer('max_sources')],
            'groups.*' => ['required', 'string', 'distinct', 'regex:/^(?:[a-z][a-z0-9_]{3,31}|-[1-9][0-9]{0,18})$/']];
        if ($this->routeIs('telegram.tracking.validate')) {
            return $rules;
        }

        return [...$rules, 'name' => ['required', 'string', 'max:100'], 'mode' => ['required', Rule::in(['keyword', 'user'])],
            'query' => $this->input('mode') === 'user' ? ['required', 'string', 'regex:/^[1-9][0-9]{0,18}$/']
                : ['required', 'string', 'min:'.$config->integer('keyword_min_length'), 'max:'.$config->integer('keyword_max_length')],
            'notify_bot' => ['sometimes', 'boolean']];
    }
}
