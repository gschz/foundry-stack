/**
 * Foundry Stack Installer
 * Herramienta CLI para generar automatizar la instalación de Foundry Stack.
 *
 * @author Gera Schz
 * @license MIT
 * @version 0.1.0-alpha
 */

import { showBanner } from './cli/banner';
import { installProject } from './cli/install';
import { runInteractiveMode } from './cli/interactive';

async function main(): Promise<void> {
  showBanner();

  const options = await runInteractiveMode();

  await installProject(options);
}

await main();
