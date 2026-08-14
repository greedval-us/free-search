<?php

namespace App\Modules\ParserSupport;

final readonly class ParserRunExecutionConfig
{
    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromArray(array $config): self
    {
        return new self(
            queueEnabled: (bool) data_get($config, 'queue.enabled', true),
            queueName: trim((string) data_get($config, 'queue.name', 'default')) ?: 'default',
            stepDelaySeconds: max(0, (int) data_get($config, 'queue.step_delay_seconds', 2)),
        );
    }

    public function __construct(
        private bool $queueEnabled,
        private string $queueName,
        private int $stepDelaySeconds,
    ) {}

    public function queueEnabled(): bool
    {
        return $this->queueEnabled;
    }

    public function queueName(): string
    {
        return $this->queueName;
    }

    public function stepDelaySeconds(): int
    {
        return $this->stepDelaySeconds;
    }
}
