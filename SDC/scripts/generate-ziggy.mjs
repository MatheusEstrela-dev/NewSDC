import { spawnSync } from 'node:child_process';

const artisanArgs = ['artisan', 'ziggy:generate', 'resources/js/ziggy.js'];
const minimumPhpVersion = '8.3.0';
const dockerContainer = process.env.ZIGGY_DOCKER_CONTAINER || 'newsdc_dev_app';

function run(command, args, options = {}) {
    return spawnSync(command, args, {
        stdio: 'inherit',
        ...options,
    });
}

const phpVersionCheck = spawnSync(
    'php',
    ['-r', `exit(version_compare(PHP_VERSION, '${minimumPhpVersion}', '>=') ? 0 : 1);`],
    { stdio: 'ignore' },
);

if (phpVersionCheck.status === 0) {
    const result = run('php', artisanArgs);
    process.exit(result.status ?? 1);
}

console.log(`PHP local abaixo de ${minimumPhpVersion}; gerando Ziggy no container ${dockerContainer}.`);

const result = run('docker', ['exec', dockerContainer, 'php', ...artisanArgs]);

if (result.error) {
    console.error(`Falha ao executar Docker: ${result.error.message}`);
}

process.exit(result.status ?? 1);
