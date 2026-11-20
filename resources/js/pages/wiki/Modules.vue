<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useLocale } from '@/composables/useLocale';
import { moduleNavDefinitions } from '@/lib/navigation/modules';
import { toUrl } from '@/lib/utils';
import en from '@/locales/en/moduleWiki.json';
import ru from '@/locales/ru/moduleWiki.json';
import { dashboard } from '@/routes';
import { modules as wikiModules } from '@/routes/wiki';

const { locale } = useLocale();
const content = computed(() => (locale.value === 'ru' ? ru : en));
const ui = computed(() => content.value.ui);
const moduleUrls = new Map(
    moduleNavDefinitions.map(({ key, href }) => [key, toUrl(href)])
);

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                titleKey: 'navigation.dashboard',
                href: dashboard(),
            },
            {
                title: 'Wiki / Modules',
                titleKey: 'navigation.modulesWiki',
                href: wikiModules(),
            },
        ],
    },
});
</script>

<template>
    <Head :title="ui.title" />

    <div class="mx-auto w-full max-w-7xl space-y-4 p-4 sm:p-6">
        <section class="intel-panel-strong">
            <h1 class="text-xl font-semibold sm:text-2xl">{{ ui.title }}</h1>
            <p class="mt-2 text-sm text-muted-foreground">{{ ui.subtitle }}</p>
        </section>

        <section class="intel-panel p-3 sm:p-4">
            <div
                class="intel-scroll max-h-[72vh] overflow-y-auto pr-1 [scrollbar-gutter:stable] sm:pr-2"
            >
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <article
                        v-for="(module, key) in content.modules"
                        :key="key"
                        class="intel-surface rounded-xl p-4"
                    >
                        <div
                            class="mb-3 flex items-start justify-between gap-3"
                        >
                            <div>
                                <h2 class="text-lg font-semibold">
                                    {{ module.title }}
                                </h2>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{ module.purpose }}
                                </p>
                            </div>
                            <a
                                v-if="moduleUrls.has(key)"
                                :href="moduleUrls.get(key)"
                                class="inline-flex rounded-md border border-primary/30 bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary"
                            >
                                {{ ui.open }}
                            </a>
                            <span
                                v-else
                                class="inline-flex rounded-md border border-border/70 bg-background/60 px-2.5 py-1 text-xs font-medium text-muted-foreground"
                            >
                                {{ ui.planned }}
                            </span>
                        </div>

                        <div class="space-y-3 text-sm">
                            <div>
                                <p class="intel-title font-medium">
                                    {{ ui.purpose }}
                                </p>
                                <p class="mt-1 text-muted-foreground">
                                    {{ module.purpose }}
                                </p>
                            </div>

                            <div>
                                <p class="intel-title font-medium">
                                    {{ ui.scenarios }}
                                </p>
                                <ul
                                    class="mt-1 list-disc space-y-1 pl-5 text-muted-foreground"
                                >
                                    <li
                                        v-for="scenario in module.scenarios"
                                        :key="scenario"
                                    >
                                        {{ scenario }}
                                    </li>
                                </ul>
                            </div>

                            <div>
                                <p class="intel-title font-medium">
                                    {{ ui.params }}
                                </p>
                                <div class="mt-2 space-y-2">
                                    <div
                                        v-for="param in module.params"
                                        :key="param.name"
                                        class="intel-section rounded-lg p-3"
                                    >
                                        <p class="text-sm font-semibold">
                                            {{ param.label }}
                                        </p>
                                        <p class="mt-1 text-muted-foreground">
                                            {{ param.description }}
                                        </p>
                                        <p
                                            class="mt-1 text-xs text-muted-foreground"
                                        >
                                            {{ ui.why }}: {{ param.why }}
                                        </p>
                                        <p class="mt-1 text-xs text-primary">
                                            {{ ui.example }}:
                                            <span class="font-mono">{{
                                                param.example
                                            }}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <details class="wiki-details group">
                                <summary class="wiki-summary">
                                    <span class="intel-title font-medium">{{
                                        ui.outputs
                                    }}</span>
                                    <span class="wiki-summary-action">{{
                                        ui.details
                                    }}</span>
                                </summary>
                                <ul
                                    class="mt-2 list-disc space-y-1 pl-5 text-muted-foreground"
                                >
                                    <li
                                        v-for="output in module.outputs"
                                        :key="output"
                                    >
                                        {{ output }}
                                    </li>
                                </ul>
                            </details>

                            <details class="wiki-details group">
                                <summary class="wiki-summary">
                                    <span class="intel-title font-medium">{{
                                        ui.streams
                                    }}</span>
                                    <span class="wiki-summary-action">{{
                                        ui.details
                                    }}</span>
                                </summary>
                                <div class="mt-2 space-y-2">
                                    <div
                                        v-for="stream in module.streams"
                                        :key="stream.name"
                                        class="intel-section rounded-lg p-3"
                                    >
                                        <p class="text-sm font-semibold">
                                            {{ stream.name }}
                                        </p>
                                        <ul
                                            class="mt-1 list-disc space-y-1 pl-5 text-muted-foreground"
                                        >
                                            <li
                                                v-for="item in stream.items"
                                                :key="item"
                                            >
                                                {{ item }}
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </details>
                        </div>
                    </article>
                </div>
            </div>
        </section>
    </div>
</template>

<style scoped>
.wiki-details {
    border-top: 1px solid rgb(148 163 184 / 0.12);
    padding-top: 0.75rem;
}

.wiki-summary {
    display: flex;
    cursor: pointer;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    list-style: none;
}

.wiki-summary::-webkit-details-marker {
    display: none;
}

.wiki-summary-action {
    color: rgb(148 163 184 / 0.9);
    font-size: 0.75rem;
    transition: color 0.2s ease;
}

.group[open] .wiki-summary-action {
    color: rgb(45 212 191 / 0.95);
}
</style>
