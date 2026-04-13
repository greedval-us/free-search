<?php

namespace App\Http\Requests\Gdelt;

use App\Modules\Gdelt\DTO\Request\GdeltSearchQueryDTO;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class GdeltSearchRequest extends FormRequest
{
    private const SORT_VALUES = [
        'datedesc',
        'dateasc',
        'hybridrel',
        'toneasc',
        'tonedesc',
    ];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'query' => ['required', 'string', 'max:500'],
            'maxRecords' => ['nullable', 'integer', 'min:1', 'max:250'],
            'sort' => ['nullable', 'string', 'in:' . implode(',', self::SORT_VALUES)],
            'dateFrom' => ['nullable', 'date_format:Y-m-d'],
            'dateTo' => ['nullable', 'date_format:Y-m-d'],
            'sourceCountry' => ['nullable', 'string', 'size:2', 'alpha'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $dateFrom = $this->input('dateFrom');
            $dateTo = $this->input('dateTo');

            if (($dateFrom && !$dateTo) || (!$dateFrom && $dateTo)) {
                $validator->errors()->add('dateTo', __('Please provide both dates for a custom range.'));
                return;
            }

            if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
                $validator->errors()->add('dateFrom', __('Date "from" must be less than or equal to date "to".'));
            }
        });
    }

    public function toQueryDTO(): GdeltSearchQueryDTO
    {
        $query = trim((string) $this->validated('query'));
        $maxRecords = (int) ($this->validated('maxRecords') ?? 50);
        $sort = strtolower(trim((string) ($this->validated('sort') ?? 'datedesc')));
        $sourceCountry = strtoupper(trim((string) ($this->validated('sourceCountry') ?? '')));

        $startDateTime = null;
        $endDateTime = null;
        $dateFrom = $this->validated('dateFrom');
        $dateTo = $this->validated('dateTo');

        if (is_string($dateFrom) && is_string($dateTo) && $dateFrom !== '' && $dateTo !== '') {
            $startDateTime = Carbon::createFromFormat('Y-m-d', $dateFrom, 'UTC')->startOfDay()->format('YmdHis');
            $endDateTime = Carbon::createFromFormat('Y-m-d', $dateTo, 'UTC')->endOfDay()->format('YmdHis');
        }

        return new GdeltSearchQueryDTO(
            query: $query,
            maxRecords: max(1, min($maxRecords, 250)),
            sort: in_array($sort, self::SORT_VALUES, true) ? $sort : 'datedesc',
            startDateTime: $startDateTime,
            endDateTime: $endDateTime,
            sourceCountry: $sourceCountry !== '' ? $sourceCountry : null,
        );
    }
}
