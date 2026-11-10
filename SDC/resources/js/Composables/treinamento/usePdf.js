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

  return { contarPaginas };
}
