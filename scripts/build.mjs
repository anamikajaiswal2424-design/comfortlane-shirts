import { existsSync, readdirSync, statSync } from 'node:fs';
for (const path of ['api/index.php', 'setting.php', 'app/index.php', 'public/assets/css/style.css']) {
    if (!existsSync(path)) throw new Error(`Missing required deployment file: ${path}`);
}
function verifyPublic(path) {
    for (const name of readdirSync(path)) {
        const target = `${path}/${name}`;
        if (statSync(target).isDirectory()) verifyPublic(target);
        else if (/\.(php|json|ndjson|zip|log|env)$/i.test(name)) throw new Error(`Private file in public output: ${target}`);
    }
}
verifyPublic('public');
console.log('Vercel PHP entry, private source, central settings and public assets verified.');
