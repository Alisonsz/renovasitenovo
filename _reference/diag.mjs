import fs from 'fs';
import path from 'path';

const root = 'C:/Users/aliso/OneDrive/Documentos/Codex/renovasitenovo';
const lines = [];

// 1) manifest
const manifest = JSON.parse(fs.readFileSync(`${root}/public/build/manifest.json`, 'utf8'));
lines.push('MANIFEST KEYS: ' + Object.keys(manifest).join(', '));
for (const [k, v] of Object.entries(manifest)) {
    lines.push(`  ${k} -> ${v.file}  (isEntry=${!!v.isEntry})`);
}

// 2) bundles
const dir = `${root}/public/build/assets`;
for (const f of fs.readdirSync(dir)) {
    const raw = fs.readFileSync(path.join(dir, f), 'utf8');
    const wa = (raw.match(/WHATSAPP/g) || []).length;
    lines.push(`ASSET ${f}: bytes=${raw.length} WHATSAPP=${wa}`);
}

// 3) app.blade.php raw
lines.push('--- app.blade.php ---');
lines.push(fs.readFileSync(`${root}/resources/views/app.blade.php`, 'utf8'));

fs.writeFileSync(`${root}/_reference/diag.txt`, lines.join('\n'));
console.log('wrote diag.txt');
