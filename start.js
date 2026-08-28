import { spawn } from 'child_process';

function run(command, args) {
    return spawn(command, args, {
        stdio: 'inherit',
        shell: true,
    });
}

run('docker', ['compose', 'up', '-d']);

setTimeout(() => {
    console.log('Starte Laravel...');

    run('php', ['artisan', 'serve']);

    console.log('Starte Vite...');

    run('npm', ['run', 'dev']);

    setTimeout(() => {
        console.log('🌐 Öffne Browser...');

        run('start', ['https://lazytown.test/']);
    }, 2000);
}, 3000);
