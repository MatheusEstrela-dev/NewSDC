/**
 * Destino do botao Voltar a partir da trilha.
 *
 * Sobe exatamente um degrau: o item imediatamente anterior ao atual. Modulo
 * puro (sem Vue nem Inertia) para poder ser exercitado fora do navegador.
 *
 * O historico do navegador nao serve de regra: a entrada anterior dele nem
 * sempre e a pagina de cima da hierarquia (filtro aplicado, link direto,
 * pagina aberta em outra aba), e o usuario via o Voltar andar para o lado.
 *
 * Quando o item anterior nao tem link (trilhas montadas automaticamente pelo
 * nome do componente deixam os degraus intermediarios sem rota), cai para o
 * degrau mais proximo que tenha, depois para o ancestral que o servidor
 * calculou e so entao para o modulo e o Inicio. O historico e a ultima reserva.
 *
 * @param {Array<{route?: string|null, params?: any}>} itens trilha completa; o ultimo e a pagina atual
 * @param {{url?: string}|null} voltarDoServidor ancestral acessivel calculado pelo servidor
 * @returns {{tipo: 'item', item: object}|{tipo: 'url', url: string}|{tipo: 'historico'}}
 */
export function destinoDoVoltar(itens, voltarDoServidor = null) {
    const anterior = itens[itens.length - 2];

    if (anterior?.route) {
        return { tipo: 'item', item: anterior };
    }

    for (let i = itens.length - 3; i >= 2; i--) {
        if (itens[i]?.route) {
            return { tipo: 'item', item: itens[i] };
        }
    }

    if (voltarDoServidor?.url) {
        return { tipo: 'url', url: voltarDoServidor.url };
    }

    for (const item of [itens[1], itens[0]]) {
        if (item?.route) {
            return { tipo: 'item', item };
        }
    }

    return { tipo: 'historico' };
}
