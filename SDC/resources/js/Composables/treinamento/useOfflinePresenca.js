import { openDB } from 'idb';
import { ref } from 'vue';
import axios from 'axios';

// RF07 - fila local de check-ins feitos sem conexao + roster cacheado do
// treinamento para a chamada continuar funcionando em campo. Mesmo padrao
// (fila + sincronizacao em lote idempotente) do app de eventos que serviu de
// referencia para este modulo.
const DB_NAME = 'sdc-treinamento-offline';
const DB_VERSION = 1;
const STORE_ROSTER = 'roster';
const STORE_FILA = 'fila_presencas';

let dbPromise = null;

function getDb() {
  if (!dbPromise) {
    dbPromise = openDB(DB_NAME, DB_VERSION, {
      upgrade(db) {
        if (!db.objectStoreNames.contains(STORE_ROSTER)) {
          db.createObjectStore(STORE_ROSTER, { keyPath: 'treinamento_id' });
        }
        if (!db.objectStoreNames.contains(STORE_FILA)) {
          db.createObjectStore(STORE_FILA, { keyPath: 'localId', autoIncrement: true });
        }
      },
    });
  }
  return dbPromise;
}

export function useOfflinePresenca() {
  const pendentes = ref(0);
  const sincronizando = ref(false);

  async function cachearRoster(treinamentoId) {
    const { data } = await axios.get(route('treinamentos.roster', treinamentoId));
    const db = await getDb();
    await db.put(STORE_ROSTER, { treinamento_id: treinamentoId, ...data, cacheado_em: new Date().toISOString() });
    return data;
  }

  async function buscarRosterLocal(treinamentoId) {
    const db = await getDb();
    return db.get(STORE_ROSTER, treinamentoId);
  }

  /**
   * Valida o token contra o roster cacheado localmente (usado quando o
   * check-in online falha por falta de rede).
   */
  async function validarTokenLocal(treinamentoId, token) {
    const roster = await buscarRosterLocal(treinamentoId);
    return roster?.inscritos?.find((i) => i.qr_code_token === token) ?? null;
  }

  async function enfileirar(item) {
    const db = await getDb();
    await db.add(STORE_FILA, { ...item, confirmado_em: item.confirmado_em ?? new Date().toISOString() });
    await atualizarContagem();
  }

  async function listarFila() {
    const db = await getDb();
    return db.getAll(STORE_FILA);
  }

  async function atualizarContagem() {
    const fila = await listarFila();
    pendentes.value = fila.length;
  }

  async function sincronizar() {
    const fila = await listarFila();
    if (fila.length === 0) return { sincronizados: 0, falharam: 0 };

    sincronizando.value = true;

    try {
      const { data } = await axios.post(route('treinamentos.presencas.sincronizar'), {
        itens: fila.map((i) => ({
          qr_code_token: i.qr_code_token,
          modulo_id: i.modulo_id,
          confirmado_em: i.confirmado_em,
        })),
      });

      const db = await getDb();
      let sincronizados = 0;
      let falharam = 0;

      for (let idx = 0; idx < fila.length; idx++) {
        const resultado = data.resultados[idx];
        if (resultado?.sucesso) {
          await db.delete(STORE_FILA, fila[idx].localId);
          sincronizados++;
        } else {
          falharam++;
        }
      }

      await atualizarContagem();
      return { sincronizados, falharam };
    } finally {
      sincronizando.value = false;
    }
  }

  atualizarContagem();

  return {
    pendentes,
    sincronizando,
    cachearRoster,
    buscarRosterLocal,
    validarTokenLocal,
    enfileirar,
    listarFila,
    sincronizar,
  };
}
