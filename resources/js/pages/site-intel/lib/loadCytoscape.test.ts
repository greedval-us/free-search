import { beforeEach, describe, expect, it, vi } from 'vitest';
import {
    loadCytoscape,
    resetCytoscapeLoader,
} from '@/pages/site-intel/lib/loadCytoscape';
import type {
    CytoscapeFactory,
    CytoscapeModuleLoader,
} from '@/pages/site-intel/lib/loadCytoscape';

const cytoscapeFactory = vi.fn() as unknown as CytoscapeFactory;

describe('loadCytoscape', () => {
    beforeEach(() => {
        resetCytoscapeLoader();
    });

    it('loads the graph runtime only when requested and caches it', async () => {
        const loader = vi.fn(async () => ({ default: cytoscapeFactory }));

        const firstLoad = loadCytoscape(loader);
        const secondLoad = loadCytoscape(loader);

        expect(loader).toHaveBeenCalledOnce();
        await expect(firstLoad).resolves.toBe(cytoscapeFactory);
        await expect(secondLoad).resolves.toBe(cytoscapeFactory);
    });

    it('allows a later retry after a failed import', async () => {
        const error = new Error('Graph runtime unavailable');
        const failedLoader = vi.fn(async () => {
            throw error;
        }) as CytoscapeModuleLoader;

        await expect(loadCytoscape(failedLoader)).rejects.toBe(error);

        const retryLoader = vi.fn(async () => ({
            default: cytoscapeFactory,
        }));

        await expect(loadCytoscape(retryLoader)).resolves.toBe(
            cytoscapeFactory
        );
        expect(retryLoader).toHaveBeenCalledOnce();
    });
});
