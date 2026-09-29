<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight, Pin } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { moduleNavDefinitions } from '@/lib/navigation/modules';

const props = defineProps<{
    modules: { key: string; url: string; is_pinned: boolean }[];
    availableModules: string[];
}>();
const { t } = useI18n();
const tools = computed(() =>
    moduleNavDefinitions
        .filter(
            (item) =>
                item.key !== 'dashboard' &&
                props.availableModules.includes(item.key)
        )
        .map((item) => {
            const activity = props.modules.find(
                (module) => module.key === item.key
            );

            return {
                ...item,
                href: activity?.url ?? item.href,
                pinned: activity?.is_pinned ?? false,
            };
        })
        .sort((a, b) => Number(b.pinned) - Number(a.pinned))
);
</script>

<template>
    <section aria-labelledby="dashboard-tools-heading">
        <div class="mb-4 space-y-1">
            <h2
                id="dashboard-tools-heading"
                class="text-lg font-semibold tracking-tight"
            >
                {{ t('dashboard.sections.quickActions') }}
            </h2>
            <p class="text-sm text-muted-foreground">
                {{ t('dashboard.toolsHint') }}
            </p>
        </div>
        <div
            class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,18rem),1fr))] gap-3"
        >
            <Link
                v-for="tool in tools"
                :key="tool.key"
                :href="tool.href"
                class="group flex min-w-0 items-start gap-4 rounded-2xl border border-border/80 bg-card p-4 transition-colors hover:border-primary/50 hover:bg-primary/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring sm:p-5"
            >
                <span
                    class="flex size-11 shrink-0 items-center justify-center rounded-xl border border-primary/15 bg-primary/8 text-primary"
                    aria-hidden="true"
                    ><component :is="tool.icon" class="size-5"
                /></span>
                <span class="min-w-0 flex-1">
                    <span class="flex items-center gap-2 font-semibold"
                        ><span>{{ t(tool.labelKey) }}</span
                        ><Pin
                            v-if="tool.pinned"
                            class="size-3.5 shrink-0 text-primary"
                            :aria-label="t('dashboard.modules.pinned')"
                            role="img"
                    /></span>
                    <span
                        class="mt-1.5 block text-sm leading-relaxed text-muted-foreground"
                        >{{ t(`dashboard.toolDescriptions.${tool.key}`) }}</span
                    >
                </span>
                <ArrowUpRight
                    class="mt-1 size-4 shrink-0 text-muted-foreground transition-colors group-hover:text-primary"
                    aria-hidden="true"
                />
            </Link>
        </div>
    </section>
</template>
