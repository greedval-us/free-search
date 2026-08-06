export type ParserHistoryItem = {
    runId: string;
    status: string;
    stage: string | null;
    progress: number;
    error: string | null;
    createdAt: string | null;
    updatedAt: string | null;
    finishedAt: string | null;
    expiresAt: string | null;
    downloadable: boolean;
    downloadUrl: string | null;
    downloadJsonUrl: string | null;
};

export type ParserHistoryField<TItem extends ParserHistoryItem> = {
    label: string;
    value: (item: TItem) => string | null;
};

export type ParserHistoryStat<TItem extends ParserHistoryItem> = {
    label: string;
    value: (item: TItem) => number | string;
};
