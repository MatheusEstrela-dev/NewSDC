/**
 * Funcoes puras de mascara para campos de desastre.
 * Sem estado reativo — pode ser importado diretamente.
 */

/**
 * Formata numero float como BRL sem simbolo.
 * Ex: 1234.5 -> "1.234,50"
 */
export function formatCurrency(value) {
    if (value === null || value === undefined || value === '') return '';
    const num = typeof value === 'number' ? value : parseFloat(String(value).replace(/\./g, '').replace(',', '.'));
    if (isNaN(num)) return '';
    return num.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

/**
 * Formata string numerica com separador de milhar.
 * Ex: "1234" -> "1.234"
 */
export function formatNumber(value) {
    if (value === null || value === undefined || value === '') return '';
    const digits = String(value).replace(/\D/g, '');
    if (!digits) return '';
    return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

/**
 * Aplica mascaras a todos os campos currency/number de um array de municipios.
 * Muta o array in-place (chamado em onMounted).
 */
export function formatOnLoad(municipios) {
    if (!Array.isArray(municipios)) return;

    municipios.forEach((municipio) => {
        (municipio.categorias ?? []).forEach((categoria) => {
            (categoria.desastres ?? []).forEach((desastre) => {
                (desastre.items ?? []).forEach((item) => {
                    (item.campos ?? []).forEach((campo) => {
                        if (campo.tipo === 'currency' && campo.valor != null) {
                            const raw = String(campo.valor).replace(/\D/g, '');
                            campo.valor = formatCurrency(parseFloat(raw) / 100);
                        } else if (campo.tipo === 'number' && campo.valor != null) {
                            campo.valor = formatNumber(String(campo.valor));
                        }
                    });
                });
            });
        });
    });
}
