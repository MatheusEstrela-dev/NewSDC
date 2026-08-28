import { computed, ref } from 'vue';

/**
 * Fonte unica das preferencias de notificacao no cliente.
 *
 * O estado vive no modulo (fora do composable) de proposito: o painel do sino e o
 * modal de Configuracoes editam a MESMA coisa e antes cada um mantinha sua copia,
 * com axios duplicado. O painel ainda cacheava com um "if (ja tem, nao recarrega)",
 * entao mudar um canal no modal so aparecia no sino depois de um F5.
 *
 * Divisao de responsabilidade entre as duas telas:
 *  - painel do sino: alternar() grava na hora, sem botao de confirmar;
 *  - modal: edita um rascunho() isolado e so publica em salvarRascunho(), porque
 *    a tela promete "Alteracoes nao salvas serao perdidas" e tem Cancelar.
 *
 * Em ambos os caminhos a resposta do PUT vira o novo estado -- o servidor decide,
 * a tela nunca inventa o resultado.
 */

// Rota web (sessao), nao a de API: as duas telas vivem dentro do app Inertia, que
// ja viaja com o cookie. A de API depende de sanctum.stateful reconhecer o dominio.
const ENDPOINT = '/notificacoes/preferencias';

const CANAIS_PERSISTIVEIS = [
    'canal_sistema',
    'canal_email',
    'canal_push',
    'canal_telegram',
    'canal_whatsapp',
];

const modulos = ref([]);
const canais = ref([]);
const updateMode = ref('auto');
const carregando = ref(false);
const salvando = ref(false);
const erro = ref(null);
const carregado = ref(false);

// Duas telas podem pedir o carregamento ao mesmo tempo (abrir o modal com o sino
// aberto). Guardar a promessa em voo evita a segunda requisicao.
let emVoo = null;

function aplicarResposta(data) {
    modulos.value = data?.modules ?? [];
    canais.value = data?.canais ?? [];
    updateMode.value = data?.update_mode ?? 'auto';
    carregado.value = true;
}

/**
 * Apenas os campos que o backend persiste. Mandar label/descricao/icone de volta
 * so faria a validacao recusar campos que ela nao conhece.
 */
function linhaPersistivel(modulo) {
    const linha = { module: modulo.module };

    CANAIS_PERSISTIVEIS.forEach((canal) => {
        if (canal in modulo) {
            linha[canal] = Boolean(modulo[canal]);
        }
    });

    return linha;
}

async function carregar(forcar = false) {
    if (carregado.value && !forcar) return;
    if (emVoo) return emVoo;

    carregando.value = true;
    erro.value = null;

    emVoo = window.axios
        .get(ENDPOINT)
        .then(({ data }) => aplicarResposta(data))
        .catch(() => {
            erro.value = 'Não foi possível carregar suas preferências.';
        })
        .finally(() => {
            carregando.value = false;
            emVoo = null;
        });

    return emVoo;
}

/**
 * Grava um unico canal de um unico modulo, no ato. Em caso de falha o valor volta
 * ao anterior: a tela nunca mostra um estado que o backend nao tem.
 */
async function alternar(modulo, canal, valor) {
    const anterior = modulo[canal];

    modulo[canal] = valor;
    salvando.value = true;
    erro.value = null;

    try {
        const { data } = await window.axios.put(ENDPOINT, {
            modules: [{ ...linhaPersistivel(modulo), [canal]: valor }],
        });
        aplicarResposta(data);
        return true;
    } catch (e) {
        modulo[canal] = anterior;
        erro.value = 'Não foi possível salvar. Tente novamente.';
        return false;
    } finally {
        salvando.value = false;
    }
}

/**
 * Copia isolada para o modal editar sem publicar cada clique no sino.
 */
function rascunho() {
    return {
        modulos: modulos.value.map((modulo) => ({ ...modulo })),
        updateMode: updateMode.value,
    };
}

/**
 * Publica o rascunho do modal. Devolve true/false para a tela decidir se fecha.
 */
async function salvarRascunho({ modulos: rascunhoModulos, updateMode: modo }) {
    salvando.value = true;
    erro.value = null;

    try {
        const { data } = await window.axios.put(ENDPOINT, {
            modules: (rascunhoModulos ?? []).map(linhaPersistivel),
            update_mode: modo,
        });
        aplicarResposta(data);
        return true;
    } catch (e) {
        erro.value = 'Não foi possível salvar suas preferências. Tente novamente.';
        return false;
    } finally {
        salvando.value = false;
    }
}

export function useNotificationPreferences() {
    // Canais que este servidor/usuario realmente entrega. O modal ainda mostra os
    // indisponiveis, desabilitados e com motivo, entao a lista completa continua
    // exposta em `canais`.
    const canaisDisponiveis = computed(() => canais.value.filter((canal) => canal.disponivel));

    const canal = (id) => canais.value.find((c) => c.id === id) ?? null;

    return {
        modulos,
        canais,
        canaisDisponiveis,
        canal,
        updateMode,
        carregando,
        salvando,
        erro,
        carregado,
        carregar,
        alternar,
        rascunho,
        salvarRascunho,
    };
}

export default useNotificationPreferences;
