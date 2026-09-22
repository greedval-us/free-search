<?php

namespace App\Services\PublicSite;

final class PublicFeatureCatalog
{
    /** @return list<array{slug: string, url: string, workspaceUrl: string}> */
    public function pages(): array
    {
        $pages = [];

        foreach (config('public_features', []) as $slug => $feature) {
            $pages[] = [
                'slug' => $slug,
                'url' => route('features.show', ['feature' => $slug], absolute: false),
                'workspaceUrl' => route($feature['route'], $feature['parameters'] ?? [], absolute: false),
            ];
        }

        return $pages;
    }
}
