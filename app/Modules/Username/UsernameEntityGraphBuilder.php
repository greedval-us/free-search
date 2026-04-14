<?php

namespace App\Modules\Username;

use App\Modules\Username\DTO\UsernameSourceCheckResultDTO;

final class UsernameEntityGraphBuilder
{
    /**
     * @param array<int, UsernameSourceCheckResultDTO> $items
     * @return array<string, mixed>
     */
    public function build(string $username, array $items): array
    {
        $nodes = [
            [
                'id' => 'username:'.$username,
                'type' => 'username',
                'label' => $username,
            ],
        ];
        $edges = [];
        $regionSeen = [];

        foreach ($items as $item) {
            $platformNodeId = 'platform:'.$item->key;
            $regionNodeId = 'region:'.$item->regionGroup;

            $nodes[] = [
                'id' => $platformNodeId,
                'type' => 'platform',
                'label' => $item->name,
                'status' => $item->status->value,
                'confidence' => $item->confidence,
            ];

            if (!isset($regionSeen[$item->regionGroup])) {
                $nodes[] = [
                    'id' => $regionNodeId,
                    'type' => 'region',
                    'label' => $item->regionGroup,
                ];
                $regionSeen[$item->regionGroup] = true;
            }

            $edges[] = [
                'source' => 'username:'.$username,
                'target' => $platformNodeId,
                'kind' => 'presence',
                'status' => $item->status->value,
                'confidence' => $item->confidence,
            ];

            $edges[] = [
                'source' => $platformNodeId,
                'target' => $regionNodeId,
                'kind' => 'region',
            ];
        }

        return [
            'nodes' => $nodes,
            'edges' => $edges,
        ];
    }
}
