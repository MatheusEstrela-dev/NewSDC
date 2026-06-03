import net from 'node:net';

const host = '127.0.0.1';
const port = 8081;
const socket = net.createConnection({ host, port });

socket.setTimeout(500);

socket.once('connect', () => {
    console.error(`Porta ${port} ja esta em uso. O Vite existente continua ativo.`);
    socket.destroy();
    process.exit(1);
});

socket.once('error', () => {
    process.exit(0);
});

socket.once('timeout', () => {
    socket.destroy();
    process.exit(0);
});
