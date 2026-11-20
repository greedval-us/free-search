import { useI18n } from '@/composables/useI18n';

export const useDashboardLabels = () => {
    const { t, locale } = useI18n();
    const moduleLabel = (key: string): string => {
        const translationKey = `dashboard.modules.${key}`;
        const translated = t(translationKey);

        return translated === translationKey
            ? t('dashboard.modules.default')
            : translated;
    };

    const formatDateTime = (value: string | null): string => {
        if (!value) {
            return t('dashboard.common.na');
        }

        return new Date(value).toLocaleString(locale.value);
    };

    const weekDay = (value: string): string => {
        return new Date(value).toLocaleDateString(locale.value, {
            weekday: 'short',
        });
    };

    return { t, moduleLabel, formatDateTime, weekDay };
};
