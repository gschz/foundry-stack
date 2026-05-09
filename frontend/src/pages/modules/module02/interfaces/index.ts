import type { EnhancedStat } from '@/components/modules/module-enhanced-stats-cards';
import type {
  BaseModulePageProps,
  BreadcrumbItem,
  ModuleNavItem,
  NavItemDefinition,
} from '@/types';
import type { PageProps } from '@inertiajs/core';

/**
 * Propiedades para la página del panel principal del Módulo 02.
 * Extiende las propiedades globales de página con datos específicos del módulo.
 *
 * Nota: `stats` contiene contadores agregados entregados por el backend y
 * usados para componer `EnhancedStatsCards` en la UI.
 */
export interface Module02IndexPageProps extends PageProps, BaseModulePageProps {
  panelItems: ModuleNavItem[];
  mainNavItems: NavItemDefinition[];
  moduleNavItems: NavItemDefinition[];
  contextualNavItems: NavItemDefinition[];
  globalNavItems: NavItemDefinition[];
  breadcrumbs: BreadcrumbItem[];
  pageTitle: string;
  description: string;
  stats: EnhancedStat[];
}
