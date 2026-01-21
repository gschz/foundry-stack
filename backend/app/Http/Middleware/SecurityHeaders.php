<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SecurityHeaders
{
    /**
     * Agrega cabeceras de seguridad a todas las respuestas HTTP.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // Verificamos si estamos respondiendo con un error o una redirección
        $isErrorOrRedirect = $response->isClientError() || $response->isRedirection();

        // Solo aplicamos cabeceras dinámicas que requieren lógica de aplicación
        // o cabeceras que no se aplicaron previamente en Nginx

        // 1. Cabeceras específicas para respuestas JSON/API
        if ($request->expectsJson() || $request->is('api/*')) {
            $response->headers->set('X-Content-Type-Options', 'nosniff');

            // Prevenir CORS problemas en API
            if (! $response->headers->has('Access-Control-Allow-Origin')) {
                // Aquí puedes implementar tu lógica de CORS si es necesaria
            }

            // Agregar informacion sobre límites de tasa para APIs
            if ($request->is('api/*') && app()->bound('limiter') && ! $isErrorOrRedirect) {
                $this->addRateLimitHeaders($response);
            }

            return $response; // Retornamos temprano para APIs
        }

        // 2. Para respuestas HTML (no JSON/API):

        // Cabeceras que podrían variar según la lógica de la aplicación o usuario
        if (! $response->headers->has('X-Frame-Options')) {
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        }

        // Feature Policy/Permissions Policy adaptativa según contexto
        // (solo si no está ya establecida por Nginx)
        if (! $response->headers->has('Permissions-Policy')) {
            // Versión mejorada con más restricciones para rutas sensibles
            $permissionsPolicy = 'camera=(), microphone=(), geolocation=(), payment=()';

            // Rutas más sensibles tienen restricciones adicionales
            if ($request->is('internal/admin*') || $request->is('internal/staff*')) {
                $permissionsPolicy .= ', autoplay=(), fullscreen=()';
            }

            $response->headers->set('Permissions-Policy', $permissionsPolicy);
        }

        if (! $response->headers->has('Content-Security-Policy') && ! $isErrorOrRedirect) {
            $this->applyCspHeaders($request, $response);
        }

        // Solo aplicar HSTS en producción y si es necesario
        if (
            app()->isProduction()
            && ! $response->headers->has('Strict-Transport-Security')
            && ! $isErrorOrRedirect
        ) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        // 4. Cabeceras de seguridad específicas para archivos descargables
        if ($this->isDownloadResponse($response)) {
            $response->headers->set('Content-Disposition', 'attachment');
            $response->headers->set('X-Content-Type-Options', 'nosniff');
        }

        return $response;
    }

    /**
     * Determina si la respuesta es para descarga de archivo
     */
    private function isDownloadResponse(Response $response): bool
    {
        $contentType = $response->headers->get('Content-Type', '');

        $downloadTypes = [
            'application/zip',
            'application/pdf',
            'application/msword',
            'application/vnd.ms-excel',
        ];

        return array_any(
            $downloadTypes,
            fn ($type): bool => mb_stripos((string) $contentType, (string) $type) !== false
        );
    }

    /**
     * Agrega cabeceras informativas sobre límites de tasa para APIs
     */
    private function addRateLimitHeaders(Response $response): void
    {
        // Simularemos información de límites de tasa
        // Idealmente estos datos vendrían de tu implementación real de rate limiting
        $response->headers->set('X-RateLimit-Limit', '60');
        $response->headers->set('X-RateLimit-Remaining', '59');
    }

    private function applyCspHeaders(Request $request, Response $response): void
    {
        $isDev = app()->environment('local', 'development');
        $base = "default-src 'self'; img-src 'self' data: blob:; style-src 'self' 'unsafe-inline'; font-src 'self' data:; frame-ancestors 'self'; form-action 'self'";
        $sensitive = $request->is('internal/admin*') || $request->is('internal/staff*');
        $devOrigin = 'http://localhost:5173';
        $connect = $isDev
            ? "connect-src 'self' ".$devOrigin
            : "connect-src 'self'";
        $script = $isDev
            ? "script-src 'self' 'unsafe-eval' ".$devOrigin
            : "script-src 'self'";
        $extra = $sensitive
            ? "; frame-src 'none'; object-src 'none'"
            : '';
        $policy = $base.'; '.$connect.'; '.$script.$extra;
        $response->headers->set('Content-Security-Policy', $policy);
    }
}
