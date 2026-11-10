import * as pdfjs from 'pdfjs-dist';

pdfjs.GlobalWorkerOptions.workerSrc = new URL('pdfjs-dist/build/pdf.worker.min.mjs', import.meta.url).toString();

/**
 * Leitura de PDF no navegador. O numero de paginas e a base da barra de
 * progresso do aluno, por isso e contado antes do envio.
 */
export function usePdf() {
  async function contarPaginas(file) {
    const dados = new Uint8Array(await file.arrayBuffer());
    const documento = await pdfjs.getDocument({ data: dados }).promise;

    try {
      return documento.numPages;
    } finally {
      await documento.destroy();
    }
  }

  /**
   * Abre um PDF servido por endpoint autorizado (usa os cookies da sessao).
   */
  async function abrirDocumento(url) {
    return pdfjs.getDocument({ url, withCredentials: true }).promise;
  }

  /**
   * Desenha a pagina no canvas, nitida em telas de alta densidade. Devolve a
   * tarefa de renderizacao: o chamador espera `tarefa.promise` e pode chamar
   * `tarefa.cancel()` se a pagina mudar antes de terminar.
   */
  async function renderizarPagina(documento, numero, canvas, escala = 1.2) {
    const pagina = await documento.getPage(numero);
    const densidade = window.devicePixelRatio || 1;
    const viewport = pagina.getViewport({ scale: escala });

    canvas.width = Math.floor(viewport.width * densidade);
    canvas.height = Math.floor(viewport.height * densidade);
    canvas.style.width = `${Math.floor(viewport.width)}px`;
    canvas.style.height = `${Math.floor(viewport.height)}px`;

    return pagina.render({
      canvasContext: canvas.getContext('2d'),
      viewport,
      transform: densidade === 1 ? null : [densidade, 0, 0, densidade, 0, 0],
    });
  }

  return { contarPaginas, abrirDocumento, renderizarPagina };
}
