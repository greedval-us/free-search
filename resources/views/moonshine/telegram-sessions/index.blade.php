@php
    use App\Support\MadelineProto\Authentication\LoginStage;
    use App\MoonShine\Support\Formatting\AdminPanelDateFormatter;
@endphp

<section class="ur-admin-hero">
    <div class="ur-admin-hero__copy">
        <div class="ur-admin-eyebrow">MadelineProto</div>
        <h2>{{ __('admin_telegram_sessions.title') }}</h2>
        <p>{{ __('admin_telegram_sessions.subtitle') }}</p>
    </div>
    <p class="ur-bot-note">{{ __('admin_telegram_sessions.safety') }}</p>
</section>

<section class="ur-admin-analytics ur-bot-overview">
    @if ($errorMessage !== null)
        <div class="ur-session-alert" role="alert">{{ $errorMessage }}</div>
    @endif
    @if ($errors->any())
        <div class="ur-session-alert" role="alert">
            @foreach ($errors->all() as $message)
                <p>{{ $message }}</p>
            @endforeach
        </div>
    @endif

    @if ($canManage)
        <article class="ur-admin-chart-card">
            @if ($selected === null)
                <h3>{{ __('admin_telegram_sessions.new') }}</h3>
                @include('moonshine.telegram-sessions.form', [
                    'action' => route('moonshine.telegram-sessions.store'),
                    'field' => 'name', 'label' => __('admin_telegram_sessions.name'),
                    'maxLength' => config('madelineproto.admin_auth.max_name_length'),
                    'hint' => __('admin_telegram_sessions.name_hint'), 'button' => __('admin_telegram_sessions.create'),
                ])
            @else
                <header>
                    <h3>{{ $selected->name }}</h3>
                    <a href="{{ $pageUrl }}" class="btn">{{ __('admin_telegram_sessions.back') }}</a>
                </header>
                <p class="ur-bot-note">{{ __('admin_telegram_sessions.stages.'.($selected->expired() ? 'expired' : $selected->stage->value)) }}</p>

                @if ($selected->retry_at?->isFuture())
                    <p role="status">{{ __('admin_telegram_sessions.retry', ['date' => $selected->retry_at->format(AdminPanelDateFormatter::DATE_TIME_FORMAT)]) }}</p>
                @elseif ($selected->stage === LoginStage::Ready)
                    <p role="status">{{ __('admin_telegram_sessions.'.(in_array($selected->name, $poolNames, true) ? 'ready' : 'not_published')) }}</p>
                @else
                    <p class="ur-bot-note">{{ __('admin_telegram_sessions.expires', ['date' => $selected->expires_at->format(AdminPanelDateFormatter::DATE_TIME_FORMAT)]) }}</p>
                    @if ($selected->expired() || in_array($selected->stage, [LoginStage::Phone, LoginStage::Unsupported], true))
                        @include('moonshine.telegram-sessions.form', [
                            'action' => route('moonshine.telegram-sessions.phone', ['connection' => $selected->id]),
                            'field' => 'phone_number', 'type' => 'tel', 'inputMode' => 'tel',
                            'label' => __('admin_telegram_sessions.phone'), 'maxLength' => 16,
                            'hint' => __('admin_telegram_sessions.phone_hint'), 'button' => __('admin_telegram_sessions.send_code'),
                        ])
                    @elseif ($selected->stage === LoginStage::Code)
                        @include('moonshine.telegram-sessions.form', [
                            'action' => route('moonshine.telegram-sessions.code', ['connection' => $selected->id]),
                            'field' => 'phone_code', 'inputMode' => 'numeric', 'autocomplete' => 'one-time-code', 'maxLength' => 8,
                            'label' => __('admin_telegram_sessions.code'), 'hint' => __('admin_telegram_sessions.code_hint'),
                            'button' => __('admin_telegram_sessions.submit_code'),
                        ])
                    @elseif ($selected->stage === LoginStage::Password)
                        @include('moonshine.telegram-sessions.form', [
                            'action' => route('moonshine.telegram-sessions.password', ['connection' => $selected->id]),
                            'field' => 'password', 'type' => 'password', 'maxLength' => 256,
                            'label' => __('admin_telegram_sessions.password'), 'button' => __('admin_telegram_sessions.submit_password'),
                        ])
                    @endif
                    @if (! $selected->expired() && in_array($selected->stage, [LoginStage::Code, LoginStage::Password], true))
                        <details class="ur-session-restart">
                            <summary>{{ __('admin_telegram_sessions.restart') }}</summary>
                            @include('moonshine.telegram-sessions.form', [
                                'action' => route('moonshine.telegram-sessions.phone', ['connection' => $selected->id]),
                                'field' => 'phone_number', 'type' => 'tel', 'inputMode' => 'tel', 'maxLength' => 16,
                                'label' => __('admin_telegram_sessions.phone'), 'button' => __('admin_telegram_sessions.send_code'),
                            ])
                        </details>
                    @endif
                @endif
                @if (! $selected->expired() && ! $selected->retry_at?->isFuture())
                    <form method="POST" action="{{ route('moonshine.telegram-sessions.inspect', ['connection' => $selected->id]) }}" class="ur-session-form" x-data="{ submitting: false }" x-on:submit="submitting = true">
                        @csrf
                        <button type="submit" class="btn" x-bind:disabled="submitting">{{ __('admin_telegram_sessions.inspect') }}</button>
                    </form>
                @endif
            @endif
            <p class="ur-bot-note">{{ __('admin_telegram_sessions.private') }}</p>
            <p class="ur-bot-note">{{ __('admin_telegram_sessions.https') }}</p>
        </article>
    @endif

    <article class="ur-admin-chart-card">
        <h3>{{ __('admin_telegram_sessions.managed') }}</h3>
        @if ($connections->isEmpty())
            <p class="ur-admin-empty">{{ __('admin_telegram_sessions.empty') }}</p>
        @else
            <div class="ur-bot-table-scroll" role="region" aria-label="{{ __('admin_telegram_sessions.managed') }}" tabindex="0">
                <table class="ur-bot-table">
                    <thead><tr>
                        <th scope="col">{{ __('admin_telegram_sessions.name') }}</th>
                        <th scope="col">{{ __('admin_panel.fields.status') }}</th>
                        <th scope="col">{{ __('admin_telegram_sessions.pool') }}</th>
                        <th scope="col">{{ __('admin_telegram_sessions.owner') }}</th>
                        <th scope="col">{{ __('admin_telegram_sessions.updated') }}</th>
                        @if ($canManage)<th scope="col">{{ __('admin_panel.fields.action') }}</th>@endif
                    </tr></thead>
                    <tbody>
                    @foreach ($connections as $connection)
                        <tr>
                            <th scope="row">{{ $connection->name }}</th>
                            <td>{{ __('admin_telegram_sessions.stages.'.($connection->expired() ? 'expired' : $connection->stage->value)) }}</td>
                            <td>{{ __('admin_panel.values.'.(in_array($connection->name, $poolNames, true) ? 'yes' : 'no')) }}</td>
                            <td>{{ $connection->created_by }}</td>
                            <td>{{ $connection->updated_at->format(AdminPanelDateFormatter::DATE_TIME_FORMAT) }}</td>
                            @if ($canManage)
                                <td>
                                    @if ($connection->created_by === $actorId)
                                        <a href="{{ $pageUrl.'?connection='.$connection->id }}" class="btn">{{ __('admin_telegram_sessions.open') }}</a>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            {{ $connections->links() }}
        @endif
    </article>
    <article class="ur-admin-chart-card">
        <h3>{{ __('admin_telegram_sessions.legacy') }}</h3>
        <p class="ur-bot-note">{{ __('admin_telegram_sessions.legacy_hint') }}</p>
        @foreach ($legacyNames as $name)
            <p>{{ $name }}</p>
        @endforeach
    </article>
</section>
