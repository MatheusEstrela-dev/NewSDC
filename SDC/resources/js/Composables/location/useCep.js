import { ref } from 'vue';

export function useCep() {
  const isLoading = ref(false);
  const error = ref(null);

  /**
   * Busca informações de endereço pelo CEP usando ViaCEP
   * @param {string} cep - CEP sem formatação (apenas números)
   * @returns {Promise<Object|null>} - Dados do endereço ou null se falhar
   */
  const buscarCep = async (cep) => {
    if (!cep || cep.length !== 8) {
      error.value = 'CEP inválido';
      return null;
    }

    isLoading.value = true;
    error.value = null;

    try {
      // 1. Buscar dados do CEP via ViaCEP
      const responseViaCep = await fetch(`https://viacep.com.br/ws/${cep}/json/`);

      if (!responseViaCep.ok) {
        throw new Error('Erro ao buscar CEP');
      }

      const dataViaCep = await responseViaCep.json();

      if (dataViaCep.erro) {
        error.value = 'CEP não encontrado';
        return null;
      }

      // 2. Buscar coordenadas via OpenStreetMap Nominatim
      let latitude = null;
      let longitude = null;

      try {
        const address = `${dataViaCep.logradouro}, ${dataViaCep.bairro}, ${dataViaCep.localidade}, ${dataViaCep.uf}, Brazil`;
        const nominatimUrl = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&limit=1`;

        const responseNominatim = await fetch(nominatimUrl, {
          headers: {
            'User-Agent': 'SDC-RAT-System/1.0', // Nominatim requer User-Agent
          },
        });

        if (responseNominatim.ok) {
          const dataNominatim = await responseNominatim.json();
          if (dataNominatim && dataNominatim.length > 0) {
            latitude = parseFloat(dataNominatim[0].lat);
            longitude = parseFloat(dataNominatim[0].lon);
          }
        }
      } catch (coordError) {
        console.warn('Não foi possível obter coordenadas:', coordError);
        // Continua mesmo sem coordenadas
      }

      return {
        cep: dataViaCep.cep,
        logradouro: dataViaCep.logradouro,
        complemento: dataViaCep.complemento,
        bairro: dataViaCep.bairro,
        localidade: dataViaCep.localidade,
        uf: dataViaCep.uf,
        ibge: dataViaCep.ibge,
        ddd: dataViaCep.ddd,
        siafi: dataViaCep.siafi,
        latitude,
        longitude,
      };
    } catch (err) {
      error.value = err.message || 'Erro ao buscar CEP';
      console.error('Erro ao buscar CEP:', err);
      return null;
    } finally {
      isLoading.value = false;
    }
  };

  /**
   * Valida formato de CEP
   * @param {string} cep - CEP formatado ou não
   * @returns {boolean}
   */
  const validarCep = (cep) => {
    const cepLimpo = cep.replace(/\D/g, '');
    return cepLimpo.length === 8;
  };

  /**
   * Formata CEP para o padrão XXXXX-XXX
   * @param {string} cep - CEP sem formatação
   * @returns {string}
   */
  const formatarCep = (cep) => {
    const cepLimpo = cep.replace(/\D/g, '');
    return cepLimpo.replace(/(\d{5})(\d{3})/, '$1-$2');
  };

  return {
    buscarCep,
    validarCep,
    formatarCep,
    isLoading,
    error,
  };
}
