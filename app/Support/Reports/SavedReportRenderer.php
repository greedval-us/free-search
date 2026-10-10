<?php

namespace App\Support\Reports;

use Illuminate\Support\Traits\Localizable;

final class SavedReportRenderer
{
    use Localizable;

    /** @param array<string, mixed> $data */
    public function json(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /** @param array<string, mixed> $viewData */
    public function html(string $view, array $viewData, string $locale): string
    {
        return $this->withLocale($locale, fn (): string => view($view, [...$viewData, 'locale' => $locale])->render());
    }
}
