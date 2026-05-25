import { useI18n } from '@/composables/useI18n';

export const useFioLabels = () => {
    const { t } = useI18n();

    const formatDateTime = (value: string | null): string => {
        if (!value) {
            return '-';
        }

        return new Date(value).toLocaleString();
    };

    const regionLabel = (key: string): string => {
        const translationKey = `fio.region.${key}`;
        const translated = t(translationKey);

        return translated === translationKey ? key : translated;
    };

    const ageBucketLabel = (key: string): string => {
        const translationKey = `fio.age.${key}`;
        const translated = t(translationKey);

        return translated === translationKey ? key : translated;
    };

    return {
        ageBucketLabel,
        formatDateTime,
        regionLabel,
    };
};
