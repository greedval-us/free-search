import type { ParserHistoryItem } from '@/types/parser';

type ValueKey<TItem, TValue> = {
    [TKey in keyof TItem]-?: TItem[TKey] extends TValue ? TKey : never;
}[keyof TItem] &
    string;

export type ParserHistoryField<TItem extends ParserHistoryItem> = {
    label: string;
    key: ValueKey<TItem, string | null>;
    translationPrefix?: string;
};

export type ParserHistoryStat<TItem extends ParserHistoryItem> = {
    label: string;
    key: ValueKey<TItem, number | string>;
};

export type ParserHistoryConfig<TItem extends ParserHistoryItem> = {
    moduleKey: string;
    title: {
        key: ValueKey<TItem, string | null>;
        prefix?: string;
    };
    details?: readonly ParserHistoryField<TItem>[];
    stats: readonly ParserHistoryStat<TItem>[];
};
