import { EnhancedStatsCards } from '@/components/modules/module-enhanced-stats-cards';
import { ModuleIndexContent } from '@/components/modules/module-index-content';
import { ModuleIndexPage } from '@/components/modules/module-index-page';
import { EnhancedStatsCardsSkeleton } from '@/components/modules/skeletons/module-enhanced-stats-cards-skeleton';
import { useFlashToasts } from '@/hooks/use-flash-toasts';
import { useNavigationProgress } from '@/hooks/use-navigation-progress';
import { getLucideIcon } from '@/utils/lucide-icons';
import { extractUserData } from '@/utils/user-data';
import { usePage } from '@inertiajs/react';
import { useMemo } from 'react';
import type { Module01IndexPageProps } from './interfaces';

/**
 * Componente para el panel principal del Módulo 01.
 */
export default function Module01IndexPanel() {
  const {
    auth,
    panelItems,
    mainNavItems,
    moduleNavItems,
    contextualNavItems,
    globalNavItems,
    stats,
    pageTitle,
    description,
    breadcrumbs,
    flash,
  } = usePage<Module01IndexPageProps>().props;

  const isNavigating = useNavigationProgress({ delayMs: 150 });
  const userData = extractUserData(auth.user);

  useFlashToasts(flash);

  // Sección de estadísticas
  const statsSection = useMemo(
    () => (isNavigating ? <EnhancedStatsCardsSkeleton /> : <EnhancedStatsCards stats={stats} />),
    [isNavigating, stats],
  );

  // Contenido principal para el dashboard
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
    <ModuleIndexPage
      user={userData}
      breadcrumbs={breadcrumbs}
      mainNavItems={mainNavItems}
      moduleNavItems={moduleNavItems}
      contextualNavItems={contextualNavItems}
      globalNavItems={globalNavItems}
      pageTitle={pageTitle}
      description={description}
      staffUserName={userData?.name ?? ''}
      stats={statsSection}
      mainContent={mainContent}
      fullWidth={true}
    />
  );
}
