import { existsSync, readFileSync } from 'node:fs';

export const generateAppKey = (): string => {
  const key = Buffer.from(Array.from({ length: 32 }, () => Math.floor(Math.random() * 256))).toString('base64');
  return `base64:${key}`;
};

export const parseEnvFile = (path: string): Record<string, string> => {
  try {
    if (!existsSync(path)) return {};
    const content = readFileSync(path, 'utf8');
    const env: Record<string, string> = {};
    for (const rawLine of content.split(/\r?\n/)) {
      const line = rawLine.trim();
      if (!line || line.startsWith('#')) continue;
      const match = line.match(/^([A-Za-z_][A-Za-z0-9_]*)=(.*)$/);
      if (!match) continue;
      let value = match[2].trim();
      if ((value.startsWith('"') && value.endsWith('"')) || (value.startsWith("'") && value.endsWith("'"))) {
        value = value.slice(1, -1);
      }
      env[match[1]] = value;
    }
    return env;
  } catch {
    return {};
  }
};
