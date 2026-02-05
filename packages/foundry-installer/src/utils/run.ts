import { execSync } from 'node:child_process';
import { log } from './log';

export const runCommand = (command: string, description: string, cwd?: string) => {
  try {
    log(`Ejecutando: ${description}`, 'step');
    execSync(command, { stdio: 'inherit', cwd });
    log(`${description} completado`, 'success');
  } catch (error) {
    log(`Error ejecutando: ${description}`, 'error');
    throw error;
  }
};

export const runCommandEnv = (command: string, description: string, extraEnv: Record<string, string>, cwd?: string) => {
  try {
    log(`Ejecutando: ${description}`, 'step');
    execSync(command, { stdio: 'inherit', env: { ...process.env, ...extraEnv }, cwd });
    log(`${description} completado`, 'success');
  } catch (error) {
    log(`Error ejecutando: ${description}`, 'error');
    throw error;
  }
};
