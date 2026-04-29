<?php

namespace App\Modules\EmailIntel\Application\Services\EmailIntel;

final class EmailEntityGraphBuilder
{
    /**
     * @param array<string, mixed> $target
     * @param array<string, mixed> $dns
     * @param array<string, mixed> $profile
     * @param array<string, mixed> $analytics
     * @param array<int, array<string, string>> $signals
     * @return array<string, mixed>
     */
    public function build(array $target, array $dns, array $profile, array $analytics, array $signals): array
    {
        $nodes = [];
        $edges = [];
        $nodeSeen = [];
        $edgeSeen = [];

        $email = (string) $target['email'];
        $domain = (string) $target['domain'];
        $localPart = (string) $target['localPart'];
        $emailNode = 'email:' . $email;
        $domainNode = 'domain:' . $domain;
        $localNode = 'local:' . $localPart;

        $this->addNode($nodes, $nodeSeen, ['id' => $emailNode, 'type' => 'email', 'label' => $email]);
        $this->addNode($nodes, $nodeSeen, ['id' => $domainNode, 'type' => 'domain', 'label' => $domain]);
        $this->addNode($nodes, $nodeSeen, ['id' => $localNode, 'type' => 'local_part', 'label' => $localPart]);
        $this->addEdge($edges, $edgeSeen, ['source' => $emailNode, 'target' => $domainNode, 'kind' => 'uses_domain']);
        $this->addEdge($edges, $edgeSeen, ['source' => $emailNode, 'target' => $localNode, 'kind' => 'has_local_part']);

        $provider = is_array($analytics['provider'] ?? null) ? $analytics['provider'] : [];
        if (($provider['name'] ?? 'Unknown') !== 'Unknown') {
            $providerNode = 'provider:' . $provider['name'];
            $this->addNode($nodes, $nodeSeen, ['id' => $providerNode, 'type' => 'provider', 'label' => (string) $provider['name']]);
            $this->addEdge($edges, $edgeSeen, ['source' => $domainNode, 'target' => $providerNode, 'kind' => 'served_by']);
        }

        foreach (($dns['mx'] ?? []) as $record) {
            $host = (string) ($record['host'] ?? '');
            if ($host === '') {
                continue;
            }

            $mxNode = 'mx:' . $host;
            $this->addNode($nodes, $nodeSeen, ['id' => $mxNode, 'type' => 'mx_host', 'label' => $host]);
            $this->addEdge($edges, $edgeSeen, ['source' => $domainNode, 'target' => $mxNode, 'kind' => 'routes_mail_to']);
        }

        foreach (($analytics['spf']['includes'] ?? []) as $include) {
            $includeNode = 'spf:' . $include;
            $this->addNode($nodes, $nodeSeen, ['id' => $includeNode, 'type' => 'spf_include', 'label' => (string) $include]);
            $this->addEdge($edges, $edgeSeen, ['source' => $domainNode, 'target' => $includeNode, 'kind' => 'authorizes_sender']);
        }

        foreach (array_merge($analytics['dmarc']['rua'] ?? [], $analytics['dmarc']['ruf'] ?? []) as $mailbox) {
            $mailbox = preg_replace('/^mailto:/i', '', (string) $mailbox) ?? (string) $mailbox;
            if ($mailbox === '' || !str_contains($mailbox, '@')) {
                continue;
            }

            [, $reportDomain] = explode('@', $mailbox, 2);
            $reportNode = 'report_email:' . $mailbox;
            $reportDomainNode = 'report_domain:' . $reportDomain;
            $this->addNode($nodes, $nodeSeen, ['id' => $reportNode, 'type' => 'dmarc_report_email', 'label' => $mailbox]);
            $this->addNode($nodes, $nodeSeen, ['id' => $reportDomainNode, 'type' => 'dmarc_report_domain', 'label' => $reportDomain]);
            $this->addEdge($edges, $edgeSeen, ['source' => $domainNode, 'target' => $reportNode, 'kind' => 'reports_to']);
            $this->addEdge($edges, $edgeSeen, ['source' => $reportNode, 'target' => $reportDomainNode, 'kind' => 'belongs_to']);
        }

        $hashNode = 'hash:' . (string) $profile['gravatarHash'];
        $this->addNode($nodes, $nodeSeen, ['id' => $hashNode, 'type' => 'gravatar_hash', 'label' => (string) $profile['gravatarHash']]);
        $this->addEdge($edges, $edgeSeen, ['source' => $emailNode, 'target' => $hashNode, 'kind' => 'hashes_to']);

        foreach ($signals as $signal) {
            $signalNode = 'signal:' . $signal['type'];
            $this->addNode($nodes, $nodeSeen, [
                'id' => $signalNode,
                'type' => 'risk_signal',
                'label' => $signal['type'],
                'level' => $signal['level'],
            ]);
            $this->addEdge($edges, $edgeSeen, ['source' => $emailNode, 'target' => $signalNode, 'kind' => 'triggered']);
        }

        return ['nodes' => $nodes, 'edges' => $edges];
    }

    /**
     * @param array<int, array<string, mixed>> $nodes
     * @param array<string, bool> $seen
     * @param array<string, mixed> $node
     */
    private function addNode(array &$nodes, array &$seen, array $node): void
    {
        $id = (string) ($node['id'] ?? '');
        if ($id === '' || isset($seen[$id])) {
            return;
        }

        $seen[$id] = true;
        $nodes[] = $node;
    }

    /**
     * @param array<int, array<string, mixed>> $edges
     * @param array<string, bool> $seen
     * @param array<string, mixed> $edge
     */
    private function addEdge(array &$edges, array &$seen, array $edge): void
    {
        $key = ($edge['source'] ?? '') . '|' . ($edge['target'] ?? '') . '|' . ($edge['kind'] ?? '');
        if ($key === '||' || isset($seen[$key])) {
            return;
        }

        $seen[$key] = true;
        $edges[] = $edge;
    }
}
