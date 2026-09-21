<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowRight } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from '@/composables/useI18n';

const props = withDefaults(
    defineProps<{ canRegister: boolean; workspaceUrl?: string }>(),
    { workspaceUrl: '/dashboard' }
);
const page = usePage();
const { t } = useI18n();
const authenticated = computed(() => Boolean(page.props.auth.user));
const destination = computed(() =>
    authenticated.value
        ? props.workspaceUrl
        : props.canRegister
          ? '/register'
          : '/login'
);
</script>

<template>
    <Link :href="destination" class="public-button public-button-primary">
        {{
            authenticated
                ? t('publicSite.openWorkspace')
                : canRegister
                  ? t('publicSite.start')
                  : t('publicSite.signIn')
        }}
        <ArrowRight :size="16" aria-hidden="true" />
    </Link>
</template>
