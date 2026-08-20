<script setup lang="ts">
import { AlertCircle } from 'lucide-vue-next';
import { computed } from 'vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { useI18n } from '@/composables/useI18n';

type Props = {
    errors: string[];
    title?: string;
};

const props = defineProps<Props>();
const { t } = useI18n();

const uniqueErrors = computed(() => Array.from(new Set(props.errors)));
const resolvedTitle = computed(() => props.title ?? t('common.fail'));
</script>

<template>
    <Alert variant="destructive">
        <AlertCircle class="size-4" />
        <AlertTitle>{{ resolvedTitle }}</AlertTitle>
        <AlertDescription>
            <ul class="list-inside list-disc text-sm">
                <li v-for="(error, index) in uniqueErrors" :key="index">
                    {{ error }}
                </li>
            </ul>
        </AlertDescription>
    </Alert>
</template>
