import { computed } from 'vue';

export function useLocaleFormatters(locale: { readonly value: string }) {
    const numbers = computed(() => new Intl.NumberFormat(locale.value));
    const formatNumber = (value: number) => numbers.value.format(value ?? 0);
    const formatDate = (value: string) =>
        value ? new Date(value).toLocaleString(locale.value) : '-';

    const formatUnixDate = (seconds: number) =>
        seconds ? new Date(seconds * 1000).toLocaleString(locale.value) : '-';

    return { formatNumber, formatDate, formatUnixDate };
}
