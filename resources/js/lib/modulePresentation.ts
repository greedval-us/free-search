import {
    AtSign,
    Cloud,
    Fingerprint,
    Globe,
    Newspaper,
    Radar,
    Search,
    Send,
    Youtube,
} from 'lucide-vue-next';
import type { Component } from 'vue';

const icons: Record<string, Component> = {
    bluesky: Cloud,
    mastodon: AtSign,
    telegram: Send,
    'telegram-tracking': Radar,
    'telegram-bot': Send,
    youtube: Youtube,
    'site-intel': Globe,
    'news-media-intel': Newspaper,
    shifr: Fingerprint,
};

export const moduleIcon = (key: string): Component => icons[key] ?? Search;
