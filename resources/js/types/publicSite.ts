export type PublicFeature = {
    slug: string;
    url: string;
    workspaceUrl: string;
};

export type PublicSiteProps = {
    canRegister: boolean;
    features: PublicFeature[];
};
