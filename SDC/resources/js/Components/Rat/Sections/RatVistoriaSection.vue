<template>
  <fieldset :disabled="props.viewOnly" style="border:none;padding:0;margin:0;min-width:0;">
    <div class="space-y-6">

      <!-- 1. Identificação do Solicitante -->
      <div class="rat-section-card">
        <div class="rat-section-header">
          <div class="rat-section-icon rat-section-icon-warning">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
          </div>
          <div><h3 class="rat-section-title">Identificação do Solicitante</h3></div>
        </div>
        <div class="rat-section-content">
          <div class="rat-grid-2">
            <FormField label="Nome Completo" v-model="localData.solicitante.nome" required />
            <FormField label="CPF" v-model="localData.solicitante.cpf" mask="cpf" required />
          </div>
          <div class="rat-grid-2">
            <FormField label="Telefone" v-model="localData.solicitante.telefone" mask="phone" />
            <FormField label="Endereço" v-model="localData.solicitante.endereco" required />
          </div>
          <div class="rat-grid-2">
            <FormField label="Bairro" v-model="localData.solicitante.bairro" />
            <FormField label="CEP" v-model="localData.solicitante.cep" mask="cep" />
          </div>
        </div>
      </div>

      <!-- 2. Localização do Imóvel -->
      <div class="rat-section-card">
        <div class="rat-section-header">
          <div class="rat-section-icon rat-section-icon-default">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
          </div>
          <div><h3 class="rat-section-title">Localização do Imóvel</h3></div>
        </div>
        <div class="rat-section-content">
          <FormField label="Endereço do Imóvel" v-model="localData.imovel.endereco" />
          <div class="rat-grid-3">
            <FormField label="Bairro" v-model="localData.imovel.bairro" />
            <FormSelect
              label="Município"
              v-model="localData.imovel.municipio"
              :options="municipioOptions"
              placeholder="Selecione o município"
              :disabled="isLoadingMunicipios"
            />
            <FormField label="CEP" v-model="localData.imovel.cep" mask="cep" />
          </div>
        </div>
      </div>

      <!-- 3. Tipo de Imóvel / Estrutura -->
      <div class="rat-section-card">
        <div class="rat-section-header">
          <div class="rat-section-icon rat-section-icon-success">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
          </div>
          <div><h3 class="rat-section-title">Tipo de Imóvel / Estrutura</h3></div>
        </div>
        <div class="rat-section-content">
          <div class="rat-grid-2">
            <FormSelect label="Tipo de Imóvel" v-model="localData.estrutura.tipo_imovel" :options="tipoImovelOptions" required />
            <FormSelect label="Tipo de Construção" v-model="localData.estrutura.tipo_construcao" :options="tipoConstrucaoOptions" />
          </div>
          <div class="rat-grid-2">
            <FormSelect label="Tipo de Destinação" v-model="localData.estrutura.tipo_destinacao" :options="tipoDestinacaoOptions" />
            <FormSelect label="Tipo de Edificação" v-model="localData.estrutura.tipo_edificacao" :options="tipoEdificacaoOptions" />
          </div>
          <div class="rat-grid-2">
            <FormSelect label="Tipo de Terreno / Relevo" v-model="localData.estrutura.tipo_terreno_relevo" :options="tipoTerrenoOptions" />
            <FormSelect label="Tipo de Localização" v-model="localData.estrutura.tipo_localizacao" :options="tipoLocalizacaoOptions" />
          </div>
          <div class="rat-grid-2">
            <FormSelect label="Sistema Estrutural" v-model="localData.estrutura.sistema_estrutural" :options="sistemaEstruturalOptions" />
            <FormField label="Número de Pavimentos" type="number" v-model="localData.estrutura.num_pavimentos" />
          </div>
          <div class="rat-grid-2">
            <FormSelect label="Estado de Conservação" v-model="localData.estrutura.estado_conservacao" :options="estadoConservacaoOptions" />
            <FormSelect label="Regime de Ocupação" v-model="localData.estrutura.regime_ocupacao" :options="regimeOcupacaoOptions" />
          </div>
        </div>
      </div>

      <!-- 4. Caracterização dos Moradores -->
      <div class="rat-section-card">
        <div class="rat-section-header">
          <div class="rat-section-icon rat-section-icon-purple">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
          </div>
          <div><h3 class="rat-section-title">Caracterização dos Moradores</h3></div>
        </div>
        <div class="rat-section-content">
          <div class="rat-grid-2">
            <FormField label="Proprietário / Morador" v-model="localData.moradores.proprietario" />
            <FormField label="Contato Telefone" v-model="localData.moradores.telefone" mask="phone" />
          </div>
          <div class="flex flex-wrap gap-8 items-start">
            <div class="w-32">
              <FormField label="Nº de Moradores" type="number" v-model="localData.moradores.num_moradores" />
            </div>
            <div>
              <label class="form-label">Há Idosos?</label>
              <div class="flex gap-5 mt-2.5">
                <label class="radio-label"><input type="radio" :value="true"  v-model="localData.moradores.ha_idosos"   class="radio-input" /> Sim</label>
                <label class="radio-label"><input type="radio" :value="false" v-model="localData.moradores.ha_idosos"   class="radio-input" /> Não</label>
              </div>
            </div>
            <div>
              <label class="form-label">Há Crianças?</label>
              <div class="flex gap-5 mt-2.5">
                <label class="radio-label"><input type="radio" :value="true"  v-model="localData.moradores.ha_criancas" class="radio-input" /> Sim</label>
                <label class="radio-label"><input type="radio" :value="false" v-model="localData.moradores.ha_criancas" class="radio-input" /> Não</label>
              </div>
            </div>
            <div>
              <label class="form-label">Há Pessoas com Dificuldades de Locomoção?</label>
              <div class="flex gap-5 mt-2.5">
                <label class="radio-label"><input type="radio" :value="true"  v-model="localData.moradores.ha_pcd"      class="radio-input" /> Sim</label>
                <label class="radio-label"><input type="radio" :value="false" v-model="localData.moradores.ha_pcd"      class="radio-input" /> Não</label>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 5. Característica do Terreno e Infraestrutura -->
      <div class="rat-section-card">
        <div class="rat-section-header">
          <div class="rat-section-icon rat-section-icon-default">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
          </div>
          <div><h3 class="rat-section-title">Característica do Terreno e Infraestrutura</h3></div>
        </div>
        <div class="rat-section-content">
          <!-- Sim/Não radios para os 3 itens de infraestrutura -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
            <div>
              <label class="form-label">Abastecimento de Água?</label>
              <div class="flex gap-5 mt-2.5">
                <label class="radio-label"><input type="radio" value="sim" v-model="localData.infraestrutura.abastecimento_agua"    class="radio-input" /> Sim</label>
                <label class="radio-label"><input type="radio" value="nao" v-model="localData.infraestrutura.abastecimento_agua"    class="radio-input" /> Não</label>
              </div>
            </div>
            <div>
              <label class="form-label">Esgotamento Sanitário?</label>
              <div class="flex gap-5 mt-2.5">
                <label class="radio-label"><input type="radio" value="sim" v-model="localData.infraestrutura.esgotamento_sanitario" class="radio-input" /> Sim</label>
                <label class="radio-label"><input type="radio" value="nao" v-model="localData.infraestrutura.esgotamento_sanitario" class="radio-input" /> Não</label>
              </div>
            </div>
            <div>
              <label class="form-label">Sistema de Drenagem Superficial?</label>
              <div class="flex gap-5 mt-2.5">
                <label class="radio-label"><input type="radio" value="sim" v-model="localData.infraestrutura.drenagem_superficial"  class="radio-input" /> Sim</label>
                <label class="radio-label"><input type="radio" value="nao" v-model="localData.infraestrutura.drenagem_superficial"  class="radio-input" /> Não</label>
              </div>
            </div>
          </div>
          <div class="rat-grid-2">
            <FormField label="Sistema Viário e de Acesso – AV / RUA" v-model="localData.infraestrutura.sistema_viario_acesso" placeholder="Ex: Avenida Principal, Rua Secundária" />
            <FormSelect label="Tipo de Revestimentos?" v-model="localData.infraestrutura.tipo_revestimento" :options="revestimentoOptions" />
          </div>
          <div class="rat-grid-2">
            <FormField label="Condições de Acesso" v-model="localData.infraestrutura.condicoes_acesso" placeholder="Descreva as condições de acesso" />
            <FormField label="Número de Moradias no Terreno" type="number" v-model="localData.infraestrutura.num_moradias_terreno" />
          </div>
          <div class="rat-grid-2">
            <FormField label="Distância da Encosta < 2 metros" v-model="localData.infraestrutura.distancia_encosta" placeholder="Distância em metros" />
            <FormField label="Material Construtivo" v-model="localData.tecnico.material_construtivo" placeholder="Ex: Alvenaria, Madeira, Concreto" />
          </div>
          <FormField type="textarea" label="Conservação Estrutural" v-model="localData.tecnico.conservacao_estrutural" placeholder="Descreva o estado de conservação estrutural" :rows="3" />
        </div>
      </div>

      <!-- 6. Tipificação da Ocorrência / Patologias Identificadas -->
      <div class="rat-section-card">
        <div class="rat-section-header">
          <div class="rat-section-icon rat-section-icon-danger">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
          </div>
          <div><h3 class="rat-section-title">Tipificação da Ocorrência / Patologias Identificadas</h3></div>
        </div>
        <div class="rat-section-content">
          <p class="text-sm text-slate-500 dark:text-slate-400 -mt-2 mb-3">Selecione as Patologias Identificadas:</p>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
            <label v-for="p in PATOLOGIAS" :key="p.key" class="check-item">
              <input type="checkbox" v-model="localData.patologias[p.key]" class="check-input" />
              <span class="text-sm text-slate-700 dark:text-slate-300">{{ p.label }}</span>
            </label>
          </div>
          <div v-if="localData.patologias.outros" class="mt-3">
            <FormField type="textarea" label="Descreva outras patologias" v-model="localData.patologias.outros_descricao" :rows="2" />
          </div>
        </div>
      </div>

      <!-- 7. Análise de Risco -->
      <div class="rat-section-card">
        <div class="rat-section-header">
          <div class="rat-section-icon rat-section-icon-danger">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
          </div>
          <div><h3 class="rat-section-title">Análise de Risco</h3></div>
        </div>
        <div class="rat-section-content">
          <div class="rat-grid-2">
            <FormSelect label="Tipo de Risco" v-model="localData.tecnico.manutencao_uso_ocupacao" :options="tipoRiscoOptions" placeholder="Selecione o tipo de risco" />
            <FormSelect label="Grau de Risco" v-model="localData.tecnico.danos_estruturais"       :options="grauRiscoOptions"  placeholder="Selecione o grau de risco" />
          </div>
          <FormField type="textarea" label="Descrição Técnica" v-model="localData.tecnico.historico" placeholder="Descreva tecnicamente o risco identificado" :rows="4" />
        </div>
      </div>

      <!-- 8. Bens e Infra Afetados -->
      <div class="rat-section-card">
        <div class="rat-section-header">
          <div class="rat-section-icon rat-section-icon-warning">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
          </div>
          <div><h3 class="rat-section-title">Bens e Infra Afetados</h3></div>
        </div>
        <div class="rat-section-content">
          <p class="text-sm text-slate-500 dark:text-slate-400 -mt-2 mb-3">Selecione os Bens e Infraestruturas Afetados:</p>
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
            <label v-for="b in BENS_AFETADOS" :key="b.key" class="check-item">
              <input type="checkbox" v-model="localData.bens_afetados[b.key]" class="check-input" />
              <span class="text-sm text-slate-700 dark:text-slate-300">{{ b.label }}</span>
            </label>
          </div>
          <div v-if="localData.bens_afetados.outros" class="mt-3">
            <FormField type="textarea" label="Descreva outros bens afetados" v-model="localData.bens_afetados.outros_descricao" :rows="2" />
          </div>
        </div>
      </div>

      <!-- 9. Análise de Vulnerabilidade / Encaminhamentos -->
      <div class="rat-section-card">
        <div class="rat-section-header">
          <div class="rat-section-icon rat-section-icon-success">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div><h3 class="rat-section-title">Análise de Vulnerabilidade / Encaminhamentos</h3></div>
        </div>
        <div class="rat-section-content">
          <p class="text-sm text-slate-500 dark:text-slate-400 -mt-2 mb-3">Selecione os Encaminhamentos Necessários:</p>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
            <label v-for="e in ENCAMINHAMENTOS" :key="e.key" class="check-item">
              <input type="checkbox" v-model="localData.encaminhamentos[e.key]" class="check-input" />
              <span class="text-sm text-slate-700 dark:text-slate-300">{{ e.label }}</span>
            </label>
          </div>
          <div v-if="localData.encaminhamentos.outros" class="mt-3">
            <FormField type="textarea" label="Descreva outros encaminhamentos" v-model="localData.encaminhamentos.outros_descricao" :rows="2" />
          </div>
        </div>
      </div>

      <!-- 10. Órgãos / Entidades Acionadas -->
      <div class="rat-section-card">
        <div class="rat-section-header">
          <div class="rat-section-icon rat-section-icon-default">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
            </svg>
          </div>
          <div><h3 class="rat-section-title">Órgãos / Entidades Acionadas</h3></div>
        </div>
        <div class="rat-section-content">
          <p class="text-sm text-slate-500 dark:text-slate-400 -mt-2 mb-3">Selecione os Órgãos/Entidades Acionados:</p>
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
            <label v-for="o in ORGAOS" :key="o.key" class="check-item">
              <input type="checkbox" v-model="localData.orgaos_acionados[o.key]" class="check-input" />
              <span class="text-sm text-slate-700 dark:text-slate-300">{{ o.label }}</span>
            </label>
          </div>
          <div v-if="localData.orgaos_acionados.outros" class="mt-3">
            <FormField type="textarea" label="Descreva outros órgãos acionados" v-model="localData.orgaos_acionados.outros_descricao" :rows="2" />
          </div>
        </div>
      </div>

      <!-- 11. Característica das Anomalias Estruturais e Processos Geodinâmicos -->
      <div class="rat-section-card">
        <div class="rat-section-header">
          <div class="rat-section-icon rat-section-icon-warning">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
          </div>
          <div><h3 class="rat-section-title">Característica das Anomalias Estruturais e Processos Geodinâmicos</h3></div>
        </div>
        <div class="rat-section-content">
          <FormField type="textarea" label="Elementos Estruturais"                   v-model="localData.tecnico.elementos_estruturais"     placeholder="Descreva os elementos estruturais"                           :rows="3" />
          <FormField type="textarea" label="Elementos Construtivos"                  v-model="localData.tecnico.elementos_construtivos"    placeholder="Descreva os elementos construtivos"                         :rows="3" />
          <FormField type="textarea" label="Agentes Potencializadores: Lixo / Entulho" v-model="localData.tecnico.agentes_potencializadores" placeholder="Descreva os agentes potencializadores (lixo, entulho, etc.)" :rows="3" />
          <FormField type="textarea" label="Processos Geodinâmicos"                  v-model="localData.tecnico.processos_geodinamicos"    placeholder="Descreva os processos geodinâmicos observados"              :rows="3" />
        </div>
      </div>

    </div>
  </fieldset>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import FormField from '../../Form/FormField.vue';
