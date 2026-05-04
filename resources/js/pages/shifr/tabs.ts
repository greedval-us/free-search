import { Binary, Fingerprint, KeyRound, Link2 } from 'lucide-vue-next';
import type { Component } from 'vue';
import ShifrClassicTab from './tabs/ShifrClassicTab.vue';
import ShifrHashTab from './tabs/ShifrHashTab.vue';
import ShifrIocTab from './tabs/ShifrIocTab.vue';
import ShifrTransformTab from './tabs/ShifrTransformTab.vue';

export type ShifrTabValue = 'hash' | 'transform' | 'ioc' | 'classic';

export interface ShifrTabDefinition {
    key: ShifrTabValue;
    icon: Component;
    labelKey: string;
    descriptionKey: string;
    component: Component;
}

export const SHIFR_TABS: ShifrTabDefinition[] = [
    { key: 'hash', icon: Fingerprint, labelKey: 'shifr.tabs.hash', descriptionKey: 'shifr.descriptions.hash', component: ShifrHashTab },
    { key: 'transform', icon: Binary, labelKey: 'shifr.tabs.transform', descriptionKey: 'shifr.descriptions.transform', component: ShifrTransformTab },
    { key: 'ioc', icon: Link2, labelKey: 'shifr.tabs.ioc', descriptionKey: 'shifr.descriptions.ioc', component: ShifrIocTab },
    { key: 'classic', icon: KeyRound, labelKey: 'shifr.tabs.classic', descriptionKey: 'shifr.descriptions.classic', component: ShifrClassicTab },
];
