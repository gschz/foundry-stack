<?php

declare(strict_types=1);

namespace Modules\Core\Application\View;

use App\Http\Resources\StaffUserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Modules\Core\Contracts\ModuleRegistryInterface;
use Modules\Core\Contracts\NavigationBuilderInterface;
use Modules\Core\Infrastructure\Eloquent\Models\StaffUser;

/**
 * Acción para componer las props compartidas de Inertia.
 */
final readonly class ComposeInertiaProps
{
    public function __construct(
        private ModuleRegistryInterface $moduleRegistry,
        private NavigationBuilderInterface $navigationBuilder
    ) {
        //
    }

    /**
     * Ejecuta la composición de props para Inertia.
     *
     * @param  Request  $request  Petición actual.
     * @return array<string, mixed> Props compartidas.
     */
    public function execute(Request $request): array
    {
        /** @var StaffUser|null $staffUser */
        $staffUser = $request->user('staff');

        $navProps = $this->composeNavigationProps($staffUser);
        $authProps = $this->composeAuthProps($staffUser, $request);

        return array_merge($navProps, $authProps);
    }

    /**
     * Compone las propiedades de navegación.
     *
     * @return array<string, mixed>
     */
    private function composeNavigationProps(?StaffUser $staffUser): array
    {
        if (! $staffUser instanceof StaffUser) {
            return [
                'contextualNavItems' => [],
                'globalNavItems' => [],
                'passwordChangeRequired' => false,
            ];
        }

        $permissionChecker = fn (string $permission): bool => $staffUser->hasPermissionToCross($permission);

        // Construir items de navegación contextual (módulos)
        $modules = $this->moduleRegistry->getAvailableModulesForUser($staffUser);
        $contextualItems = $this->navigationBuilder->buildNavItems(
            $modules,
            $permissionChecker
        );

        // Construir items de navegación global (configuración)
        $globalItemsConfig = $this->moduleRegistry->getGlobalNavItems($staffUser);
        $globalItems = $this->navigationBuilder->buildGlobalNavItems(
            $globalItemsConfig,
            $permissionChecker
        );

        // Verificación de cambio de contraseña
        $passwordChangeRequired = $this->checkPasswordChangeRequired($staffUser);

        return [
            'contextualNavItems' => $contextualItems,
            'globalNavItems' => $globalItems,
            'passwordChangeRequired' => $passwordChangeRequired,
        ];
    }

    /**
     * Compone las propiedades de autenticación.
     *
     * @return array<string, mixed>
     */
    private function composeAuthProps(
        ?StaffUser $staffUser,
        Request $request
    ): array {
        $transformedStaffUser = $staffUser instanceof StaffUser
            ? new StaffUserResource($staffUser) : null;

        return [
            'auth' => [
                'user' => $transformedStaffUser,
                'staff' => $transformedStaffUser,
                'can' => $staffUser instanceof StaffUser
                    ? ($staffUser->getAttribute('frontend_permissions') ?? []) : [],
                'impersonate' => $staffUser && $request->session()->has('impersonated_by'),
            ],
        ];
    }

    /**
     * Verifica si se requiere cambio de contraseña.
     */
    private function checkPasswordChangeRequired(StaffUser $staffUser): bool
    {
        /** @var int|numeric|string $rawMaxAge */
        $rawMaxAge = config(
            'security.authentication.passwords.staff.max_age_days',
            90
        );
        $maxAgeDays = is_int($rawMaxAge)
            ? $rawMaxAge
            : (is_numeric($rawMaxAge)
                ? (int) $rawMaxAge : 90
            );

        /** @var \Illuminate\Support\Carbon|string|null $passwordChangedAt */
        $passwordChangedAt = $staffUser->password_changed_at;

        if ($passwordChangedAt) {
            $passwordAge = Date::parse($passwordChangedAt)
                ->diffInDays(Date::now());

            return $passwordAge >= $maxAgeDays;
        }

        return false;
    }
}
