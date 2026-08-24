<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Sparkles } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

defineProps<{
    badge: string;
    title: string;
    description: string;
    bullets: string[];
    note?: string;
    primaryLabel?: string;
    primaryHref?: string;
    secondaryLabel: string;
    secondaryHref: string;
}>();
</script>

<template>
    <section
        class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-background/60 shadow-sm"
    >
        <div
            class="border-b border-sidebar-border/70 bg-primary/[0.04] p-4 sm:p-5"
        >
            <div
                class="inline-flex max-w-full items-center gap-2 rounded-full border border-primary/25 bg-primary/10 px-3 py-1 text-xs font-semibold tracking-[0.18em] break-words text-primary uppercase"
            >
                <Sparkles class="h-3.5 w-3.5" />
                {{ badge }}
            </div>

            <h2 class="mt-4 text-2xl font-semibold break-words sm:text-3xl">
                {{ title }}
            </h2>
            <p
                class="mt-3 max-w-2xl text-sm leading-6 break-words text-muted-foreground"
            >
                {{ description }}
            </p>
        </div>

        <div class="space-y-4 p-4 sm:p-5">
            <div class="grid gap-3">
                <article
                    v-for="bullet in bullets"
                    :key="bullet"
                    class="rounded-lg bg-muted/35 p-3 text-sm leading-6 break-words text-foreground/90"
                >
                    {{ bullet }}
                </article>
            </div>

            <p
                v-if="note"
                class="rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm leading-6 break-words text-amber-800 dark:text-amber-100"
            >
                {{ note }}
            </p>

            <div class="grid gap-3 sm:flex sm:flex-wrap">
                <Button
                    v-if="primaryLabel && primaryHref"
                    as-child
                    class="rounded-xl"
                >
                    <Link :href="primaryHref">
                        {{ primaryLabel }}
                    </Link>
                </Button>

                <Button variant="outline" as-child class="rounded-xl">
                    <Link :href="secondaryHref">
                        {{ secondaryLabel }}
                    </Link>
                </Button>
            </div>
        </div>
    </section>
</template>