import FormSelect from '../../Form/FormSelect.vue';
import { useLocationData } from '@/Composables/location/useLocationData';

const props = defineProps({
  modelValue: { type: Object, default: () => ({}) },
  viewOnly:   { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

// ── Municípios de MG via IBGE ─────────────────────────────────────────────────
const { municipios, municipioOptions, isLoadingMunicipios, loadMunicipios } = useLocationData();

onMounted(() => loadMunicipios('MG'));

// Resolve nome do município quando o usuário seleciona ou ao carregar as opções
watch(
  [() => localData.value.imovel.municipio, municipios],
  ([id]) => {
    if (!id || !municipios.value.length) return;
    const match = municipios.value.find(m => String(m.id) === String(id));
    if (match && localData.value.imovel.municipio_nome !== match.nome) {
      localData.value.imovel.municipio_nome = match.nome;
    }
  }
);

const mv = props.modelValue ?? {};

const localData = ref({
  solicitante: {
    nome:     mv.solicitante?.nome     ?? '',
    cpf:      mv.solicitante?.cpf      ?? '',
    telefone: mv.solicitante?.telefone ?? '',
    endereco: mv.solicitante?.endereco ?? '',
    bairro:   mv.solicitante?.bairro   ?? '',
    cep:      mv.solicitante?.cep      ?? '',
  },
  imovel: {
    endereco:       mv.imovel?.endereco       ?? '',
    complemento:    mv.imovel?.complemento    ?? '',
    bairro:         mv.imovel?.bairro         ?? '',
    municipio:      mv.imovel?.municipio_id != null ? Number(mv.imovel.municipio_id) || '' : (mv.imovel?.municipio != null ? Number(mv.imovel.municipio) || '' : ''),
    municipio_nome: mv.imovel?.municipio_nome ?? '',
    cep:            mv.imovel?.cep            ?? '',
    latitude:       mv.imovel?.latitude       ?? null,
    longitude:      mv.imovel?.longitude      ?? null,
  },
  estrutura: {
    tipo_imovel:         mv.estrutura?.tipo_imovel         ?? '',
    tipo_construcao:     mv.estrutura?.tipo_construcao     ?? '',
    tipo_edificacao:     mv.estrutura?.tipo_edificacao     ?? '',
    tipo_destinacao:     mv.estrutura?.tipo_destinacao     ?? '',
    tipo_terreno_relevo: mv.estrutura?.tipo_terreno_relevo ?? '',
    tipo_localizacao:    mv.estrutura?.tipo_localizacao    ?? '',
    sistema_estrutural:  mv.estrutura?.sistema_estrutural  ?? '',
    num_pavimentos:      mv.estrutura?.num_pavimentos      ?? '',
    estado_conservacao:  mv.estrutura?.estado_conservacao  ?? '',
    regime_ocupacao:     mv.estrutura?.regime_ocupacao     ?? '',
  },
  moradores: {
    proprietario:  mv.moradores?.proprietario  ?? '',
    telefone:      mv.moradores?.telefone      ?? '',
    num_moradores: mv.moradores?.num_moradores ?? '',
    ha_idosos:     mv.moradores?.ha_idosos     ?? null,
    ha_criancas:   mv.moradores?.ha_criancas   ?? null,
    ha_pcd:        mv.moradores?.ha_pcd        ?? null,
  },
  infraestrutura: {
    abastecimento_agua:    mv.infraestrutura?.abastecimento_agua    ?? null,
    esgotamento_sanitario: mv.infraestrutura?.esgotamento_sanitario ?? null,
    drenagem_superficial:  mv.infraestrutura?.drenagem_superficial  ?? null,
    sistema_viario_acesso: mv.infraestrutura?.sistema_viario_acesso ?? '',
    tipo_revestimento:     mv.infraestrutura?.tipo_revestimento     ?? '',
    condicoes_acesso:      mv.infraestrutura?.condicoes_acesso      ?? '',
    num_moradias_terreno:  mv.infraestrutura?.num_moradias_terreno  ?? '',
    distancia_encosta:     mv.infraestrutura?.distancia_encosta     ?? '',
  },
  tecnico: {
    material_construtivo:      mv.tecnico?.material_construtivo      ?? '',
    conservacao_estrutural:    mv.tecnico?.conservacao_estrutural    ?? '',
    manutencao_uso_ocupacao:   mv.tecnico?.manutencao_uso_ocupacao   ?? '',
    danos_estruturais:         mv.tecnico?.danos_estruturais         ?? '',
    historico:                 mv.tecnico?.historico                 ?? '',
    elementos_estruturais:     mv.tecnico?.elementos_estruturais     ?? '',
    elementos_construtivos:    mv.tecnico?.elementos_construtivos    ?? '',
    agentes_potencializadores: mv.tecnico?.agentes_potencializadores ?? '',
    processos_geodinamicos:    mv.tecnico?.processos_geodinamicos    ?? '',
  },
  patologias: {
    rachaduras:                mv.patologias?.rachaduras                ?? false,
    trincas:                   mv.patologias?.trincas                   ?? false,
    fissuras_estruturais:      mv.patologias?.fissuras_estruturais      ?? false,
    deformacoes_estruturais:   mv.patologias?.deformacoes_estruturais   ?? false,
    infiltracoes:              mv.patologias?.infiltracoes              ?? false,
    corrosao_armaduras:        mv.patologias?.corrosao_armaduras        ?? false,
    desagregacao:              mv.patologias?.desagregacao              ?? false,
    eflorescencia:             mv.patologias?.eflorescencia             ?? false,
    desplacamento:             mv.patologias?.desplacamento             ?? false,
    fundacoes:                 mv.patologias?.fundacoes                 ?? false,
    instabilidade_talude:      mv.patologias?.instabilidade_talude      ?? false,
    movimentacao_solo:         mv.patologias?.movimentacao_solo         ?? false,
    tombamento_muralhas:       mv.patologias?.tombamento_muralhas       ?? false,
    inundacoes:                mv.patologias?.inundacoes                ?? false,
    alagamentos:               mv.patologias?.alagamentos               ?? false,
    enxurradas:                mv.patologias?.enxurradas                ?? false,
    madeira:                   mv.patologias?.madeira                   ?? false,
    elementos_nao_estruturais: mv.patologias?.elementos_nao_estruturais ?? false,
    falha_drenagem:            mv.patologias?.falha_drenagem            ?? false,
    queda_arvores:             mv.patologias?.queda_arvores             ?? false,
    outros:                    mv.patologias?.outros                    ?? false,
    outros_descricao:          mv.patologias?.outros_descricao          ?? '',
  },
  encaminhamentos: {
    interdicao_parcial:      mv.encaminhamentos?.interdicao_parcial      ?? false,
    interdicao_total:        mv.encaminhamentos?.interdicao_total        ?? false,
    remocao_temporaria:      mv.encaminhamentos?.remocao_temporaria      ?? false,
    remocao_definitiva:      mv.encaminhamentos?.remocao_definitiva      ?? false,
    isolamento_area:         mv.encaminhamentos?.isolamento_area         ?? false,
    desocupacao_abrigo:      mv.encaminhamentos?.desocupacao_abrigo      ?? false,
    notificacao_responsavel: mv.encaminhamentos?.notificacao_responsavel ?? false,
    contratacao_responsavel: mv.encaminhamentos?.contratacao_responsavel ?? false,
    comunicacao_orgaos:      mv.encaminhamentos?.comunicacao_orgaos      ?? false,
    apoio_social:            mv.encaminhamentos?.apoio_social            ?? false,
    outros:                  mv.encaminhamentos?.outros                  ?? false,
    outros_descricao:        mv.encaminhamentos?.outros_descricao        ?? '',
  },
  bens_afetados: {
    residencia:         mv.bens_afetados?.residencia         ?? false,
    muros:              mv.bens_afetados?.muros              ?? false,
    vias_publicas:      mv.bens_afetados?.vias_publicas      ?? false,
    pontes:             mv.bens_afetados?.pontes             ?? false,
    viadutos:           mv.bens_afetados?.viadutos           ?? false,
    comercios:          mv.bens_afetados?.comercios          ?? false,
    galpoes:            mv.bens_afetados?.galpoes            ?? false,
    predios_publicos:   mv.bens_afetados?.predios_publicos   ?? false,
    edificios_publicos: mv.bens_afetados?.edificios_publicos ?? false,
    outros:             mv.bens_afetados?.outros             ?? false,
    outros_descricao:   mv.bens_afetados?.outros_descricao   ?? '',
  },
  orgaos_acionados: {
    copasa:                mv.orgaos_acionados?.copasa                ?? false,
    cemig:                 mv.orgaos_acionados?.cemig                 ?? false,
    secretaria_municipal:  mv.orgaos_acionados?.secretaria_municipal  ?? false,
    defesa_civil_estadual: mv.orgaos_acionados?.defesa_civil_estadual ?? false,
    dnit:                  mv.orgaos_acionados?.dnit                  ?? false,
    pm:                    !!(mv.orgaos_acionados?.pm),
    bm:                    !!(mv.orgaos_acionados?.bm),
    crea:                  mv.orgaos_acionados?.crea                  ?? false,
    emater:                mv.orgaos_acionados?.emater                ?? false,
    seapa:                 mv.orgaos_acionados?.seapa                 ?? false,
    outros:                mv.orgaos_acionados?.outros                ?? false,
    outros_descricao:      mv.orgaos_acionados?.outros_descricao      ?? '',
  },
});

// ── Options ───────────────────────────────────────────────────────────────────

const tipoImovelOptions = [
  { value: 'residencial',   label: 'Residencial' },
  { value: 'comercial',     label: 'Comercial' },
  { value: 'industrial',    label: 'Industrial' },
  { value: 'institucional', label: 'Institucional' },
  { value: 'misto',         label: 'Misto' },
  { value: 'outro',         label: 'Outro' },
];
const tipoConstrucaoOptions = [
  { value: 'alvenaria', label: 'Alvenaria' },
  { value: 'madeira',   label: 'Madeira' },
  { value: 'concreto',  label: 'Concreto Armado' },
  { value: 'metalica',  label: 'Metálica' },
  { value: 'mista',     label: 'Mista' },
  { value: 'outro',     label: 'Outro' },
];
const tipoEdificacaoOptions = [
  { value: 'unifamiliar',   label: 'Unifamiliar' },
  { value: 'multifamiliar', label: 'Multifamiliar' },
  { value: 'comercial',     label: 'Comercial' },
  { value: 'industrial',    label: 'Industrial' },
  { value: 'institucional', label: 'Institucional' },
  { value: 'outro',         label: 'Outro' },
];
const tipoDestinacaoOptions = [
  { value: 'residencial',   label: 'Residencial' },
  { value: 'comercial',     label: 'Comercial' },
  { value: 'servico',       label: 'Serviços' },
  { value: 'industrial',    label: 'Industrial' },
  { value: 'institucional', label: 'Institucional' },
  { value: 'outro',         label: 'Outro' },
];
const tipoTerrenoOptions = [
  { value: 'plano',          label: 'Plano' },
  { value: 'suave_ondulado', label: 'Suave Ondulado' },
  { value: 'ondulado',       label: 'Ondulado' },
  { value: 'forte_ondulado', label: 'Forte Ondulado' },
  { value: 'montanhoso',     label: 'Montanhoso' },
  { value: 'escarpado',      label: 'Escarpado' },
];
const tipoLocalizacaoOptions = [
  { value: 'urbano', label: 'Urbano' },
  { value: 'rural',  label: 'Rural' },
  { value: 'outro',  label: 'Outro' },
];
const sistemaEstruturalOptions = [
  { value: 'porticos',          label: 'Pórticos' },
  { value: 'paredes_portantes', label: 'Paredes Portantes' },
  { value: 'pre_moldado',       label: 'Pré-Moldado' },
  { value: 'metalico',          label: 'Metálico' },
  { value: 'misto',             label: 'Misto' },
  { value: 'outro',             label: 'Outro' },
];
const estadoConservacaoOptions = [
  { value: 'otimo',   label: 'Ótimo' },
  { value: 'bom',     label: 'Bom' },
  { value: 'regular', label: 'Regular' },
  { value: 'ruim',    label: 'Ruim' },
  { value: 'pessimo', label: 'Péssimo' },
];
const regimeOcupacaoOptions = [
  { value: 'proprio',   label: 'Próprio' },
  { value: 'alugado',   label: 'Alugado' },
  { value: 'cedido',    label: 'Cedido' },
  { value: 'irregular', label: 'Irregular' },
  { value: 'outro',     label: 'Outro' },
];
const revestimentoOptions = [
  { value: 'asfalto',        label: 'Asfalto' },
  { value: 'paralelepipedo', label: 'Paralelepípedo' },
  { value: 'calcamento',     label: 'Calçamento' },
  { value: 'terra',          label: 'Terra' },
  { value: 'outro',          label: 'Outro' },
];
const tipoRiscoOptions = [
  { value: 'geologico',    label: 'Geológico' },
  { value: 'hidrologico',  label: 'Hidrológico' },
  { value: 'meteorologico',label: 'Meteorológico' },
  { value: 'estrutural',   label: 'Estrutural / Antrópico' },
  { value: 'multiplo',     label: 'Múltiplo' },
  { value: 'outro',        label: 'Outro' },
];
const grauRiscoOptions = [
  { value: 'r1', label: 'R1 – Sem Risco' },
  { value: 'r2', label: 'R2 – Risco Baixo' },
  { value: 'r3', label: 'R3 – Risco Médio' },
  { value: 'r4', label: 'R4 – Risco Alto' },
];

// ── Checkbox lists ────────────────────────────────────────────────────────────

const PATOLOGIAS = [
  { key: 'rachaduras',                label: 'Rachaduras' },
  { key: 'trincas',                   label: 'Trincas' },
  { key: 'fissuras_estruturais',      label: 'Fissuras em Elementos Estruturais (pilar, viga, laje, etc.)' },
  { key: 'deformacoes_estruturais',   label: 'Deformações ou Deslocamentos Estruturais' },
  { key: 'infiltracoes',              label: 'Infiltrações / Umidade Ascendente' },
  { key: 'corrosao_armaduras',        label: 'Corrosão de Armaduras' },
  { key: 'desagregacao',              label: 'Desagregação de Reboco / Argamassa' },
  { key: 'eflorescencia',             label: 'Eflorescência / Bolor / Mofo' },
  { key: 'desplacamento',             label: 'Desplacamento de Revestimento' },
  { key: 'fundacoes',                 label: 'Comprometimento das Fundações' },
  { key: 'instabilidade_talude',      label: 'Instabilidade de Talude / Contenções' },
  { key: 'movimentacao_solo',         label: 'Indícios de Movimentação de Solo' },
  { key: 'tombamento_muralhas',       label: 'Risco de Tombamento de Muralhas de Vedação' },
  { key: 'inundacoes',                label: 'Inundações' },
  { key: 'alagamentos',               label: 'Alagamentos' },
  { key: 'enxurradas',                label: 'Enxurradas' },
  { key: 'madeira',                   label: 'Patologia em Elementos de Madeira (cupins, apodrecimento, etc.)' },
  { key: 'elementos_nao_estruturais', label: 'Comprometimento de Elementos Não Estruturais (janelas, portas, etc.)' },
  { key: 'falha_drenagem',            label: 'Falha em Drenagem Pluvial ou Esgoto' },
  { key: 'queda_arvores',             label: 'Risco de Queda de Árvores e/ou Troncos e Galhos' },
  { key: 'outros',                    label: 'Outros' },
];

const ENCAMINHAMENTOS = [
  { key: 'interdicao_parcial',      label: 'Interdição Parcial' },
  { key: 'interdicao_total',        label: 'Interdição Total' },
  { key: 'remocao_temporaria',      label: 'Remoção Temporária' },
  { key: 'remocao_definitiva',      label: 'Remoção Definitiva' },
  { key: 'isolamento_area',         label: 'Isolamento da Área' },
  { key: 'desocupacao_abrigo',      label: 'Desocupação Recorrendo ao Abrigo (parentes/amigos)' },
  { key: 'notificacao_responsavel', label: 'Notificação do Responsável Técnico' },
  { key: 'contratacao_responsavel', label: 'Contratação de Responsável Técnico' },
  { key: 'comunicacao_orgaos',      label: 'Comunicação a Órgãos Competentes' },
  { key: 'apoio_social',            label: 'Solicitação de Apoio Social / Abrigamento' },
  { key: 'outros',                  label: 'Outros' },
];

const BENS_AFETADOS = [
  { key: 'residencia',         label: 'Residência' },
  { key: 'muros',              label: 'Muro(s)' },
  { key: 'vias_publicas',      label: 'Vias Públicas' },
  { key: 'pontes',             label: 'Ponte(s)' },
  { key: 'viadutos',           label: 'Viaduto(s)' },
  { key: 'comercios',          label: 'Comércio(s)' },
  { key: 'galpoes',            label: 'Galpão(ões)' },
  { key: 'predios_publicos',   label: 'Prédio(s) Público(s)' },
  { key: 'edificios_publicos', label: 'Edifício(s) Público(s)' },
  { key: 'outros',             label: 'Outros' },
];

const ORGAOS = [
  { key: 'copasa',                label: 'COPASA' },
  { key: 'cemig',                 label: 'CEMIG' },
  { key: 'secretaria_municipal',  label: 'Secretaria Municipal' },
  { key: 'defesa_civil_estadual', label: 'Defesa Civil Estadual' },
  { key: 'dnit',                  label: 'DNIT' },
  { key: 'pm',                    label: 'PM (Polícia Militar)' },
  { key: 'bm',                    label: 'BM (Bombeiros Militares)' },
  { key: 'crea',                  label: 'CREA' },
  { key: 'emater',                label: 'EMATER' },
  { key: 'seapa',                 label: 'SEAPA' },
  { key: 'outros',                label: 'Outros' },
];

// ── Sync ─────────────────────────────────────────────────────────────────────

watch(
  () => localData.value,
  (nv) => {
    if (JSON.stringify(nv) !== JSON.stringify(props.modelValue)) {
      emit('update:modelValue', nv);
    }
  },
  { deep: true }
);

watch(
  () => props.modelValue,
  (nv) => {
    if (nv && JSON.stringify(nv) !== JSON.stringify(localData.value)) {
      localData.value = JSON.parse(JSON.stringify(nv));
    }
  },
  { deep: true }
);
</script>

<style scoped>
.form-label {
  @apply block text-xs sm:text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5 sm:mb-2;
}

.radio-label {
  @apply flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300 cursor-pointer select-none;
}

.radio-input {
  @apply h-4 w-4 text-blue-600 border-slate-300 dark:border-slate-600 focus:ring-blue-500 cursor-pointer;
}

.check-item {
  @apply flex items-start gap-2.5 p-3 rounded-lg
    bg-slate-50 dark:bg-slate-950/30
    border border-slate-200 dark:border-slate-700/30
    cursor-pointer
    hover:bg-slate-100 dark:hover:bg-slate-800/40
    transition-colors;
}

.check-input {
  @apply mt-0.5 h-4 w-4 rounded border-slate-400 dark:border-slate-600
    text-blue-600 focus:ring-blue-500 cursor-pointer flex-shrink-0;
}
</style>
