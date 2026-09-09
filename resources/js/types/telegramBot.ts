export type TelegramBotPreferences = {
    locale: 'ru' | 'en';
    notifications_enabled: boolean;
    exports_enabled: boolean;
    broadcasts_enabled: boolean;
};

export type TelegramBotState = {
    available: boolean;
    username: string;
    link: (TelegramBotPreferences & { telegram_id: string }) | null;
    pending: {
        id: string;
        telegram_id: string | null;
        expires_at: string;
    } | null;
    routes: Record<
        'status' | 'issue' | 'confirm' | 'disconnect' | 'preferences',
        string
    >;
};
