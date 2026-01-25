import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { useToastNotifications } from '@/hooks/use-toast-notifications';
import AppLayout from '@/layouts/app-layout';
import ProfileLayout from '@/layouts/profile-layout';
import type { BreadcrumbItem, NavItemDefinition } from '@/types';
import { extractUserData } from '@/utils/user-data';
import type { PageProps } from '@inertiajs/core';
import { Head, useForm, usePage } from '@inertiajs/react';
import { useEffect } from 'react';

interface NotificationPrefs {
  email?: boolean;
  internal?: boolean;
}

interface NotificationsPageProps extends PageProps {
  breadcrumbs?: BreadcrumbItem[];
  contextualNavItems?: NavItemDefinition[];
  notificationPreferences?: NotificationPrefs;
}

export default function NotificationsPage() {
  const { auth, contextualNavItems, flash, notificationPreferences, breadcrumbs } =
    usePage<NotificationsPageProps>().props;

  const { showSuccess, showError } = useToastNotifications();

  const form = useForm({
    email: Boolean(notificationPreferences?.email),
    internal: Boolean(notificationPreferences?.internal),
  });

  useEffect(() => {
    if (flash.success) {
      showSuccess(flash.success);
    }
    if (flash.error) {
      showError(flash.error);
    }
  }, [flash, showSuccess, showError]);

  const save = () => {
    form.patch(route('internal.staff.notifications.update'), {
      preserveScroll: true,
    });
  };

  return (
    <AppLayout
      user={extractUserData(auth.user)}
      breadcrumbs={breadcrumbs ?? []}
      contextualNavItems={contextualNavItems ?? []}
    >
      <Head title="Notificaciones" />

      <ProfileLayout>
        <div className="space-y-8">
          <HeadingSmall
            title="Preferencias de notificaciones"
            description="Elige cómo quieres recibir notificaciones del sistema"
          />

          <Card className="w-full max-w-2xl">
            <CardHeader>
              <CardTitle>Canales</CardTitle>
              <CardDescription>Activa o desactiva canales de notificación</CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="flex items-start gap-3">
                <Checkbox
                  id="pref_email"
                  checked={form.data.email}
                  onClick={() => {
                    form.setData('email', !form.data.email);
                  }}
                  disabled={form.processing}
                />
                <div className="space-y-1">
                  <Label htmlFor="pref_email" className="text-sm">
                    Email
                  </Label>
                  <p className="text-muted-foreground text-sm">
                    Recibe notificaciones por correo electrónico.
                  </p>
                </div>
              </div>

              <div className="flex items-start gap-3">
                <Checkbox
                  id="pref_internal"
                  checked={form.data.internal}
                  onClick={() => {
                    form.setData('internal', !form.data.internal);
                  }}
                  disabled={form.processing}
                />
                <div className="space-y-1">
                  <Label htmlFor="pref_internal" className="text-sm">
                    Internas
                  </Label>
                  <p className="text-muted-foreground text-sm">
                    Notificaciones dentro del panel interno.
                  </p>
                </div>
              </div>

              <div className="flex items-center gap-4 pt-2">
                <Button type="button" onClick={save} disabled={form.processing}>
                  {form.processing ? 'Guardando...' : 'Guardar preferencias'}
                </Button>
              </div>
            </CardContent>
          </Card>
        </div>
      </ProfileLayout>
    </AppLayout>
  );
}
