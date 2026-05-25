import { computed, onMounted, ref } from 'vue';
import type { ComputedRef, Ref } from 'vue';
import {
    getRepeatQueryParams,
    readRepeatQueryParam,
} from '@/composables/useRepeatQuery';

type TabLike = {
    key: string;
};

export function useRepeatTab<D extends TabLike>(
    tabs: readonly D[],
    defaultTab: D['key']
): {
    activeTab: Ref<D['key']>;
    activeTabDefinition: ComputedRef<D>;
} {
    const activeTab = ref(defaultTab) as Ref<D['key']>;
    const activeTabKeys = new Set<D['key']>(tabs.map((tab) => tab.key));

    const activeTabDefinition = computed(
        () => tabs.find((tab) => tab.key === activeTab.value) ?? tabs[0]
    );

    onMounted(() => {
        const params = getRepeatQueryParams();

        if (!params) {
            return;
        }

        const tab = readRepeatQueryParam(params, ['tab']);

        if (tab && activeTabKeys.has(tab as D['key'])) {
            activeTab.value = tab as D['key'];
        }
    });

    return {
        activeTab,
        activeTabDefinition,
    };
}
