import chalk from 'chalk';
import prompts from 'prompts';

export interface InstallerOptions {
  projectName?: string;
  installDependencies?: boolean;
  environments?: {
    docker: boolean;
    postgres: boolean;
  };
}

/**
 * Ejecuta el modo interactivo para configurar el instalador
 * @returns Opciones del instalador
 */
export async function runInteractiveMode(): Promise<InstallerOptions> {
  console.log(chalk.gray('\nConfigura la instalación de Foundry Stack:\n'));

  const response = await prompts(
    [
      {
        type: 'text',
        name: 'projectName',
        message: '¿Nombre del proyecto?',
        initial: 'my-foundry-app',
        validate: (value) => (value.trim() === '' ? 'El nombre del proyecto es requerido' : true),
      },
      {
        type: 'confirm',
        name: 'confirmInstall',
        message: (prev) => chalk.hex('#f97316')(`¿Deseas crear el proyecto en '${prev}'?`),
        initial: true,
      },
      {
        type: 'multiselect',
        name: 'envs',
        message: '¿Qué entornos adicionales quieres habilitar?',
        instructions: false,
        hint: 'Usa espacio para seleccionar; Enter para continuar',
        choices: [
          { title: 'Docker stack', value: 'docker', selected: false },
          { title: 'PostgreSQL local', value: 'postgres', selected: false },
        ],
      },
    ],
    {
      onCancel: () => {
        console.log(chalk.red.dim('\nOperación cancelada por el usuario.'));
        process.exit(0);
      },
    }
  );

  if (!response.confirmInstall) {
    console.log(chalk.red.dim('\nOperación cancelada por el usuario.'));
    process.exit(0);
  }

  return {
    projectName: response.projectName,
    environments: {
      docker: Array.isArray(response.envs) ? response.envs.includes('docker') : false,
      postgres: Array.isArray(response.envs) ? response.envs.includes('postgres') : false,
    },
  };
}
