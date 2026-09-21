<script setup lang="ts">
import { useI18n } from '@/composables/useI18n';
const { t } = useI18n();
</script>

<template>
    <figure class="public-workflow">
        <figcaption class="public-eyebrow">
            {{ t('publicSite.previewLabel') }}
        </figcaption>
        <ol>
            <li v-for="step in ['sources', 'collection', 'result']" :key="step">
                <span class="public-workflow-dot" aria-hidden="true"></span>
                <div>
                    <strong>{{ t(`publicSite.preview.${step}.title`) }}</strong>
                    <p>{{ t(`publicSite.preview.${step}.text`) }}</p>
                </div>
            </li>
        </ol>
        <p class="public-workflow-note">{{ t('publicSite.previewNote') }}</p>
    </figure>
</template>
