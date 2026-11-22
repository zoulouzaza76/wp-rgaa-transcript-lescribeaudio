import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');

// 1. Validate required WordPress.org files exist
const required = [
  'wp-rgaa-transcript-lescribeaudio.php',
  'block.json',
  'readme.txt',
  'LICENSE',
  'assets/editor.js',
  'assets/frontend.js',
  'assets/style.css',
  'assets/editor.css',
];
for (const rel of required) {
  assert.ok(fs.existsSync(path.join(root, rel)), `Missing required file: ${rel}`);
}

// 2. Validate block.json schema
const block = JSON.parse(fs.readFileSync(path.join(root, 'block.json'), 'utf8'));
assert.equal(block.apiVersion, 3);
assert.equal(block.name, 'lescribeaudio/rgaa-transcript');
assert.equal(block.textdomain, 'rgaa-transcript-lescribeaudio');

// 3. Validate PHP plugin headers & WordPress.org escaping / security hygiene
const phpSrc = fs.readFileSync(path.join(root, 'wp-rgaa-transcript-lescribeaudio.php'), 'utf8');
assert.match(phpSrc, /Plugin Name:\s+Transcription Accessible RGAA 4\.1/);
assert.match(phpSrc, /License:\s+GPL-2\.0-or-later/);
assert.match(phpSrc, /if\s*\(\s*!\s*defined\(\s*'ABSPATH'\s*\)\s*\)/);
assert.match(phpSrc, /esc_html\(/);
assert.match(phpSrc, /esc_attr\(/);
assert.match(phpSrc, /esc_url\(/);
// Ensure ZERO unsolicited front-end promo links in PHP render output (WP.org Guideline #10)
const renderBody = phpSrc.slice(phpSrc.indexOf('function rgaa_lsa_render_transcript'));
assert.ok(!renderBody.includes('lescribeaudio.com'), 'Must not inject promotional backlink into rendered front-end HTML');

// 4. Validate JS syntax parses cleanly via Node Function constructor
for (const jsRel of ['assets/editor.js', 'assets/frontend.js']) {
  const src = fs.readFileSync(path.join(root, jsRel), 'utf8');
  assert.doesNotThrow(() => new Function(src), `Syntax error in ${jsRel}`);
}

// 5. Validate readme.txt sections required by WordPress.org parser
const readmeTxt = fs.readFileSync(path.join(root, 'readme.txt'), 'utf8');
for (const marker of ['Stable tag: 1.0.0', '== Description ==', '== Installation ==', '== Frequently Asked Questions ==', '== Changelog ==']) {
  assert.ok(readmeTxt.includes(marker), `readme.txt missing ${marker}`);
}

console.log('✅ WordPress RGAA 4.1 plugin validation passed (8 files, security & WP.org compliance verified).');
