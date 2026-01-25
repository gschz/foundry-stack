import AppearanceTabs from '@/components/appearance/appearance-tabs';
import HeadingSmall from '@/components/heading-small';
import { useToastNotifications } from '@/hooks/use-toast-notifications';
import AppLayout from '@/layouts/app-layout';
import ProfileLayout from '@/layouts/profile-layout';
import type { BreadcrumbItem, NavItemDefinition } from '@/types';
import { extractUserData } from '@/utils/user-data';
import type { PageProps } from '@inertiajs/core';
import { Head, usePage } from '@inertiajs/react';
import { useEffect } from 'react';

interface AppearancePageProps extends PageProps {
  breadcrumbs?: BreadcrumbItem[];
  contextualNavItems?: NavItemDefinition[];
}

export default function AppearancePage() {
  const { auth, contextualNavItems, flash, breadcrumbs } = usePage<AppearancePageProps>().props;

  const { showSuccess, showError } = useToastNotifications();

  useEffect(() => {
    if (flash.success) {
      showSuccess(flash.success);
    }
    if (flash.error) {
      showError(flash.error);
    }
  }, [flash, showSuccess, showError]);

  return (
    <AppLayout
      user={extractUserData(auth.user)}
      breadcrumbs={breadcrumbs ?? []}
      contextualNavItems={contextualNavItems ?? []}
    >
      <Head title="Apariencia" />

      <ProfileLayout>
        <div className="space-y-8">
          <HeadingSmall
            title="Apariencia"
            description="Actualiza la configuración de apariencia de tu cuenta"
          />
          <div className="max-w-[336px]">
            <AppearanceTabs />
          </div>
        </div>
      </ProfileLayout>
    </AppLayout>
  );
}
