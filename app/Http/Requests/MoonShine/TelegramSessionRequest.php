<?php

declare(strict_types=1);

namespace App\Http\Requests\MoonShine;

use App\MoonShine\Support\AdminAccess;
use App\MoonShine\Support\AdminRole;
use Illuminate\Foundation\Http\FormRequest;
use MoonShine\Laravel\Models\MoonshineUser;

final class TelegramSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $staff = auth('moonshine')->user();

        return $staff instanceof MoonshineUser
            && app(AdminAccess::class)->role($staff) === AdminRole::Admin
            && (! app()->isProduction() || $this->isSecure());
    }

    public function rules(): array
    {
        return match ($this->route()->getName()) {
            'moonshine.telegram-sessions.store' => [
                'name' => ['required', 'string', 'max:'.config('madelineproto.admin_auth.max_name_length'), 'regex:/\A[a-z][a-z0-9_-]*\z/D'],
            ],
            'moonshine.telegram-sessions.phone' => ['phone_number' => ['required', 'string', 'regex:/\A\+[1-9][0-9]{6,14}\z/D']],
            'moonshine.telegram-sessions.code' => ['phone_code' => ['required', 'string', 'regex:/\A[0-9]{4,8}\z/D']],
            'moonshine.telegram-sessions.password' => ['password' => ['required', 'string', 'max:256']],
            default => [],
        };
    }

    public function attributes(): array
    {
        return [
            'name' => __('admin_telegram_sessions.name'),
            'phone_number' => __('admin_telegram_sessions.phone'),
            'phone_code' => __('admin_telegram_sessions.code'),
            'password' => __('admin_telegram_sessions.password'),
        ];
    }
}
