<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useI18n } from '@/composables/useI18n';
import { show } from '@/routes/monitoring/reports';
import { displayDate, sourceUrl, sourceIssue } from './presentation';
import type { Report } from './types';
defineProps<{ report: Report }>();
const { t, locale } = useI18n();
</script>
<template>
    <section class="grid gap-4">
        <h2 class="font-semibold">{{ t('monitoring.summary') }}</h2>
        <p class="text-sm leading-6">{{ report.summary.introduction }}</p>
        <p class="text-sm">{{ report.summary.message }}</p>
        <dl class="grid gap-3 sm:grid-cols-2">
            <div class="rounded-lg border p-3">
                <dt class="text-xs text-muted-foreground">
                    {{ t('monitoring.summaryField.count') }}
                </dt>
                <dd class="mt-1 text-2xl font-semibold">
                    {{ report.summary.count ?? 0 }}
                </dd>
            </div>
            <div
                v-for="(count, platform) in report.summary.platform_counts"
                :key="platform"
                class="rounded-lg border p-3"
            >
                <dt class="text-xs text-muted-foreground">
                    {{ t(`monitoring.platform.${platform}`) }}
                </dt>
                <dd class="mt-1 text-xl font-semibold">{{ count }}</dd>
            </div>
        </dl>
        <p
            v-if="report.summary.unknown_date_count"
            class="text-sm text-muted-foreground"
        >
            {{
                t('monitoring.unknownDates', {
                    count: report.summary.unknown_date_count,
                })
            }}
        </p>
        <p
            v-if="report.summary.truncated"
            class="text-sm text-amber-700 dark:text-amber-300"
        >
            {{ t('monitoring.truncated') }}
        </p>
        <ol class="grid list-decimal gap-3 pl-5">
            <li
                v-for="(bullet, i) in report.summary.bullets"
                :key="i"
                class="text-sm leading-6"
            >
                <p>{{ bullet.text }}</p>
                <p v-if="bullet.count" class="text-xs text-muted-foreground">
                    {{ t('monitoring.groupCount', { count: bullet.count }) }} ·
                    {{
                        bullet.platforms
                            ?.map((platform) =>
                                t(`monitoring.platform.${platform}`)
                            )
                            .join(', ')
                    }}
                </p>
                <p
                    v-if="bullet.first_published_at"
                    class="text-xs text-muted-foreground"
                >
                    {{
                        displayDate(
                            bullet.first_published_at,
                            report.timezone,
                            locale
                        )
                    }}
                    →
                    {{
                        displayDate(
                            bullet.last_published_at ?? null,
                            report.timezone,
                            locale
                        )
                    }}
                </p>
                <a
                    v-for="url in bullet.urls.filter((value) =>
                        sourceUrl(value)
                    )"
                    :key="url"
                    :href="sourceUrl(url)"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="block break-all text-primary underline underline-offset-4"
                    >{{ url }}</a
                >
            </li>
        </ol>
        <section
            v-if="report.summary.recurring_topics?.length"
            class="grid gap-3"
        >
            <h3 class="font-medium">{{ t('monitoring.recurringTopics') }}</h3>
            <p class="text-sm text-muted-foreground">
                {{ t('monitoring.lexicalHint') }}
            </p>
            <div class="grid gap-3 sm:grid-cols-2">
                <article
                    v-for="topic in report.summary.recurring_topics"
                    :key="topic.term"
                    class="rounded-lg border p-3 text-sm"
                >
                    <p class="font-medium">
                        {{ topic.term }} · {{ topic.count }}
                    </p>
                    <a
                        v-for="url in topic.urls.filter((value) =>
                            sourceUrl(value)
                        )"
                        :key="url"
                        :href="sourceUrl(url)"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="mt-2 block text-xs break-all text-primary underline"
                        >{{ url }}</a
                    >
                </article>
            </div>
        </section>
        <section
            v-if="
                report.summary.timeline &&
                Object.keys(report.summary.timeline).length
            "
            class="grid gap-3"
        >
            <h3 class="font-medium">{{ t('monitoring.timeline') }}</h3>
            <p class="text-xs text-muted-foreground">{{ report.timezone }}</p>
            <dl class="grid gap-2 sm:grid-cols-3">
                <div
                    v-for="(count, date) in report.summary.timeline"
                    :key="date"
                    class="flex justify-between gap-3 rounded-lg bg-muted/30 p-3 text-sm"
                >
                    <dt>{{ date }}</dt>
                    <dd class="font-semibold">{{ count }}</dd>
                </div>
            </dl>
        </section>
        <div
            v-if="report.summary.comparison"
            class="rounded-lg bg-muted/30 p-4 text-sm"
        >
            <template v-if="report.summary.comparison.available"
                ><p>
                    {{
                        t('monitoring.comparison', {
                            difference:
                                report.summary.comparison.difference ?? 0,
                        })
                    }}
                </p>
                <Link
                    v-if="report.summary.comparison.previous_report_id"
                    :href="show(report.summary.comparison.previous_report_id)"
                    class="text-primary underline"
                    >{{ t('monitoring.previousReport') }}</Link
                ></template
            >
            <p v-else>{{ report.summary.comparison.reason }}</p>
        </div>
        <details class="rounded-lg border p-4">
            <summary class="cursor-pointer text-sm">
                {{ t('monitoring.coverage') }}
            </summary>
            <div class="mt-4 grid gap-4">
                <article
                    v-for="source in report.coverage"
                    :key="source.id"
                    class="grid gap-2 border-t pt-3 text-sm"
                >
                    <h3 class="font-medium">
                        {{ t(`monitoring.platform.${source.platform}`) }} ·
                        {{ source.title ?? source.identity }}
                    </h3>
                    <p>{{ t(`monitoring.coverageState.${source.state}`) }}</p>
                    <p
                        v-for="(gap, i) in source.gaps"
                        :key="i"
                        class="text-xs text-muted-foreground"
                    >
                        {{ t('monitoring.coverageGap') }}:
                        {{ displayDate(gap.start, report.timezone, locale) }} →
                        {{ displayDate(gap.end, report.timezone, locale) }}
                    </p>
                    <p
                        v-for="warning in source.warnings"
                        :key="warning"
                        class="text-xs text-muted-foreground"
                    >
                        {{ sourceIssue(warning, t) }}
                    </p>
                </article>
            </div>
        </details>
    </section>
</template>
