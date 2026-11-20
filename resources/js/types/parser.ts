export type ParserRunStatus = 'running' | 'completed' | 'failed' | 'stopped';

export type ParserStage<WorkStage extends string> =
    | WorkStage
    | 'idle'
    | 'finishing'
    | 'completed'
    | 'failed'
    | 'stopped';

type ParserProgress = {
    runId: string;
    progress: number;
    error: string | null;
    downloadUrl: string | null;
    downloadJsonUrl: string | null;
};

export type ParserRunPayload<Stage extends string> = ParserProgress & {
    ok: boolean;
    status: ParserRunStatus;
    stage: Stage;
};

export type ParserHistoryItem = ParserProgress & {
    status: ParserRunStatus | 'unknown';
    stage: string | null;
    createdAt: string | null;
    updatedAt: string | null;
    finishedAt: string | null;
    expiresAt: string | null;
    downloadable: boolean;
};

export type ParserHistoryResponse<Item extends ParserHistoryItem> = {
    ok: boolean;
    items: Item[];
    retentionDays: number;
};
