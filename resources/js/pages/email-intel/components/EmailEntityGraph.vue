<script setup lang="ts">
import cytoscape from 'cytoscape';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import type { EmailIntelGraphEdge, EmailIntelGraphNode } from '../types';

type Props = {
    nodes: EmailIntelGraphNode[];
    edges: EmailIntelGraphEdge[];
};

const props = defineProps<Props>();
const root = ref<HTMLDivElement | null>(null);
let cy: cytoscape.Core | null = null;

const nodeColor = (node: EmailIntelGraphNode): string => {
    if (node.type === 'email') return '#06b6d4';
    if (node.type === 'domain') return '#f97316';
    if (node.type === 'provider') return '#8b5cf6';
    if (node.type === 'mx_host') return '#14b8a6';
    if (node.type === 'spf_include') return '#22c55e';
    if (node.type.includes('dmarc')) return '#eab308';
    if (node.type === 'risk_signal') return node.level === 'high' ? '#f43f5e' : '#f59e0b';
    return '#64748b';
};

const edgeColor = (edge: EmailIntelGraphEdge): string => {
    if (edge.kind === 'triggered') return '#f43f5e';
    if (edge.kind === 'authorizes_sender') return '#22c55e';
    if (edge.kind === 'reports_to') return '#eab308';
    if (edge.kind === 'routes_mail_to') return '#14b8a6';
    return '#64748b';
};

const shortLabel = (label: string): string => (label.length <= 18 ? label : `${label.slice(0, 17)}...`);

const renderGraph = () => {
    if (!root.value) return;

    if (cy) {
        cy.destroy();
        cy = null;
    }

    cy = cytoscape({
        container: root.value,
        elements: [
            ...props.nodes.map((node) => ({
                data: { id: node.id, label: shortLabel(node.label), type: node.type },
                style: { 'background-color': nodeColor(node), label: shortLabel(node.label) },
                classes: node.type,
            })),
            ...props.edges.map((edge, index) => ({
                data: { id: `edge-${index}`, source: edge.source, target: edge.target, kind: edge.kind },
                style: {
                    'line-color': edgeColor(edge),
                    'target-arrow-color': edgeColor(edge),
                },
            })),
        ],
        wheelSensitivity: 0.55,
        minZoom: 0.35,
        maxZoom: 2.5,
        style: [
            {
                selector: 'node',
                style: {
                    width: 30,
                    height: 30,
                    color: '#f8fafc',
                    'font-size': 10,
                    'text-valign': 'bottom',
                    'text-halign': 'center',
                    'text-margin-y': 10,
                    'text-background-color': '#0b1220',
                    'text-background-opacity': 0.92,
                    'text-background-shape': 'roundrectangle',
                    'text-background-padding': '3px',
                    'border-width': 2,
                    'border-color': '#0f172a',
                },
            },
            {
                selector: 'node.email',
                style: { width: 44, height: 44, 'font-weight': 700 },
            },
            {
                selector: 'edge',
                style: {
                    width: 1.8,
                    'curve-style': 'bezier',
                    'target-arrow-shape': 'triangle',
                    'arrow-scale': 0.8,
                    'line-opacity': 0.82,
                },
            },
            {
                selector: '.dimmed',
                style: { opacity: 0.12 },
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
        if (!cy) return;
        const node = event.target;
        cy.elements().addClass('dimmed');
        node.removeClass('dimmed');
        node.connectedEdges().removeClass('dimmed');
        node.connectedEdges().connectedNodes().removeClass('dimmed');
    });

    cy.on('tap', (event) => {
        if (cy && event.target === cy) {
            cy.elements().removeClass('dimmed');
        }
    });
};

onMounted(renderGraph);
watch(() => [props.nodes, props.edges], renderGraph, { deep: true });
onBeforeUnmount(() => {
    if (cy) {
        cy.destroy();
        cy = null;
    }
});
</script>

<template>
    <div class="rounded-lg border border-border/70 bg-background/60 p-3">
        <div ref="root" class="h-[34rem] w-full rounded-md border border-border/70 bg-slate-950/80" />
    </div>
</template>
