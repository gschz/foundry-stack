<?php

declare(strict_types=1);

namespace App\Facades;

use App\Services\RouteFilterService;
use Illuminate\Support\Facades\Facade;

/**
 * Facade para filtrar rutas de Ziggy según el contexto del usuario.
 *
 * @method static array{url: string, port: int|null, defaults: array<string, mixed>, routes: array<string, mixed>, location: string} getFilteredZiggy(\Illuminate\Http\Request $request)
 *
 * @see RouteFilterService
 */
final class RouteFilter extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return RouteFilterService::class;
    }
}
