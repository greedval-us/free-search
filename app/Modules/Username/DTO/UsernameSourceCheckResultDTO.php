<?php

namespace App\Modules\Username\DTO;

use App\Modules\Username\Enums\UsernameSearchStatus;

final class UsernameSourceCheckResultDTO
{
    public function __construct(
        public readonly string $key,
        public readonly string $name,
        public readonly string $profileUrl,
        public readonly string $regionGroup,
        public readonly string $primaryUsersRegion,
        public readonly UsernameSearchStatus $status,
        public readonly ?int $httpStatus,
        public readonly int $confidence,
        public readonly ?string $error = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'profileUrl' => $this->profileUrl,
            'regionGroup' => $this->regionGroup,
            'primaryUsersRegion' => $this->primaryUsersRegion,
            'status' => $this->status->value,
            'httpStatus' => $this->httpStatus,
            'confidence' => $this->confidence,
            'error' => $this->error,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            key: (string) ($data['key'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            profileUrl: (string) ($data['profileUrl'] ?? ''),
            regionGroup: (string) ($data['regionGroup'] ?? 'global'),
            primaryUsersRegion: (string) ($data['primaryUsersRegion'] ?? 'global'),
            status: UsernameSearchStatus::tryFrom((string) ($data['status'] ?? 'unknown')) ?? UsernameSearchStatus::Unknown,
            httpStatus: isset($data['httpStatus']) ? (int) $data['httpStatus'] : null,
            confidence: isset($data['confidence']) ? (int) $data['confidence'] : 0,
            error: isset($data['error']) ? (string) $data['error'] : null,
        );
    }
}
