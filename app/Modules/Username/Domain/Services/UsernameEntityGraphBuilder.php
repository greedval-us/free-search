<?php

namespace App\Modules\Username\Domain\Services;

use App\Modules\Username\Domain\DTO\UsernameSourceCheckResultDTO;

final class UsernameEntityGraphBuilder
{
    /**
     * @param array<int, UsernameSourceCheckResultDTO> $items
     * @return array<string, mixed>
     */
    public function build(string $username, array $items): array
    {
        $usernameNodeId = 'username:'.$username;
        $nodes = [
            [
                'id' => $usernameNodeId,
                'type' => 'username',
                'label' => $username,
            ],
        ];
        $edges = [];
        $nodeSeen = [
            $usernameNodeId => true,
        ];
        $edgeSeen = [];

        foreach ($items as $item) {
            $platformNodeId = 'platform:'.$item->key;
            $regionNodeId = 'region:'.$item->regionGroup;
            $categoryNodeId = 'category:'.$item->category;
            $domainNodeId = $item->profileDomain !== '' ? 'domain:'.$item->profileDomain : null;

            $this->addNodeIfMissing($nodes, $nodeSeen, [
                'id' => $platformNodeId,
                'type' => 'platform',
                'label' => $item->name,
                'status' => $item->status->value,
                'confidence' => $item->confidence,
            ]);

            $this->addNodeIfMissing($nodes, $nodeSeen, [
                'id' => $regionNodeId,
                'type' => 'region',
                'label' => $item->regionGroup,
            ]);

            $this->addNodeIfMissing($nodes, $nodeSeen, [
                'id' => $categoryNodeId,
                'type' => 'category',
                'label' => $item->category,
            ]);

            if ($domainNodeId !== null && $item->status->value === 'found') {
                $this->addNodeIfMissing($nodes, $nodeSeen, [
                    'id' => $domainNodeId,
                    'type' => 'domain',
                    'label' => $item->profileDomain,
                ]);
            }

            $this->addEdgeIfMissing($edges, $edgeSeen, [
                'source' => $usernameNodeId,
                'target' => $platformNodeId,
                'kind' => 'presence',
                'status' => $item->status->value,
                'confidence' => $item->confidence,
            ]);

            $this->addEdgeIfMissing($edges, $edgeSeen, [
                'source' => $platformNodeId,
                'target' => $regionNodeId,
                'kind' => 'region',
            ]);

            $this->addEdgeIfMissing($edges, $edgeSeen, [
                'source' => $platformNodeId,
                'target' => $categoryNodeId,
                'kind' => 'category',
            ]);

            if ($domainNodeId !== null && $item->status->value === 'found') {
                $this->addEdgeIfMissing($edges, $edgeSeen, [
                    'source' => $platformNodeId,
                    'target' => $domainNodeId,
                    'kind' => 'domain',
                    'status' => $item->status->value,
                ]);
            }
        }

        return [
            'nodes' => $nodes,
            'edges' => $edges,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $nodes
     * @param array<string, bool> $nodeSeen
     * @param array<string, mixed> $node
     */
    private function addNodeIfMissing(array &$nodes, array &$nodeSeen, array $node): void
    {
        $nodeId = (string) ($node['id'] ?? '');

        if ($nodeId === '' || isset($nodeSeen[$nodeId])) {
            return;
        }

        $nodeSeen[$nodeId] = true;
        $nodes[] = $node;
    }

    /**
     * @param array<int, array<string, mixed>> $edges
     * @param array<string, bool> $edgeSeen
     * @param array<string, mixed> $edge
     */
    private function addEdgeIfMissing(array &$edges, array &$edgeSeen, array $edge): void
    {
        $source = (string) ($edge['source'] ?? '');
        $target = (string) ($edge['target'] ?? '');
        $kind = (string) ($edge['kind'] ?? '');

        if ($source === '' || $target === '' || $kind === '') {
            return;
        }

        $edgeKey = $source.'|'.$target.'|'.$kind;

        if (isset($edgeSeen[$edgeKey])) {
            return;
        }

        $edgeSeen[$edgeKey] = true;
        $edges[] = $edge;
    }
}
