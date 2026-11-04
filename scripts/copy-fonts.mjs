import { copyFileSync, mkdirSync } from 'node:fs';
mkdirSync('web/fonts', { recursive: true });
for (const weight of [400, 500, 600, 700]) {
  copyFileSync(`node_modules/@fontsource/noto-sans-thai/files/noto-sans-thai-thai-${weight}-normal.woff2`, `web/fonts/thai-${weight}.woff2`);
}
