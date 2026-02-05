import chalk from 'chalk';

export function showBanner(): void {
  const bannerText = `
  ┏                                                          ┓
                   F O U N D R Y   S T A C K                   
  ┗                                                          ┛
  `;

  console.log(chalk.gray(bannerText));
  console.log(chalk.hex('#f97316')('A modular Laravel + React baseline for internal management systems'));
}
