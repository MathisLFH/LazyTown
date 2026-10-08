import { spawn } from 'child_process';

function run(command, args) {
    return spawn(command, args, {
        stdio: 'inherit',
        shell: true,
    });
}

const vite = run('npm', ['run', 'dev']);
run('docker', ['compose', 'up', '-d']);

setTimeout(() => {
    if (!vite.killed) {
        console.log('Öffne Herd: https://lazytown.test/');
        run('start', ['https://lazytown.test/']);
    }
}, 4000);
