import { renderToString } from '@vue/server-renderer';
import { describe, expect, it, vi } from 'vitest';
import { createSSRApp, defineComponent, h, reactive, shallowRef } from 'vue';
import type { AccountAccess } from '@/types';
import Billing from './Billing.vue';

vi.mock('@inertiajs/vue3', () => ({
    Head: defineComponent({ render: () => null }),
    Link: defineComponent({
        props: ['href'],
        setup:
            (props, { slots }) =>
            () =>
                h('a', { href: props.href }, slots.default?.()),
    }),
    useForm: (data: Record<string, string>) =>
        reactive({
            ...data,
            errors: {},
            processing: false,
            post: vi.fn(),
            reset: vi.fn(),
        }),
}));

vi.mock('@/composables/useI18n', () => ({
    useI18n: () => ({ t: (key: string) => key, locale: shallowRef('en') }),
}));

vi.mock('@/components/ui/button', () => ({
    Button: defineComponent({
        setup:
            (_, { slots }) =>
            () =>
                h('button', slots.default?.()),
    }),
}));

describe('Billing checkout availability', () => {
    it.each([false, true])(
        'keeps token activation without advertising placeholder purchases (flag=%s)',
        async (checkoutEnabled) => {
            const html = await renderToString(
                createSSRApp(Billing, {
                    access: {
                        plan: 'free',
                        subscription: null,
                    } as AccountAccess,
                    plans: { free: {}, plus: {}, pro: {} },
                    checkoutEnabled,
                })
            );

            expect(html).toContain('activation_token');
            expect(html).toContain('settings.billingPage.token.button');
            expect(html).toContain('settings.billingPage.hero.textDisabled');
            expect(html).not.toContain('/settings/placeholder');
            expect(html).not.toContain('settings.billingPage.payButton');
        }
    );
});
