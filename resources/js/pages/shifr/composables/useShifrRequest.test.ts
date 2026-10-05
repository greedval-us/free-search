import { afterEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';
import { apiRequest } from '@/lib/api';
import { hash } from '@/routes/shifr';
import { useShifrRequest } from './useShifrRequest';

vi.mock('@/lib/api', () => ({
    apiRequest: vi.fn(),
    resolveClientErrorMessage: () => 'failed',
}));

describe('useShifrRequest', () => {
    afterEach(() => {
        vi.unstubAllGlobals();
        vi.clearAllMocks();
    });

    it('sends sensitive values in the POST body with shared CSRF handling and no query', async () => {
        vi.mocked(apiRequest).mockResolvedValue({
            ok: true,
            data: { hash: 'result' },
        });
        const request = useShifrRequest(hash.url(), () => 'failed', ref(true));

        await request.run(
            new URLSearchParams({ text: 'private input', hmac_key: 'test-key' })
        );

        expect(apiRequest).toHaveBeenCalledWith('/shifr/hash', {
            method: 'POST',
            body: { text: 'private input', hmac_key: 'test-key' },
        });
        expect(request.result.value).toEqual({ hash: 'result' });
        expect(request.loading.value).toBe(false);
    });
});
