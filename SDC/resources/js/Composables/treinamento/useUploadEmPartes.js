import { onBeforeUnmount, ref, toValue } from 'vue';

export const LIMITE_BYTES = 2 * 1024 * 1024 * 1024;
export const TIPOS_ACEITOS = ['video/mp4', 'video/quicktime', 'video/x-matroska'];
export const ACCEPT_VIDEO = TIPOS_ACEITOS.join(',');

const MIME_POR_EXTENSAO = { mp4: 'video/mp4', mov: 'video/quicktime', mkv: 'video/x-matroska' };
const MAX_TENTATIVAS = 3;
// Espera antes da 2a e da 3a tentativa.
const ESPERAS_MS = [1000, 2000];

function mimeDoArquivo(file) {
  if (file.type) return file.type;
  return MIME_POR_EXTENSAO[file.name.split('.').pop().toLowerCase()] ?? '';
}

/** Mensagem de erro do arquivo escolhido, ou null quando pode ser enviado. */
export function validarArquivoDeVideo(file) {
  if (!file || file.size === 0) return 'O arquivo está vazio.';
  if (!TIPOS_ACEITOS.includes(mimeDoArquivo(file))) return 'Formato não aceito. Envie um vídeo MP4, MOV ou MKV.';
  if (file.size > LIMITE_BYTES) return 'O arquivo passa de 2 GB, o limite para envio.';
  return null;
}

export function mensagemDeErro(error) {
  const dados = error?.response?.data;
  if (dados?.errors) return Object.values(dados.errors).flat()[0];
  return dados?.message || 'Não foi possível concluir o envio. Verifique a conexão e tente novamente.';
}

// Falha transitoria (rede, timeout, 5xx) vale nova tentativa; erro de regra ou validacao (4xx) nao.
const ehTransitorio = (error) => !error.response || error.response.status >= 500;

function esperar(ms, signal) {
  return new Promise((resolve, reject) => {
    const abortar = () => {
      clearTimeout(timer);
      reject(new DOMException('Cancelado', 'AbortError'));
    };
    const timer = setTimeout(() => {
      signal.removeEventListener('abort', abortar);
      resolve();
    }, ms);
    if (signal.aborted) return abortar();
    signal.addEventListener('abort', abortar, { once: true });
  });
}

/**
 * Envio de um arquivo grande em partes sequenciais (abrir, partes, finalizar).
 * Estado so no cliente e no servidor: nada e reaproveitado entre execucoes.
 */
export function useUploadEmPartes({ videoId }) {
  const progresso = ref(0);
  const estado = ref('ocioso');
  const erro = ref(null);

  let controlador = null;
  let uploadId = null;

  const rota = (nome, parametros) => route(`treinamentos.professor.videos.upload.${nome}`, parametros);

  async function enviarParte(file, n, tamanhoParte, id) {
    for (let tentativa = 1; ; tentativa++) {
      const corpo = new FormData();
      corpo.append('_method', 'PUT');
      corpo.append('parte', file.slice((n - 1) * tamanhoParte, n * tamanhoParte));

      try {
        // POST com _method: o PUT multipart nao e interpretado pelo PHP/Swoole.
        await window.axios.post(rota('parte', [toValue(videoId), id, n]), corpo, { signal: controlador.signal });
        return;
      } catch (e) {
        if (!ehTransitorio(e) || tentativa >= MAX_TENTATIVAS || controlador.signal.aborted) throw e;
        await esperar(ESPERAS_MS[tentativa - 1], controlador.signal);
      }
    }
  }

  async function enviar(file) {
    controlador = new AbortController();
    uploadId = null;
    progresso.value = 0;
    erro.value = null;
    estado.value = 'enviando';

    try {
      const { data: aberto } = await window.axios.post(rota('abrir', toValue(videoId)), {
        nome_original: file.name,
        tamanho_total: file.size,
        mime: mimeDoArquivo(file),
      }, { signal: controlador.signal });
      uploadId = aberto.upload_id;

      for (let n = 1; n <= aberto.total_partes; n++) {
        await enviarParte(file, n, aberto.tamanho_parte, uploadId);
        progresso.value = Math.round((n / aberto.total_partes) * 100);
      }

      estado.value = 'finalizando';
      await window.axios.post(rota('finalizar', [toValue(videoId), uploadId]));
      estado.value = 'enviado';
    } catch (e) {
      if (controlador.signal.aborted) return;
      erro.value = mensagemDeErro(e);
      estado.value = 'erro';
    }
  }

  async function cancelar() {
    // Finalizar ja foi pedido: nao ha mais o que interromper.
    if (estado.value !== 'enviando') return;

    estado.value = 'cancelado';
    controlador.abort();
    if (uploadId) {
      try {
        await window.axios.delete(rota('cancelar', [toValue(videoId), uploadId]));
      } catch {
        // O servidor expira o envio aberto sozinho.
      }
    }
  }

  onBeforeUnmount(cancelar);

  return { enviar, cancelar, progresso, estado, erro };
}
