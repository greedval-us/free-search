<script setup lang="ts">
defineProps<{
    badge: string;
    title: string;
    description: string;
}>();
</script>

<template>
    <section
        class="overflow-hidden rounded-xl border border-sidebar-border/75 bg-background/60 shadow-sm"
    >
        <div
            class="border-b border-sidebar-border/70 bg-primary/[0.04] p-4 sm:p-5"
        >
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0 flex-1 space-y-2">
                    <p class="text-xs tracking-[0.2em] text-primary uppercase">
                        {{ badge }}
                    </p>
                    <h1 class="text-2xl font-semibold break-words sm:text-3xl">
                        {{ title }}
                    </h1>
                    <p
                        class="max-w-2xl text-sm leading-6 break-words text-muted-foreground"
                    >
                        {{ description }}
                    </p>
                </div>

                <div
                    class="w-full max-w-full min-w-0 rounded-lg border border-primary/20 bg-background/80 p-3 text-left break-words sm:w-auto sm:min-w-48 sm:text-right"
                >
                    <slot name="summary" />
                </div>
            </div>
        </div>
    </section>
</template>
