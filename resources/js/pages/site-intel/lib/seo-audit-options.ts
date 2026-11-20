export const SEO_AUDIT_PLATFORMS = [
    { value: 'auto', label: 'siteIntel.seoAudit.platformAuto' },
    { value: 'generic', label: 'siteIntel.seoAudit.profile.generic' },
    {
        value: 'media-platform',
        label: 'siteIntel.seoAudit.profile.media-platform',
    },
    { value: 'content-site', label: 'siteIntel.seoAudit.profile.content-site' },
    { value: 'storefront', label: 'siteIntel.seoAudit.profile.storefront' },
] as const;

export type SitePlatformType = (typeof SEO_AUDIT_PLATFORMS)[number]['value'];

export const DEFAULT_SEO_AUDIT_OPTIONS: Readonly<{
    crawlLimit: number;
    platformType: SitePlatformType;
}> = { crawlLimit: 8, platformType: 'auto' };

export const SEO_AUDIT_CRAWL_PRESETS = [5, 8, 12, 20] as const;
export const SCHEDULED_CRAWL_LIMIT = { min: 3, max: 20 } as const;
