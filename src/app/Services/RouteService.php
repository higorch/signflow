<?php

namespace App\Services;

class RouteService
{
    public static function absolute(string $name, array $parameters = []): string
    {
        return config('app.url') . route($name, $parameters, absolute: false);
    }
}