<?php

namespace App\Filament\Widgets;

trait DashboardWidgetFilter
{
    public static function canView(): bool
    {
        return auth()->check() && static::isVisibleToRole(auth()->user()->roles()->pluck('name')->all());
    }

    protected static function isVisibleToRole(array $roles): bool
    {
        return true;
    }
}
