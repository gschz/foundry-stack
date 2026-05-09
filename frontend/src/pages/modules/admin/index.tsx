/**
 * Página principal del Módulo de Administración.
 * Muestra tarjetas de navegación y estadísticas del sistema.
 */
import { EnhancedStatsCards } from '@/components/modules/module-enhanced-stats-cards';
import { ModuleIndexContent } from '@/components/modules/module-index-content';
import { EnhancedStatsCardsSkeleton } from '@/components/modules/skeletons/module-enhanced-stats-cards-skeleton';
import { useFlashToasts } from '@/hooks/use-flash-toasts';
import { useNavigationProgress } from '@/hooks/use-navigation-progress';
import AppLayout from '@/layouts/app-layout';
import { ModuleDashboardLayout } from '@/layouts/module-dashboard-layout';
import { getLucideIcon } from '@/utils/lucide-icons';
import { extractUserData } from '@/utils/user-data';
import { Head, usePage } from '@inertiajs/react';
import { useMemo } from 'react';
import { type AdminIndexPageProps } from './interfaces';

/**
 * Página principal del Módulo de Administración.
 *
 * @returns Elemento JSX del panel principal de administración.
 */
export default function AdminIndexPage() {
  const {
    auth,
    flash,
    panelItems,
    stats,
    mainNavItems,
    moduleNavItems,
    contextualNavItems,
    globalNavItems,
    breadcrumbs,
    pageTitle,
    description,
  } = usePage<AdminIndexPageProps>().props as AdminIndexPageProps;

  const isNavigating = useNavigationProgress({ delayMs: 150 });
  const userData = extractUserData(auth.user);

  useFlashToasts(flash);

  // Sección de estadísticas
  const statsSection = useMemo(
    () => (isNavigating ? <EnhancedStatsCardsSkeleton /> : <EnhancedStatsCards stats={stats} />),
    [isNavigating, stats],
  );

  // Contenido principal
  const mainContent = useMemo(
    () => (
      <ModuleIndexContent
        isLoading={isNavigating}
        items={panelItems}
        getIconComponent={getLucideIcon}
        headerTitle="Secciones del Módulo"
        headerDescription="Acceda a las distintas secciones disponibles."
      />
    ),
    [isNavigating, panelItems],
  );

  return (
    <AppLayout
      user={userData}
      breadcrumbs={breadcrumbs}
      mainNavItems={mainNavItems}
      moduleNavItems={moduleNavItems}
      contextualNavItems={contextualNavItems}
      globalNavItems={globalNavItems}
      pageTitle={pageTitle}
    >
      <Head title={pageTitle} />

      <ModuleDashboardLayout
        title={pageTitle}
        description={description}
        userName={userData?.name ?? ''}
        stats={statsSection}
        mainContent={mainContent}
        fullWidth={true}
      />
    </AppLayout>
  );
}
