export type TrackingForm = {
    name: string;
    groups: string;
    mode: 'keyword' | 'user';
    query: string;
    notify_bot: boolean;
};

export type TrackingSource = {
    id: number;
    title: string;
    peer_id: string;
    username: string | null;
    checked_at: string | null;
    next_check_at: string;
    collecting: boolean;
    error_code: string | null;
    overdue: boolean;
};

export type TrackingTask = {
    id: number;
    name: string;
    mode: 'keyword' | 'user';
    query: string;
    status: 'active' | 'paused' | 'stopped' | 'expired';
    pause_reason: string | null;
    notify_bot: boolean;
    started_at: string;
    expires_at: string;
    purge_at: string | null;
    messages_count: number;
    can_renew: boolean;
    sources: TrackingSource[];
};

export type TrackingMessage = {
    id: number;
    message_id: string;
    group: string;
    peer_id: string;
    sender_id: string | null;
    text: string;
    sent_at: string;
    received_at: string;
    url: string | null;
};

export type TrackingCapacity = {
    limit: number;
    active_count: number;
    remaining: number;
};

export type TrackingList = TrackingCapacity & {
    items: TrackingTask[];
    has_more: boolean;
    max_sources: number;
    keyword_min_length: number;
    interval_hours: number;
    max_interval_hours: number;
    retention_days: number;
    duration_months: number;
};

export type TrackingAction =
    | 'pause'
    | 'resume'
    | 'stop'
    | 'renew'
    | 'preferences';
