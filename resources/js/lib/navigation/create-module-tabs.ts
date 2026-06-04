import type { Component } from 'vue';
import { MODULE_TAB_ICONS } from './tab-icons';

type ModuleTabKey = 'search' | 'analytics' | 'parser';

type ModuleTabInput = {
    key: ModuleTabKey;
    labelKey: string;
    component: Component;
    accessKey?: string;
};

export type StandardModuleTabDefinition = {
    key: ModuleTabKey;
    labelKey: string;
    icon: Component;
    component: Component;
    accessKey?: string;
};

export const createModuleTabs = (
    tabs: readonly ModuleTabInput[]
): readonly StandardModuleTabDefinition[] =>
    tabs.map((tab) => ({
        ...tab,
        icon: MODULE_TAB_ICONS[tab.key],
    }));
