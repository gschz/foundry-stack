import { existsSync, mkdirSync, unlinkSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';
import { log } from './log';

export const cleanupSqliteArtifacts = (projectRoot: string) => {
  const expected = join(projectRoot, 'database', 'database.sqlite');
  const legacy = join(projectRoot, 'backend', 'database.sqlite');

  if (existsSync(legacy)) {
    log('Eliminando archivo SQLite en ruta antigua: backend/database.sqlite', 'warning');
    try {
      unlinkSync(legacy);
      log('Archivo antiguo eliminado', 'success');
    } catch (_e) {
      log('No se pudo eliminar el archivo antiguo', 'error');
    }
  }

  if (existsSync(expected)) {
    log('Base de datos SQLite detectada en ruta correcta', 'success');
  } else {
    log('Advertencia: No se encontró database/database.sqlite en la ruta del proyecto', 'warning');
  }
};

export const ensureSqliteDatabase = (projectRoot: string) => {
  const dbDir = join(projectRoot, 'database');
  const dbFile = join(dbDir, 'database.sqlite');

  if (!existsSync(dbDir)) {
    mkdirSync(dbDir, { recursive: true });
    log('Directorio ./database creado', 'info');
  }

  if (!existsSync(dbFile)) {
    writeFileSync(dbFile, '');
    log('SQLite DB file ensured at ./database/database.sqlite', 'success');
  } else {
    log('SQLite DB ya existe en ./database/database.sqlite', 'success');
  }
};
