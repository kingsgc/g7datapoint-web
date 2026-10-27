// Rasterize the G7DataPoint SVG logos into every PNG size the app needs.
// Uses headless Chrome to screenshot each SVG at a given size.
const { execFileSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const CHROME = 'C:/Program Files (x86)/Google/Chrome/Application/chrome.exe';
const ROOT = path.resolve(__dirname, '..');
const BRAND = path.join(ROOT, 'public', 'brand');

const jobs = [
  // [svg, outPath, width, height]
  ['logo-master.svg', path.join(BRAND, 'g7-icon-512.png'), 512, 512],
  ['logo-master.svg', path.join(BRAND, 'g7-icon-192.png'), 192, 192],
  ['logo-master.svg', path.join(ROOT, 'public', 'favicon', '1.png'), 180, 180],
  ['logo-master.svg', path.join(ROOT, 'public', 'favicon', '2.png'), 32, 32],
  ['logo-master.svg', path.join(ROOT, 'public', 'favicon', '3.png'), 16, 16],
  ['logo-master.svg', path.join(ROOT, 'public', 'upload', 'welcome.png'), 512, 512],
  ['logo-master.svg', path.join(ROOT, 'public', 'assets', 'images', 'welcome.png'), 200, 200],
  ['logo-horizontal.svg', path.join(BRAND, 'g7-logo-horizontal.png'), 980, 240],
];

function rasterize(svgFile, outFile, w, h) {
  const svgPath = path.join(BRAND, svgFile);
  const html = `data:text/html,<!doctype html><html><head><style>*{margin:0;padding:0}body{width:${w}px;height:${h}px;overflow:hidden}img{width:${w}px;height:${h}px;display:block}</style></head><body><img src="file:///${svgPath.replace(/\\/g, '/')}"></body></html>`;
  fs.mkdirSync(path.dirname(outFile), { recursive: true });
  const tmp = path.join(BRAND, `.shot-${w}x${h}.png`);
  execFileSync(CHROME, [
    '--headless=new', '--disable-gpu', '--hide-scrollbars',
    `--screenshot=${tmp}`, `--window-size=${w},${h}`, '--default-background-color=00000000',
    html,
  ], { stdio: 'ignore' });
  fs.renameSync(tmp, outFile);
  console.log('OK  ', path.relative(ROOT, outFile), `(${w}x${h})`);
}

jobs.forEach(([svg, out, w, h]) => {
  try { rasterize(svg, out, w, h); } catch (e) { console.error('FAIL', out, e.message); }
});
