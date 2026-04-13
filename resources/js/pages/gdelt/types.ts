export type GdeltSort = 'datedesc' | 'dateasc' | 'hybridrel' | 'toneasc' | 'tonedesc';

export type GdeltArticle = {
    title: string;
    url: string;
    domain: string;
    language: string;
    sourceCountry: string;
    sourceCommonName: string;
    seenDate: string;
    socialImage: string;
    tone: number | string | null;
};

export type GdeltSearchResponse = {
    ok: boolean;
    items: GdeltArticle[];
    total: number;
    message?: string;
};
