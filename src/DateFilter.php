<?php

namespace Webbingbrasil\FilamentDateFilter;

use Carbon\CarbonInterface;
use Closure;
use Filament\Forms;
use Filament\Tables\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class DateFilter extends BaseFilter
{
    protected string $column;

    protected string $operator = '=';

    protected bool $range = false;

    protected bool $displayRangeInLine = false;

    protected CarbonInterface | string | Closure | null $maxDate = null;

    protected CarbonInterface | string | Closure | null $minDate = null;

    protected CarbonInterface | string | Closure | null $defaultFrom = null;

    protected CarbonInterface | string | Closure | null $defaultUntil = null;

    protected string | Closure | null $displayFormat = 'M j, Y';

    protected string | Closure | null $timezone = null;

    protected array $labels = [
        'from' => 'From',
        'until' => 'Until',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->displayFormat = config('tables.date_format', $this->displayFormat);

        $this
            ->useColumn($this->getName())
            ->indicateUsing(function (array $state): array {
                $state = Arr::only($state, $this->range ? ['from', 'until'] : ['value']);
                if (!array_filter($state)) {
                    return [];
                }

                /** @var string $displayFormat */
                $displayFormat = $this->evaluate($this->displayFormat);

                if (!$this->range) {
                    $label = Carbon::parse($state['value'])->format($displayFormat);
                    return ["{$this->getIndicator()}: {$label}"];
                }

                $format = fn(string $field) => $state[$field]
                    ? [
                        $this->labels[$field],
                        Carbon::parse($state[$field])->format($displayFormat)
                    ] : [];

                $label = implode(' ', array_filter([
                    ...$format('from'),
                    ...$format('until'),
                ]));

                return ["{$this->getIndicator()}: {$label}"];
            });
    }

    public function apply(Builder $query, array $data = []): Builder
    {
        if (!$this->range) {
            $date = $data['value'];
            return $query
                ->when(
                    $date && $this->operator === '=',
                    fn(Builder $query): Builder => $query
                        ->where($this->column, '>=', Carbon::parse($date)->startOfDay())
                        ->where($this->column, '<=', Carbon::parse($date)->endOfDay()),
                )
                ->when(
                    $date && $this->operator !== '=',
                    fn(Builder $query): Builder => $query->where($this->column, $this->operator, $date),
                );
        }

        return $query
            ->when(
                $data['from'] && empty($data['until']),
                fn(Builder $query, $date): Builder => $query->where($this->column, '>=', $data['from']),
            )
            ->when(
                empty($data['from']) && $data['until'],
                fn(Builder $query, $date): Builder => $query->where($this->column, '<=', $data['until']),
            )
            ->when(
                $data['from'] && $data['until'],
                fn(Builder $query, $date): Builder => $query->whereBetween($this->column, [
                    Carbon::parse($data['from'])->startOfDay(),
                    Carbon::parse($data['until'])->endOfDay(),
                ]),
            );
    }

    public function range(): self
    {
        $this->range = true;

        return $this;
    }

    public function displayRangeInLine(): self
    {
        $this->displayRangeInLine = true;

        return $this;
    }

    public function fromLabel(string $label): self
    {
        $this->labels['from'] = $label;

        return $this;
    }

    public function untilLabel(string $label): self
    {
        $this->labels['until'] = $label;

        return $this;
    }

    public function useColumn(string $column): self
    {
        $this->column = $column;

        return $this;
    }

    public function operator(string $operator): self
    {
        $this->operator = $operator;

        return $this;
    }

    public function equal(): self
    {
        $this->operator = '=';

        return $this;
    }

    public function gte(): self
    {
        $this->operator = '>=';

        return $this;
    }

    public function lte(): self
    {
        $this->operator = '<=';

        return $this;
    }

    public function lt(): self
    {
        $this->operator = '<';

        return $this;
    }

    public function gt(): self
    {
        $this->operator = '>';

        return $this;
    }

    public function maxDate(CarbonInterface | string | Closure | null $date): static
    {
        $this->maxDate = $date;

        return $this;
    }

    public function minDate(CarbonInterface | string | Closure | null $date): static
    {
        $this->minDate = $date;

        return $this;
    }

    public function displayFormat(string | Closure | null $format): static
    {
        $this->displayFormat = $format;

        return $this;
    }

    public function timezone(string | Closure | null $timezone): static
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function defaultFrom(CarbonInterface | string | Closure | null $date): static
    {
        $this->defaultFrom = $date;
        return $this;
    }

    public function defaultUntil(CarbonInterface | string | Closure | null $date): static
    {
        $this->defaultUntil = $date;
        return $this;
    }

    public function getFormSchema(): array
    {
        $schema = $this->evaluate($this->formSchema);

        if ($schema !== null) {
            return $schema;
        }

        if (!$this->range) {
            return [
                Forms\Components\DatePicker::make('value')
                    ->closeOnDateSelection()
                    ->label($this->getLabel())
                    ->displayFormat($this->displayFormat)
                    ->maxDate($this->maxDate)
                    ->minDate($this->minDate)
                    ->timezone($this->timezone)
                    ->columnSpan($this->getColumnSpan())
            ];
        }

        return [
            Forms\Components\Fieldset::make('range')
                ->columnSpan($this->getColumnSpan())
                ->label($this->getLabel())
                ->columns($this->displayRangeInLine ? 2 : 1)
                ->schema([
                    Forms\Components\DatePicker::make('from')
                        ->closeOnDateSelection()
                        ->label($this->labels['from'])
                        ->displayFormat($this->displayFormat)
                        ->timezone($this->timezone)
                        ->minDate($this->minDate)
                        ->maxDate(fn($get) => $get('until') ?? $this->maxDate)
                        ->default($this->defaultFrom),

                    Forms\Components\DatePicker::make('until')
                        ->closeOnDateSelection()
                        ->label($this->labels['until'])
                        ->displayFormat($this->displayFormat)
                        ->timezone($this->timezone)
                        ->minDate(fn($get) => $get('from') ?? $this->minDate)
                        ->maxDate($this->maxDate)
                        ->default($this->defaultUntil),
                ])
        ];
    }
}
