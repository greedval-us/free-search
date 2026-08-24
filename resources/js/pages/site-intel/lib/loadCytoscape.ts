import type cytoscape from 'cytoscape';

export type CytoscapeFactory = typeof cytoscape;
export type CytoscapeModuleLoader = () => Promise<{
    default: CytoscapeFactory;
}>;

let cytoscapePromise: Promise<CytoscapeFactory> | null = null;

export const loadCytoscape = (
    loader: CytoscapeModuleLoader = () => import('cytoscape')
): Promise<CytoscapeFactory> => {
    if (cytoscapePromise) {
        return cytoscapePromise;
    }

    cytoscapePromise = loader()
        .then((module) => module.default)
        .catch((error: unknown) => {
            cytoscapePromise = null;

            throw error;
        });

    return cytoscapePromise;
};

export const resetCytoscapeLoader = (): void => {
    cytoscapePromise = null;
};
