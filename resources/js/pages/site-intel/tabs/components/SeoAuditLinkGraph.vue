<script setup lang="ts">
import cytoscape from 'cytoscape';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

type Node = {
    id: string;
    url: string;
    title: string;
    status: number;
    indexable: boolean;
    inDegree: number;
    outDegree: number;
    riskFlags: {
        non200: boolean;
        noindex: boolean;
        orphanRisk: boolean;
    };
};

type Edge = {
    source: string;
    target: string;
};

const props = defineProps<{
    nodes: Node[];
    edges: Edge[];
    filters?: {
        non200Only: boolean;
        noindexOnly: boolean;
        orphanRiskOnly: boolean;
        mode: 'any' | 'all';
    };
}>();

const root = ref<HTMLDivElement | null>(null);
let cy: cytoscape.Core | null = null;

const render = () => {
    if (!root.value) {
        return;
    }

    if (cy) {
        cy.destroy();
        cy = null;
    }

    const activeFilters = props.filters ?? {
        non200Only: false,
        noindexOnly: false,
        orphanRiskOnly: false,
        mode: 'all',
    };

    const selectedChecks: Array<(node: Node) => boolean> = [];
    if (activeFilters.non200Only) {
        selectedChecks.push((node) => node.riskFlags.non200);
    }
    if (activeFilters.noindexOnly) {
        selectedChecks.push((node) => node.riskFlags.noindex);
    }
    if (activeFilters.orphanRiskOnly) {
        selectedChecks.push((node) => node.riskFlags.orphanRisk);
    }

    const visibleNodeMap = new Map<string, Node>();
    for (const node of props.nodes) {
        if (selectedChecks.length > 0) {
            const isMatch =
                activeFilters.mode === 'any'
                    ? selectedChecks.some((check) => check(node))
                    : selectedChecks.every((check) => check(node));
            if (!isMatch) {
                continue;
            }
        }
        visibleNodeMap.set(node.id, node);
    }

    const visibleEdges = props.edges.filter(
        (edge) => visibleNodeMap.has(edge.source) && visibleNodeMap.has(edge.target),
    );

    cy = cytoscape({
        container: root.value,
        elements: [
            ...Array.from(visibleNodeMap.values()).map((node) => ({
                data: {
                    id: node.id,
                    label: node.title.trim() !== '' ? node.title : node.url,
                    inDegree: node.inDegree,
                    outDegree: node.outDegree,
                    indexable: node.indexable,
                    non200: node.riskFlags.non200,
                    noindex: node.riskFlags.noindex,
                    orphanRisk: node.riskFlags.orphanRisk,
                },
                classes: [
                    node.indexable ? 'indexable' : 'noindex',
                    node.riskFlags.non200 ? 'risk-non200' : '',
                    node.riskFlags.noindex ? 'risk-noindex' : '',
                    node.riskFlags.orphanRisk ? 'risk-orphan' : '',
                ]
                    .filter((value) => value !== '')
                    .join(' '),
            })),
            ...visibleEdges.map((edge, index) => ({
                data: {
                    id: `edge-${index}`,
                    source: edge.source,
                    target: edge.target,
                },
            })),
        ],
        minZoom: 0.4,
        maxZoom: 2.2,
        wheelSensitivity: 0.55,
        style: [
            {
                selector: 'node',
                style: {
                    label: 'data(label)',
                    'font-size': 9,
                    color: '#e2e8f0',
                    'text-wrap': 'wrap',
                    'text-max-width': '150px',
                    'text-valign': 'bottom',
                    'text-halign': 'center',
                    'text-margin-y': 8,
                    width: 'mapData(inDegree, 0, 15, 20, 46)',
                    height: 'mapData(inDegree, 0, 15, 20, 46)',
                    'border-width': 1,
                    'border-color': '#0f172a',
                    'background-color': '#06b6d4',
                },
            },
            {
                selector: 'node.risk-non200',
                style: {
                    'background-color': '#ef4444',
                    'border-color': '#7f1d1d',
                    'border-width': 2,
                },
            },
            {
                selector: 'node.noindex',
                style: {
                    'background-color': '#f59e0b',
                },
            },
            {
                selector: 'node.risk-orphan',
                style: {
                    'background-color': '#8b5cf6',
                },
            },
            {
                selector: 'edge',
                style: {
                    width: 1.5,
                    'line-color': '#64748b',
                    'target-arrow-color': '#64748b',
                    'target-arrow-shape': 'triangle',
                    'curve-style': 'bezier',
                    opacity: 0.85,
                },
            },
            {
                selector: '.dimmed',
                style: {
                    opacity: 0.12,
                },
            },
        ],
        layout: {
            name: 'cose',
            animate: false,
            fit: true,
            padding: 28,
            nodeRepulsion: 600000,
            edgeElasticity: 60,
            idealEdgeLength: 120,
        },
    });

    cy.on('tap', 'node', (event) => {
        if (!cy) {
            return;
        }
        const node = event.target;
        cy.elements().addClass('dimmed');
        node.removeClass('dimmed');
        node.connectedEdges().removeClass('dimmed');
        node.connectedEdges().connectedNodes().removeClass('dimmed');
    });

    cy.on('tap', (event) => {
        if (!cy || event.target !== cy) {
            return;
        }
        cy.elements().removeClass('dimmed');
    });
};

onMounted(() => {
    render();
});

watch(
    () => [props.nodes, props.edges, props.filters],
    () => {
        render();
    },
    { deep: true },
);

onBeforeUnmount(() => {
    if (cy) {
        cy.destroy();
        cy = null;
    }
});
</script>

<template>
    <div class="rounded-lg border border-border/70 bg-background/60 p-3">
        <div ref="root" class="h-[26rem] w-full rounded-md border border-border/70 bg-slate-950/80" />
    </div>
</template>
