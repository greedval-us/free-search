<script setup lang="ts">
import { FileCheck2, Radar, Send } from 'lucide-vue-next';
import { useI18n } from '@/composables/useI18n';
const { t } = useI18n();
const steps = [
    { key: 'sources', icon: Send },
    { key: 'collection', icon: Radar },
    { key: 'result', icon: FileCheck2 },
];
</script>

<template>
    <figure class="public-workflow">
        <figcaption class="public-eyebrow">
            {{ t('publicSite.previewLabel') }}
        </figcaption>
        <ol>
            <li
                v-for="step in steps"
                :key="step.key"
                :class="`public-workflow-step public-workflow-step-${step.key}`"
            >
                <span class="public-workflow-dot" aria-hidden="true"
                    ><component :is="step.icon" :size="20"
                /></span>
                <div class="public-workflow-content">
                    <strong>{{
                        t(`publicSite.preview.${step.key}.title`)
                    }}</strong>
                    <p>{{ t(`publicSite.preview.${step.key}.text`) }}</p>
                </div>
            </li>
        </ol>
        <p class="public-workflow-note">{{ t('publicSite.previewNote') }}</p>
    </figure>
</template>
