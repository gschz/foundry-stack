export type LogType = 'info' | 'success' | 'error' | 'warning' | 'step';

export const log = (message: string, type: LogType = 'info') => {
  const colors = {
    info: '\x1b[37m',
    success: '\x1b[32m',
    error: '\x1b[31m',
    warning: '\x1b[33m',
    step: '\x1b[36m',
    reset: '\x1b[0m',
    dim: '\x1b[90m',
  };

  const prefix = {
    info: '•',
    success: '✓',
    error: '✗',
    warning: '!',
    step: '→',
  } as const;

  const timestamp = new Date().toLocaleTimeString('en-US', {
    hour12: false,
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  });

  console.log(`${colors.dim}[${timestamp}]${colors.reset} ${colors[type]}${prefix[type]} ${message}${colors.reset}`);
};

export const logSection = (title: string) => {
  const line = '─'.repeat(50);
  console.log(`\n\x1b[90m${line}\x1b[0m`);
  console.log(`\x1b[1m\x1b[37m  ${title.toUpperCase()}\x1b[0m`);
  console.log(`\x1b[90m${line}\x1b[0m\n`);
};

export const createProgressBar = (total: number, label: string) => {
  let current = 0;

  return {
    update: (step: number, stepLabel?: string) => {
      current = step;
      const percentage = Math.round((current / total) * 100);
      const filled = Math.round((current / total) * 20);
      const bar = '█'.repeat(filled) + '░'.repeat(20 - filled);

      process.stdout.write(`\r\x1b[90m[${bar}] ${percentage}% ${label}${stepLabel ? ` - ${stepLabel}` : ''}\x1b[0m`);

      if (current === total) {
        process.stdout.write('\n');
      }
    },
  };
};
