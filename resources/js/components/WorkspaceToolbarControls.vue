<script setup lang="ts">
import NotificationBell from '@/components/NotificationBell.vue';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/composables/useI18n';

type Props = {
    containerClass?: string;
    buttonClass?: string;
    localeButtonClass?: string;
    localeButtonSize?: 'default' | 'sm' | 'lg' | 'icon';
};

withDefaults(defineProps<Props>(), {
    containerClass: 'flex items-center gap-2',
    buttonClass:
        'group relative h-8 w-8 rounded-md border border-input bg-background text-foreground transition hover:bg-accent hover:text-accent-foreground',
    localeButtonClass: 'h-8 px-2.5 text-xs',
    localeButtonSize: 'sm',
});

const { locale, setLocale, t } = useI18n();

const toggleLocale = () => {
    setLocale(locale.value === 'ru' ? 'en' : 'ru');
};
</script>

<template>
    <div :class="containerClass">
        <NotificationBell :button-class="buttonClass" />
        <Button
            type="button"
            variant="outline"
            :size="localeButtonSize"
            :class="localeButtonClass"
            @click="toggleLocale"
        >
            {{ t('common.language') }}: {{ locale.toUpperCase() }}
        </Button>
    </div>
</template>
