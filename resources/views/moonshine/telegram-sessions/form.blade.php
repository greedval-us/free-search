<form method="POST" action="{{ $action }}" class="ur-session-form" autocomplete="off" x-data="{ submitting: false }" x-on:submit="submitting = true" x-bind:aria-busy="submitting">
    @csrf
    <label for="{{ $field }}">{{ $label }}</label>
    <input
        id="{{ $field }}"
        name="{{ $field }}"
        type="{{ $type ?? 'text' }}"
        class="form-input"
        required
        maxlength="{{ $maxLength ?? 256 }}"
        autocomplete="{{ $autocomplete ?? 'off' }}"
        @if (isset($inputMode)) inputmode="{{ $inputMode }}" @endif
        @if (isset($hint)) aria-describedby="{{ $field }}-hint" @endif
    >
    @if (isset($hint))
        <p id="{{ $field }}-hint" class="ur-bot-note">{{ $hint }}</p>
    @endif
    <button type="submit" class="btn btn-primary" x-bind:disabled="submitting">{{ $button }}</button>
    <span x-show="submitting" x-cloak role="status">{{ __('admin_telegram_sessions.processing') }}</span>
</form>
