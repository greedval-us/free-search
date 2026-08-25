import { describe, expect, it } from 'vitest';
import { formatPageTitle } from '@/lib/pageTitle';

describe('formatPageTitle', () => {
    it('does not append an app name already present in the title', () => {
        expect(
            formatPageTitle('Uraboros | Intelligence Workspace', 'Uraboros')
        ).toBe('Uraboros | Intelligence Workspace');
    });

    it('appends the app name to an unbranded page title', () => {
        expect(formatPageTitle('Privacy Policy', 'Uraboros')).toBe(
            'Privacy Policy - Uraboros'
        );
    });
});
