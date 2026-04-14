<?php

namespace App\Modules\Username;

use App\Modules\Username\DTO\UsernameSourceDTO;

final class UsernameSourceCatalog
{
    /**
     * @return array<int, UsernameSourceDTO>
     */
    public function all(): array
    {
        /** @var mixed $rawSources */
        $rawSources = config('username.sources', []);

        if (!is_array($rawSources)) {
            return [];
        }

        $sources = [];

        foreach ($rawSources as $rawSource) {
            if (!is_array($rawSource)) {
                continue;
            }

            $key = trim((string) ($rawSource['key'] ?? ''));
            $name = trim((string) ($rawSource['name'] ?? ''));
            $profileTemplate = trim((string) ($rawSource['profile_template'] ?? ''));

            if ($key === '' || $name === '' || $profileTemplate === '') {
                continue;
            }

            $notFoundMarkers = [];

            if (isset($rawSource['not_found_markers']) && is_array($rawSource['not_found_markers'])) {
                foreach ($rawSource['not_found_markers'] as $marker) {
                    $markerValue = trim((string) $marker);

                    if ($markerValue !== '') {
                        $notFoundMarkers[] = $markerValue;
                    }
                }
            }

            $sources[] = new UsernameSourceDTO(
                key: $key,
                name: $name,
                profileTemplate: $profileTemplate,
                regionGroup: trim((string) ($rawSource['region_group'] ?? 'global')) ?: 'global',
                primaryUsersRegion: trim((string) ($rawSource['primary_users_region'] ?? 'global')) ?: 'global',
                notFoundMarkers: $notFoundMarkers,
                strictProfileUri: (bool) ($rawSource['strict_profile_uri'] ?? false),
            );
        }

        return $sources;
    }
}
