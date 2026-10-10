import { describe, expect, it } from 'vitest';
import { blockedTabFeature } from './module-tab-access';

const reports = {
    key: 'reports',
    accessKeys: ['site-intel.analytics', 'site-intel.seo-audit'],
};

describe('module tab access', () => {
    it.each([
        [1, 0],
        [0, 1],
        [1, 1],
    ])(
        'allows reports when either resource is available (%i, %i)',
        (analytics, seo) => {
            expect(
                blockedTabFeature(reports, {
                    'site-intel.analytics': { limit: analytics },
                    'site-intel.seo-audit': { limit: seo },
                })
            ).toBeNull();
        }
    );

    it('links to the first resource when every resource is unavailable', () => {
        expect(
            blockedTabFeature(reports, {
                'site-intel.analytics': { limit: 0 },
                'site-intel.seo-audit': { limit: 0 },
            })
        ).toBe('site-intel.analytics');
    });

    it('preserves access for unavailable account capability data', () => {
        expect(blockedTabFeature(reports)).toBeNull();
        expect(
            blockedTabFeature(reports, {
                'site-intel.analytics': { limit: 0 },
            })
        ).toBeNull();
    });

    it('preserves scalar resource and tab key behavior', () => {
        expect(
            blockedTabFeature(
                { key: 'analytics', accessKey: 'youtube.analytics' },
                { 'youtube.analytics': { limit: 0 } }
            )
        ).toBe('youtube.analytics');
        expect(
            blockedTabFeature({ key: 'analytics' }, { analytics: { limit: 0 } })
        ).toBe('analytics');
        expect(
            blockedTabFeature(
                { key: 'analytics', accessKeys: [] },
                { analytics: { limit: 1 } }
            )
        ).toBeNull();
    });
});
