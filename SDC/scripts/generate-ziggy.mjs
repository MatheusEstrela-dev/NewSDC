import { spawnSync } from 'node:child_process';

// O memory_limit de 512M do container nao basta para bootar a aplicacao aqui e
// o comando morria com "Allowed memory size exhausted" no symfony/finder,
// derrubando o build inteiro antes do vite rodar. Sobrescrever so nesta
// invocacao evita mexer no php.ini do servico.
// Valor finito de proposito: com -1 um escaneamento descontrolado nao falha,
// so trava indefinidamente, que e pior de diagnosticar.
const phpArgs = ['-d', process.env.ZIGGY_MEMORY_LIMIT || 'memory_limit=2G'];
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
    const result = run('php', [...phpArgs, ...artisanArgs]);
    process.exit(result.status ?? 1);
}

console.log(`PHP local abaixo de ${minimumPhpVersion}; gerando Ziggy no container ${dockerContainer}.`);

const result = run('docker', ['exec', dockerContainer, 'php', ...phpArgs, ...artisanArgs]);

if (result.error) {
    console.error(`Falha ao executar Docker: ${result.error.message}`);
}

process.exit(result.status ?? 1);
