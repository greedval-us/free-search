export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type FeatureAccess = {
    limit: number;
    used: number;
    remaining: number;
    allowed: boolean;
};

export type AccountAccess = {
    plan: string;
    subscription: {
        plan: string;
        status: string;
        starts_at: string | null;
        ends_at: string | null;
    } | null;
    features: Record<string, FeatureAccess>;
};

export type AppNotification = {
    id: string;
    title: string;
    body: string;
    titleKey?: string | null;
    bodyKey?: string | null;
    titleParams?: Record<string, string | number | null> | null;
    bodyParams?: Record<string, string | number | null> | null;
    url: string | null;
    kind: string;
    read_at: string | null;
    created_at: string | null;
};

export type AuthNotifications = {
    unreadCount: number;
    items: AppNotification[];
};

export type Auth = {
    user: User;
    access: AccountAccess;
    notifications: AuthNotifications;
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
