<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Http\Controllers\Profile;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Contracts\NotificationPreferences\UpdateNotificationPreferencesInterface;

final class NotificationPreferencesController extends AbstractProfileController
{
    public function edit(Request $request): Response
    {
        $this->requireStaffUser($request);

        $breadcrumbs = $this->buildBreadcrumbs('notifications.edit');

        return Inertia::render('profile/notifications', [
            'contextualNavItems' => $this->getProfileNavigationItems(),
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    public function update(
        UpdateNotificationPreferencesInterface $updateNotificationPreferences,
        Request $request
    ): RedirectResponse {
        $user = $this->requireStaffUser($request);

        /** @var array<string, mixed> $validated */
        $validated = $request->validate([
            'email' => ['sometimes', 'boolean'],
            'internal' => ['sometimes', 'boolean'],
        ]);

        $updateNotificationPreferences->handle($user, $validated);

        return back()->with(
            'success',
            'Preferencias actualizadas correctamente.'
        );
    }
}
