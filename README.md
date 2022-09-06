
# Filament Date Filter

A easy-to-use date filter with range option for [Laravel Filament](https://filamentadmin.com/).

## Installation

Install the package via composer (requires filament >= 2.15)
```bash
composer require webbingbrasil/filament-datefilter
```

## Usage

Date filters allow you to quickly create a filter that allows the user to select a date.

```php
use Webbingbrasil\FilamentDateFilter;

DateFilter::make('created_at')
    ->label(__('Created At'))
    ->minDate(Carbon::today()->subMonth(1))
    ->maxDate(Carbon::today()->addMonth(1))
    ->timeZone('America/New_York')
```

Another common use case is to filter by a date range. You can do this with `range()` method:

```php
use Webbingbrasil\FilamentDateFilter;

DateFilter::make('created_at')
    ->label(__('Created At'))
    ->minDate(Carbon::today()->subMonth(1))
    ->maxDate(Carbon::today()->addMonth(1))
    ->timeZone('America/New_York')
    ->range()
    ->fromLabel(__('From'))
    ->untilLabel(__('Until'))
```

If you need to use a different column name than the one you pass to the `make()` method, you can use the `useColumn()` method:


```php
use Webbingbrasil\FilamentDateFilter;

DateFilter::make('created_at')
    ->useColumn('updated_at')
```

## Global Configuration

You can set From/Until label globally using `configureUsing()` method in a service provider:

```php
use Webbingbrasil\FilamentDateFilter;

DateFilter::configureUsing(fn ($filter) => $filter->fromLabel(__('From'))->untilLabel(__('Until')));
```

## Credits

-   [Danilo Andrade](https://github.com/dmandrade)
