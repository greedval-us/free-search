<script setup lang="ts">
import cytoscape from 'cytoscape';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import type { UsernameGraphEdge, UsernameGraphNode } from '@/pages/username/types';

type Props = {
    nodes: UsernameGraphNode[];
    edges: UsernameGraphEdge[];
};

const props = defineProps<Props>();

const root = ref<HTMLDivElement | null>(null);
let cy: cytoscape.Core | null = null;

const nodeColor = (node: UsernameGraphNode): string => {
    if (node.type === 'username') return '#06b6d4';
    if (node.type === 'region') return '#8b5cf6';
    if (node.type === 'category') return '#14b8a6';
    if (node.type === 'domain') return '#f97316';
    if (node.status === 'found') return '#10b981';
    if (node.status === 'not_found') return '#64748b';
    return '#f59e0b';
};

const edgeColor = (edge: UsernameGraphEdge): string => {
    if (edge.kind === 'region') return '#475569';
    if (edge.kind === 'category') return '#0f766e';
    if (edge.kind === 'domain') return '#9a3412';
    if (edge.status === 'found') return '#10b981';
    if (edge.status === 'not_found') return '#64748b';
    return '#f59e0b';
};

const shortLabel = (label: string): string => {
    const clean = label.trim();

    if (clean.length <= 16) {
        return clean;
    }

    return `${clean.slice(0, 15)}...`;
};

const buildElements = () => {
    const nodeElements = props.nodes.map((node) => ({
        data: {
            id: node.id,
            label: node.label,
            shortLabel: shortLabel(node.label),
            type: node.type,
            status: node.status ?? '',
            confidence: node.confidence ?? null,
        },
        style: {
            'background-color': nodeColor(node),
            label: shortLabel(node.label),
        },
        classes: node.type,
    }));

    const edgeElements = props.edges.map((edge, index) => ({
        data: {
            id: `edge-${index}-${edge.source}-${edge.target}`,
            source: edge.source,
            target: edge.target,
            kind: edge.kind,
            status: edge.status ?? '',
            confidence: edge.confidence ?? null,
        },
        style: {
            'line-color': edgeColor(edge),
            'target-arrow-color': edgeColor(edge),
        },
    }));

    return [...nodeElements, ...edgeElements];
};

const renderGraph = () => {
    if (!root.value) return;

    if (cy) {
        cy.destroy();
        cy = null;
    }

    cy = cytoscape({
        container: root.value,
        elements: buildElements(),
        wheelSensitivity: 0.55,
        minZoom: 0.4,
        maxZoom: 2.5,
        style: [
            {
                selector: 'node',
                style: {
                    'font-size': 11,
                    color: '#f8fafc',
                    'text-valign': 'bottom',
                    'text-halign': 'center',
                    'text-margin-y': 10,
                    'text-wrap': 'wrap',
                    'text-max-width': '130px',
                    'text-background-color': '#0b1220',
                    'text-background-opacity': 0.92,
                    'text-background-shape': 'roundrectangle',
                    'text-border-color': '#334155',
                    'text-border-width': 1,
                    'text-border-opacity': 0.9,
                    'text-background-padding': '3px',
                    'min-zoomed-font-size': 8,
                    width: 28,
                    height: 28,
                    'border-width': 2,
                    'border-color': '#0f172a',
                    'text-outline-width': 1,
                    'text-outline-color': '#020617',
                },
            },
            {
                selector: 'node.username',
                style: {
                    width: 44,
                    height: 44,
                    'font-size': 12,
                    'font-weight': 700,
                },
            },
            {
                selector: 'node.region',
                style: {
                    width: 32,
                    height: 32,
                    'font-size': 10,
                },
            },
            {
                selector: 'node.category',
                style: {
                    width: 34,
                    height: 34,
                    'font-size': 10,
                },
            },
            {
                selector: 'node.domain',
                style: {
                    width: 30,
                    height: 30,
                    'font-size': 9,
                },
            },
            {
                selector: 'edge',
                style: {
                    width: 1.7,
                    'curve-style': 'bezier',
                    'line-opacity': 0.8,
                    'target-arrow-shape': 'triangle',
                    'arrow-scale': 0.8,
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
            padding: 36,
            nodeRepulsion: 600000,
            edgeElasticity: 80,
            gravity: 0.5,
            idealEdgeLength: 120,
        },
    });

    cy.on('tap', 'node', (event) => {
        const node = event.target;

        if (!cy) return;

        cy.elements().addClass('dimmed');
        node.removeClass('dimmed');
        node.connectedEdges().removeClass('dimmed');
        node.connectedEdges().connectedNodes().removeClass('dimmed');
    });

    cy.on('tap', (event) => {
        if (!cy) return;
        if (event.target === cy) {
            cy.elements().removeClass('dimmed');
        }
    });
};

onMounted(() => {
    renderGraph();
});

watch(
    () => [props.nodes, props.edges],
    () => {
        renderGraph();
    },
    { deep: true }
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
        <div
            ref="root"
            class="h-[34rem] w-full rounded-md border border-border/70 bg-slate-950/80"
        />

        <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
            <span class="inline-flex items-center gap-1 rounded-full border border-input px-2 py-1"><span class="h-2 w-2 rounded-full bg-cyan-500" /> username</span>
            <span class="inline-flex items-center gap-1 rounded-full border border-input px-2 py-1"><span class="h-2 w-2 rounded-full bg-emerald-500" /> found</span>
            <span class="inline-flex items-center gap-1 rounded-full border border-input px-2 py-1"><span class="h-2 w-2 rounded-full bg-slate-500" /> not found</span>
            <span class="inline-flex items-center gap-1 rounded-full border border-input px-2 py-1"><span class="h-2 w-2 rounded-full bg-amber-500" /> unknown</span>
            <span class="inline-flex items-center gap-1 rounded-full border border-input px-2 py-1"><span class="h-2 w-2 rounded-full bg-violet-500" /> region</span>
            <span class="inline-flex items-center gap-1 rounded-full border border-input px-2 py-1"><span class="h-2 w-2 rounded-full bg-teal-500" /> category</span>
            <span class="inline-flex items-center gap-1 rounded-full border border-input px-2 py-1"><span class="h-2 w-2 rounded-full bg-orange-500" /> domain</span>
        </div>
    </div>
</template>
