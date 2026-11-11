// Render an ad: deterministic frame capture (engine/ad.html renderAt(t)) -> ffmpeg + voiceover.
// usage: node render.mjs <general|realestate> [--stills 1,5,12] [--fps 30]
import { createServer } from 'node:http';
import { readFileSync, writeFileSync, existsSync, mkdirSync } from 'node:fs';
import { spawn, execFileSync } from 'node:child_process';
import { join, extname, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { createRequire } from 'node:module';
const require = createRequire(import.meta.url);
const { chromium } = require('playwright');

const ROOT = dirname(fileURLToPath(import.meta.url));
const v = process.argv[2];
const arg = k => { const i = process.argv.indexOf(k); return i > 0 ? process.argv[i + 1] : null; };
const fps = +(arg('--fps') || 30);
const stills = arg('--stills');
const timing = JSON.parse(readFileSync(join(ROOT, `engine/timing-${v}.json`)));
const HOLD = 0.6; // seconds of held end card after the voice ends
const total = timing.duration + HOLD;

const types = { '.html': 'text/html', '.js': 'text/javascript', '.json': 'application/json', '.woff2': 'font/woff2' };
const srv = createServer((req, res) => {
  const f = join(ROOT, 'engine', decodeURIComponent(req.url.split('?')[0]));
  if (!existsSync(f)) { res.writeHead(404).end(); return; }
  res.writeHead(200, { 'content-type': types[extname(f)] || 'application/octet-stream' }).end(readFileSync(f));
}).listen(0);
const port = srv.address().port;

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1080, height: 1920 }, deviceScaleFactor: 1 });
page.on('pageerror', e => console.error('PAGE ERROR', e.message));
await page.goto(`http://127.0.0.1:${port}/ad.html?v=${v}`);
await page.waitForFunction(() => window.READY === true);

mkdirSync(join(ROOT, 'out'), { recursive: true });
if (stills) {
  for (const t of stills.split(',').map(Number)) {
    await page.evaluate(t => renderAt(t), t);
    await page.screenshot({ path: join(ROOT, `out/still-${v}-${String(t).padStart(5, '0')}.png`) });
  }
  console.log('stills done');
} else {
  // soundtrack: synthesised music bed + SFX at the cues the scenes declared
  const events = await page.evaluate(() => window.SFX);
  const evPath = join(ROOT, `out/.sfx-${v}.json`), bedPath = join(ROOT, `out/.bed-${v}.wav`);
  writeFileSync(evPath, JSON.stringify(events));
  execFileSync('python3', [join(ROOT, 'soundtrack.py'), evPath, total.toFixed(3), bedPath], { stdio: 'inherit' });
  const vo = join(ROOT, `audio/${v}-raw.mp3`);
  const withVo = !timing.placeholder && existsSync(vo);
  const out = join(ROOT, `out/ot1-pro-${v}-9x16${withVo ? '' : '-preview'}.mp4`);
  const audioArgs = withVo
    ? ['-i', bedPath, '-i', vo, '-filter_complex', `[2:a]atempo=${timing.tempo},loudnorm=I=-15:TP=-1.5:LRA=11,apad[vo];[1:a]volume=0.32[bed];[vo][bed]amix=inputs=2:normalize=0:duration=longest,alimiter=limit=0.95[a]`]
    : ['-i', bedPath, '-filter_complex', `[1:a]loudnorm=I=-16:TP=-1.5[a]`];
  const ff = spawn('ffmpeg', ['-y', '-hide_banner', '-loglevel', 'error',
    '-f', 'image2pipe', '-framerate', String(fps), '-c:v', 'mjpeg', '-i', '-',
    ...audioArgs,
    '-map', '0:v', '-map', '[a]', '-t', total.toFixed(3),
    '-c:v', 'libx264', '-preset', 'medium', '-crf', '19', '-pix_fmt', 'yuv420p', '-profile:v', 'high', '-movflags', '+faststart',
    '-c:a', 'aac', '-b:a', '192k', '-ar', '48000', out], { stdio: ['pipe', 'inherit', 'inherit'] });
  const n = Math.ceil(total * fps);
  const t0 = Date.now();
  for (let i = 0; i < n; i++) {
    await page.evaluate(t => renderAt(t), i / fps);
    const buf = await page.screenshot({ type: 'jpeg', quality: 92 });
    if (!ff.stdin.write(buf)) await new Promise(r => ff.stdin.once('drain', r));
    if (i % 300 === 0) console.log(`${v}: frame ${i}/${n} (${((Date.now() - t0) / 1000).toFixed(0)}s)`);
  }
  ff.stdin.end();
  await new Promise(r => ff.on('close', r));
  console.log('wrote', out);
}
await browser.close(); srv.close();
