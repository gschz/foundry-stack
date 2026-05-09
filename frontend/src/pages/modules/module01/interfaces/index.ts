import type { EnhancedStat } from '@/components/modules/module-enhanced-stats-cards';
import type {
  BaseModulePageProps,
  BreadcrumbItem,
  ModuleNavItem,
  NavItemDefinition,
} from '@/types';
import type { PageProps } from '@inertiajs/core';

/**
 * Propiedades para la página del panel principal del Módulo 01.
 * Extiende las propiedades globales de página con datos específicos del módulo.
 *
 * Nota: `stats` es un objeto tipado por el backend y usado para construir
 * tarjetas de estadísticas en el frontend. Mantener nombres de campos estables.
 */
export interface Module01IndexPageProps extends PageProps, BaseModulePageProps {
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
