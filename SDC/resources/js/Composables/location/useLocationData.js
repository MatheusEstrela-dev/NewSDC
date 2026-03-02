import { ref, computed } from 'vue';

export function useLocationData() {
  const municipios = ref([]);
  const isLoadingMunicipios = ref(false);

  // Lista de países (pode ser expandida)
  const paisOptions = [
    { value: 'BR', label: 'Brasil' },
    { value: 'AR', label: 'Argentina' },
    { value: 'UY', label: 'Uruguai' },
    { value: 'PY', label: 'Paraguai' },
    { value: 'BO', label: 'Bolívia' },
    { value: 'CL', label: 'Chile' },
    { value: 'PE', label: 'Peru' },
    { value: 'CO', label: 'Colômbia' },
    { value: 'VE', label: 'Venezuela' },
    { value: 'EC', label: 'Equador' },
  ];

  // Lista de UFs do Brasil
  const ufOptions = [
    { value: 'AC', label: 'Acre' },
    { value: 'AL', label: 'Alagoas' },
    { value: 'AP', label: 'Amapá' },
    { value: 'AM', label: 'Amazonas' },
    { value: 'BA', label: 'Bahia' },
    { value: 'CE', label: 'Ceará' },
    { value: 'DF', label: 'Distrito Federal' },
    { value: 'ES', label: 'Espírito Santo' },
    { value: 'GO', label: 'Goiás' },
    { value: 'MA', label: 'Maranhão' },
    { value: 'MT', label: 'Mato Grosso' },
    { value: 'MS', label: 'Mato Grosso do Sul' },
    { value: 'MG', label: 'Minas Gerais' },
    { value: 'PA', label: 'Pará' },
    { value: 'PB', label: 'Paraíba' },
    { value: 'PR', label: 'Paraná' },
    { value: 'PE', label: 'Pernambuco' },
    { value: 'PI', label: 'Piauí' },
    { value: 'RJ', label: 'Rio de Janeiro' },
    { value: 'RN', label: 'Rio Grande do Norte' },
    { value: 'RS', label: 'Rio Grande do Sul' },
    { value: 'RO', label: 'Rondônia' },
    { value: 'RR', label: 'Roraima' },
    { value: 'SC', label: 'Santa Catarina' },
    { value: 'SP', label: 'São Paulo' },
    { value: 'SE', label: 'Sergipe' },
    { value: 'TO', label: 'Tocantins' },
  ];

  const municipioOptions = computed(() => {
    return municipios.value.map(m => ({
      value: m.id,
      label: m.nome,
    }));
  });

  /**
   * Carrega municípios de uma UF específica
   * Usa API do IBGE
   * @param {string} uf - Sigla da UF
   */
  const loadMunicipios = async (uf) => {
    if (!uf) {
      municipios.value = [];
      return;
    }

    isLoadingMunicipios.value = true;

    try {
      const response = await fetch(
        `https://servicodados.ibge.gov.br/api/v1/localidades/estados/${uf}/municipios`
      );

      if (!response.ok) {
        throw new Error('Erro ao carregar municípios');
      }

      const data = await response.json();

      municipios.value = data.map(municipio => ({
        id: municipio.id,
        nome: municipio.nome,
        codigo_ibge: municipio.id,
      })).sort((a, b) => a.nome.localeCompare(b.nome));
    } catch (error) {
      console.error('Erro ao carregar municípios:', error);
      municipios.value = [];
    } finally {
      isLoadingMunicipios.value = false;
    }
  };

  /**
   * Busca município pelo código IBGE
   * @param {number|string} codigoIbge
   * @returns {Promise<Object|null>}
   */
  const getMunicipioPorCodigoIbge = async (codigoIbge) => {
    try {
      const response = await fetch(
        `https://servicodados.ibge.gov.br/api/v1/localidades/municipios/${codigoIbge}`
      );

      if (!response.ok) {
        throw new Error('Município não encontrado');
      }

      const data = await response.json();

      return {
        id: data.id,
        nome: data.nome,
        uf: data.microrregiao.mesorregiao.UF.sigla,
        codigo_ibge: data.id,
      };
    } catch (error) {
      console.error('Erro ao buscar município:', error);
      return null;
    }
  };

  /**
   * Obtém o nome da UF pela sigla
   * @param {string} sigla
   * @returns {string}
   */
  const getNomeUf = (sigla) => {
    const uf = ufOptions.find(u => u.value === sigla);
    return uf ? uf.label : sigla;
  };

  /**
   * Obtém o nome do país pelo código
   * @param {string} codigo
   * @returns {string}
   */
  const getNomePais = (codigo) => {
    const pais = paisOptions.find(p => p.value === codigo);
    return pais ? pais.label : codigo;
  };

  return {
    paisOptions,
    ufOptions,
    municipios,
    municipioOptions,
    isLoadingMunicipios,
    loadMunicipios,
    getMunicipioPorCodigoIbge,
    getNomeUf,
    getNomePais,
  };
}
