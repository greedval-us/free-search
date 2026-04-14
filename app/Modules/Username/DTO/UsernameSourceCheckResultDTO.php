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
            'error' => $this->error,
        ];
    }
}
