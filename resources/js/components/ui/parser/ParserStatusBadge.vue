<script setup lang="ts">
import {
    CheckCircle2,
    CircleHelp,
    CircleStop,
    Clock3,
    LoaderCircle,
    OctagonX,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { normalizeParserStatus } from './presentation';
import type { ParserDisplayStatus } from './presentation';

const props = defineProps<{ status: string }>();
const { t } = useI18n();
const status = computed(() => normalizeParserStatus(props.status));

const presentation = {
    idle: {
        icon: Clock3,
        class: 'border-border bg-muted/35 text-muted-foreground',
    },
    running: {
        icon: LoaderCircle,
        class: 'border-primary/25 bg-primary/10 text-primary',
    },
    completed: {
        icon: CheckCircle2,
        class: 'border-emerald-500/25 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    },
    failed: {
        icon: OctagonX,
        class: 'border-destructive/25 bg-destructive/10 text-destructive',
    },
    stopped: {
        icon: CircleStop,
        class: 'border-amber-500/25 bg-amber-500/10 text-amber-800 dark:text-amber-300',
    },
    unknown: {
        icon: CircleHelp,
        class: 'border-border bg-muted/35 text-muted-foreground',
    },
} satisfies Record<ParserDisplayStatus, { icon: unknown; class: string }>;
</script>

<template>
    <span
        class="inline-flex max-w-full items-center gap-1.5 rounded-full border px-2 py-1 text-xs font-medium"
        :class="presentation[status].class"
    >
        <component
            :is="presentation[status].icon"
            class="size-3.5 shrink-0"
            :class="
                status === 'running'
                    ? 'animate-spin motion-reduce:animate-none'
                    : ''
            "
            aria-hidden="true"
        />
        <span class="break-words">{{ t(`parser.status.${status}`) }}</span>
    </span>
</template>
