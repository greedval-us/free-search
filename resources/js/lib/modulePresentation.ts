import { Radar, Search, Send } from 'lucide-vue-next';
import type { Component } from 'vue';
import { moduleNavDefinitions } from '@/lib/navigation/modules';

const icons: Record<string, Component> = {
    'telegram-tracking': Radar,
    'telegram-bot': Send,
};

export const moduleIcon = (key: string): Component =>
    moduleNavDefinitions.find((module) => module.key === key)?.icon ??
    icons[key] ??
    Search;
