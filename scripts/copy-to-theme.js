#!/usr/bin/env node
/*
 Copies build outputs to a WordPress theme when WP_THEME_DIR is set.

 Env vars:
  - WP_THEME_DIR (required): Absolute path to your theme directory
  - WP_BUILD_DIR (optional): Subdirectory in theme to place files (default: accordion-blocks)
  - WP_BUILD_BASENAME (optional): Base filename for JS (default: index.js; e.g. block.build.js)
*/
const fs = require('fs');
const path = require('path');

const workspace = process.cwd();
const themeDir = process.env.WP_THEME_DIR;
if (!themeDir) {
  console.log('WP_THEME_DIR not set; skipping copy to theme.');
  process.exit(0);
}

const buildDirName = process.env.WP_BUILD_DIR || 'accordion-blocks';
const basename = process.env.WP_BUILD_BASENAME || 'index.js';

const srcJs = path.join(workspace, 'build', 'index.js');
const srcAsset = path.join(workspace, 'build', 'index.asset.php');
const destDir = path.join(themeDir, buildDirName);
const destJs = path.join(destDir, basename);
const destAsset = path.join(destDir, 'index.asset.php');

fs.mkdirSync(destDir, { recursive: true });

if (!fs.existsSync(srcJs)) {
  console.error('Build JS not found at', srcJs);
  process.exit(1);
}

fs.copyFileSync(srcJs, destJs);
if (fs.existsSync(srcAsset)) {
  fs.copyFileSync(srcAsset, destAsset);
}

console.log(`Copied build to theme: ${destJs}`);
if (fs.existsSync(srcAsset)) {
  console.log(`Copied asset file to theme: ${destAsset}`);
}

