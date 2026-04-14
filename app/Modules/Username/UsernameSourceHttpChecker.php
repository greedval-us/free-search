<?php

namespace App\Modules\Username;

use App\Modules\Username\Contracts\UsernameSourceCheckerInterface;
use App\Modules\Username\DTO\UsernameSourceCheckResultDTO;
use App\Modules\Username\DTO\UsernameSourceDTO;
use App\Modules\Username\Enums\UsernameSearchStatus;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

final class UsernameSourceHttpChecker implements UsernameSourceCheckerInterface
{
    /**
     * @param array<int, UsernameSourceDTO> $sources
     * @return array<int, UsernameSourceCheckResultDTO>
     */
    public function checkMany(array $sources, string $username): array
    {
        $responses = Http::pool(function (Pool $pool) use ($sources, $username): array {
            $requests = [];

            foreach ($sources as $source) {
                $profileUrl = sprintf($source->profileTemplate, $username);
                $requestConfig = $this->requestConfig();

                $requests[$source->key] = $pool
                    ->as($source->key)
                    ->withHeaders($this->requestHeaders())
                    ->connectTimeout($requestConfig['connect_timeout'])
                    ->timeout($requestConfig['timeout'])
                    ->withOptions([
                        'allow_redirects' => [
                            'max' => $requestConfig['max_redirects'],
                        ],
                    ])
                    ->get($profileUrl);
            }

            return $requests;
        });

        $results = [];

        foreach ($sources as $source) {
            $profileUrl = sprintf($source->profileTemplate, $username);
            $response = $responses[$source->key] ?? null;

            $results[] = $this->mapResponseToResult($response, $source, $profileUrl);
        }

        return $results;
    }

    private function mapResponseToResult(
        mixed $response,
        UsernameSourceDTO $source,
        string $profileUrl
    ): UsernameSourceCheckResultDTO {
        if (!($response instanceof Response)) {
            return new UsernameSourceCheckResultDTO(
                key: $source->key,
                name: $source->name,
                profileUrl: $profileUrl,
                regionGroup: $source->regionGroup,
                primaryUsersRegion: $source->primaryUsersRegion,
                status: UsernameSearchStatus::Unknown,
                httpStatus: null,
                confidence: 20,
                error: 'No response',
            );
        }

        $statusCode = $response->status();

        if (in_array($statusCode, [404, 410], true)) {
            return new UsernameSourceCheckResultDTO(
                key: $source->key,
                name: $source->name,
                profileUrl: $profileUrl,
                regionGroup: $source->regionGroup,
                primaryUsersRegion: $source->primaryUsersRegion,
                status: UsernameSearchStatus::NotFound,
                httpStatus: $statusCode,
                confidence: $this->calculateConfidence(UsernameSearchStatus::NotFound, $statusCode, null),
            );
        }

        if ($response->successful()) {
            if ($this->looksLikeNotFound($response, $source, $profileUrl)) {
                return new UsernameSourceCheckResultDTO(
                    key: $source->key,
                    name: $source->name,
                    profileUrl: $profileUrl,
                    regionGroup: $source->regionGroup,
                    primaryUsersRegion: $source->primaryUsersRegion,
                    status: UsernameSearchStatus::NotFound,
                    httpStatus: $statusCode,
                    confidence: $this->calculateConfidence(UsernameSearchStatus::NotFound, $statusCode, null),
                );
            }

            return new UsernameSourceCheckResultDTO(
                key: $source->key,
                name: $source->name,
                profileUrl: $profileUrl,
                regionGroup: $source->regionGroup,
                primaryUsersRegion: $source->primaryUsersRegion,
                status: UsernameSearchStatus::Found,
                httpStatus: $statusCode,
                confidence: $this->calculateConfidence(UsernameSearchStatus::Found, $statusCode, null),
            );
        }

        if (in_array($statusCode, [401, 403, 429], true)) {
            return new UsernameSourceCheckResultDTO(
                key: $source->key,
                name: $source->name,
                profileUrl: $profileUrl,
                regionGroup: $source->regionGroup,
                primaryUsersRegion: $source->primaryUsersRegion,
                status: UsernameSearchStatus::Unknown,
                httpStatus: $statusCode,
                confidence: $this->calculateConfidence(UsernameSearchStatus::Unknown, $statusCode, 'Access limited'),
                error: 'Access limited',
            );
        }

        return new UsernameSourceCheckResultDTO(
            key: $source->key,
            name: $source->name,
            profileUrl: $profileUrl,
            regionGroup: $source->regionGroup,
            primaryUsersRegion: $source->primaryUsersRegion,
            status: UsernameSearchStatus::Unknown,
            httpStatus: $statusCode,
            confidence: $this->calculateConfidence(UsernameSearchStatus::Unknown, $statusCode, $response->reason()),
            error: $response->reason(),
        );
    }

    private function looksLikeNotFound(
        Response $response,
        UsernameSourceDTO $source,
        string $profileUrl
    ): bool {
        if ($source->notFoundMarkers !== []) {
            $body = mb_strtolower($response->body());

            foreach ($source->notFoundMarkers as $marker) {
                if (str_contains($body, mb_strtolower($marker))) {
                    return true;
                }
            }
        }

        if ($source->strictProfileUri && $response->effectiveUri() !== null) {
            $effective = rtrim((string) $response->effectiveUri(), '/');
            $expected = rtrim($profileUrl, '/');

            if ($effective !== '' && $expected !== '' && $effective !== $expected) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{connect_timeout: int, timeout: int, max_redirects: int}
     */
    private function requestConfig(): array
    {
        return [
            'connect_timeout' => (int) config('username.request.connect_timeout', 6),
            'timeout' => (int) config('username.request.timeout', 8),
            'max_redirects' => (int) config('username.request.max_redirects', 5),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function requestHeaders(): array
    {
        return [
            'Accept' => 'text/html,application/xhtml+xml',
            'User-Agent' => (string) config(
                'username.request.user_agent',
                'Mozilla/5.0 (compatible; UraborosOSINT/1.0; +https://localhost)'
            ),
        ];
    }

    private function calculateConfidence(
        UsernameSearchStatus $status,
        ?int $httpStatus,
        ?string $error
    ): int {
        $score = match ($status) {
            UsernameSearchStatus::Found => 75,
            UsernameSearchStatus::NotFound => 65,
            UsernameSearchStatus::Unknown => 35,
        };

        if ($httpStatus === 200 && $status === UsernameSearchStatus::Found) {
            $score += 15;
        }

        if (in_array($httpStatus, [404, 410], true) && $status === UsernameSearchStatus::NotFound) {
            $score += 15;
        }

        if (in_array($httpStatus, [401, 403, 429], true) && $status === UsernameSearchStatus::Unknown) {
            $score -= 10;
        }

        if ($error !== null && trim($error) !== '') {
            $score -= 10;
        }

        return max(0, min(100, $score));
    }
}
