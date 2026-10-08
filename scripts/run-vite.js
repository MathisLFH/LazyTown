import { spawn } from 'node:child_process';
import { existsSync } from 'node:fs';
import { join } from 'node:path';

const nodeExecutable = process.platform === 'win32'
    ? join(process.env.USERPROFILE, '.config', 'herd', 'bin', 'nvm', 'v20.20.2', 'node.exe')
    : process.execPath;
const viteExecutable = join(process.cwd(), 'node_modules', 'vite', 'bin', 'vite.js');

if (!existsSync(nodeExecutable)) {
    console.error(`Node.js 20.20.2 was not found at ${nodeExecutable}.`);
    process.exit(1);
}

if (!existsSync(viteExecutable)) {
    console.error(`Vite was not found at ${viteExecutable}. Run npm install first.`);
    process.exit(1);
}

const vite = spawn(nodeExecutable, [viteExecutable, ...process.argv.slice(2)], {
    stdio: 'inherit',
    windowsHide: false,
});

vite.on('exit', (code, signal) => {
    process.exitCode = code ?? (signal ? 1 : 0);
});
