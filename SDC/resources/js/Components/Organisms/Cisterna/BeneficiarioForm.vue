<template>
  <form class="space-y-6" @submit.prevent="$emit('submit')">
    <!-- 1. Identificacao -->
    <CollapsibleSection
      namespace="cisterna"
      section-id="identificacao"
      title="Identificacao"
      subtitle="CPF, nome e contato do beneficiario"
      :icon="IdentificationIcon"
      tom="info"
    >
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <FormField v-model="form.cpf" label="CPF" maxlength="14" required :error="erros.cpf" hint="Somente digitos ou com mascara" />
      <FormField v-model="form.nome" label="Nome completo" maxlength="150" required :error="erros.nome" />
      <FormField v-model="form.telefone" label="Telefone" maxlength="15" :error="erros.telefone" />
      <FormDateField v-model="form.data_nascimento" label="Data de nascimento" required :error="erros.data_nascimento" hint="Beneficiario maior de 18 anos" />
      <FormField v-model="form.cadastro_unico" label="Cadastro Unico" maxlength="12" :error="erros.cadastro_unico" />
      </div>
    </CollapsibleSection>

    <!-- 2. Localizacao -->
    <CollapsibleSection
      namespace="cisterna"
      section-id="localizacao"
      title="Localizacao"
      subtitle="Municipio, comunidade e coordenada do imovel"
      :icon="MapPinIcon"
      tom="info"
    >
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <FormSelect
        v-model="form.municipio_id"
        label="Municipio"
        :options="municipiosOpcoes"
        placeholder="Selecione"
        required
        :error="erros.municipio_id"
        @update:model-value="aoTrocarMunicipio"
      />
      <FormSelect
        v-model="form.comunidade_id"
        label="Comunidade"
        :options="comunidadesOpcoes"
        :placeholder="form.municipio_id ? 'Selecione' : 'Escolha o municipio primeiro'"
        :disabled="!form.municipio_id || carregandoComunidades"
        :error="erros.comunidade_id"
      />
      <FormField v-model="form.endereco" label="Endereco" maxlength="150" :error="erros.endereco" class="sm:col-span-2 lg:col-span-3" />

      <div class="sm:col-span-2 lg:col-span-3">
        <CoordenadaField
          v-model:latitude="form.latitude"
          v-model:longitude="form.longitude"
          :error="erros"
        />
      </div>
      </div>
    </CollapsibleSection>

    <!-- 3. Situacao. Dois eixos ORTOGONAIS: analise do cadastro e obra. -->
    <CollapsibleSection
      namespace="cisterna"
      section-id="situacao"
      title="Situacao"
      subtitle="Analise do cadastro e andamento da obra, eixos independentes"
      :icon="FlagIcon"
      tom="warning"
    >
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <FormSelect v-model="form.situacao_analise" label="Situacao da analise" :options="opcoes.situacoes_analise ?? []" :error="erros.situacao_analise" />
      <FormSelect v-model="form.situacao_obra" label="Situacao da obra" :options="opcoes.situacoes_obra ?? []" :error="erros.situacao_obra" />
      <FormField v-model="form.situacao_analise_obs" label="Observacao da analise" maxlength="255" :error="erros.situacao_analise_obs" class="sm:col-span-2 lg:col-span-3" />
      <!--
        Ordenacao, nao calculo: o legado tinha rota de ranqueamento quebrada e
        nenhuma rotina. A coluna e importada e apenas ordenavel.
      -->
      <FormField v-model="form.ranqueamento_ordem" label="Ordem de ranqueamento" type="number" :error="erros.ranqueamento_ordem" hint="Valor importado, usado apenas para ordenar" />
      </div>
    </CollapsibleSection>

    <!-- 4. Composicao familiar e renda -->
    <CollapsibleSection
      namespace="cisterna"
      section-id="social"
      title="Familia e renda"
      subtitle="Composicao familiar e criterios sociais com comprovante"
      :icon="UsersIcon"
      tom="success"
    >
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <FormField v-model="form.qtd_pessoas" label="Pessoas na residencia" type="number" required :error="erros.qtd_pessoas" />
      <FormField v-model="form.renda" label="Renda familiar (R$)" inputmode="decimal" required :error="erros.renda" />
      <FormField v-model="form.renda_per_capita" label="Renda per capita (R$)" inputmode="decimal" :error="erros.renda_per_capita" />

      <div class="space-y-3 sm:col-span-2 lg:col-span-3">
        <ToggleField v-model="form.possui_deficiencia" label="Ha pessoa com deficiencia" />
        <ArquivoField
          v-if="form.possui_deficiencia"
          label="Comprovante de deficiencia"
          :obrigatorio="!temComprovante('deficiencia')"
          :existente="nomeComprovante('deficiencia')"
          :error="erros.comprovante_deficiencia"
          @change="(f) => $emit('arquivo', { campo: 'comprovante_deficiencia', arquivo: f })"
        />

        <ToggleField v-model="form.possui_crianca" label="Ha crianca na residencia" />
        <FormDateField
          v-if="form.possui_crianca"
          v-model="form.data_nascimento_crianca"
          label="Nascimento da crianca"
          required
          :error="erros.data_nascimento_crianca"
          hint="Menor de 12 anos"
        />

        <ToggleField v-model="form.possui_idoso" label="Ha idoso na residencia" />

        <ToggleField v-model="form.chefiada_mulher" label="Familia chefiada por mulher" />
        <ArquivoField
          v-if="form.chefiada_mulher"
          label="Comprovante de chefia feminina"
          :obrigatorio="!temComprovante('chefia_mulher')"
          :existente="nomeComprovante('chefia_mulher')"
          :error="erros.comprovante_chefia_mulher"
          @change="(f) => $emit('arquivo', { campo: 'comprovante_chefia_mulher', arquivo: f })"
        />

        <ArquivoField
          label="Comprovante adicional (opcional)"
          :existente="nomeComprovante('observacao')"
          :error="erros.comprovante_observacao"
          @change="(f) => $emit('arquivo', { campo: 'comprovante_observacao', arquivo: f })"
        />
      </div>
      </div>
    </CollapsibleSection>

    <!-- 5. Moradia e telhado: o que define se a agua captada serve -->
    <CollapsibleSection
      namespace="cisterna"
      section-id="tecnica"
      title="Moradia e telhado"
      subtitle="Medidas e cobertura que definem se a agua captada serve"
      :icon="HomeModernIcon"
      tom="info"
    >
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <FormSelect v-model="form.tipo_moradia" label="Regime de posse" :options="opcoes.tipos_moradia ?? []" placeholder="Selecione" required :error="erros.tipo_moradia" />
      <FormField v-if="form.tipo_moradia === 'outros'" v-model="form.tipo_moradia_outro" label="Qual regime" maxlength="50" :error="erros.tipo_moradia_outro" />

      <FormSelect v-model="form.cobertura_telhado" label="Cobertura do telhado" :options="opcoes.coberturas_telhado ?? []" placeholder="Selecione" required :error="erros.cobertura_telhado" />
      <FormField v-if="form.cobertura_telhado === 'outros'" v-model="form.cobertura_outro" label="Qual cobertura" maxlength="150" :error="erros.cobertura_outro" />

      <FormField v-model="form.comprimento_telhado" label="Comprimento do telhado (m)" inputmode="decimal" required :error="erros.comprimento_telhado" />
      <FormField v-model="form.largura_telhado" label="Largura do telhado (m)" inputmode="decimal" required :error="erros.largura_telhado" />
      <FormField
        v-model="areaCalculada"
        label="Area total do telhado (m2)"
        inputmode="decimal"
        :error="erros.area_telhado"
        hint="Calculada por comprimento x largura; pode ser ajustada"
      />
      <FormField v-model="form.comprimento_testada" label="Comprimento da testada (m)" inputmode="decimal" required :error="erros.comprimento_testada" />
      <FormField v-model="form.num_caidas_telhado" label="Numero de caidas" type="number" required :error="erros.num_caidas_telhado" />

      <div class="space-y-3 sm:col-span-2 lg:col-span-3">
        <ToggleField v-model="form.possui_fogao_lenha" label="Possui fogao a lenha" description="Fuligem contamina a agua captada na area do fogao" />
        <div v-if="form.possui_fogao_lenha" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <FormField v-model="form.medida_telhado_area_fogao" label="Telhado na area do fogao (m2)" inputmode="decimal" :error="erros.medida_telhado_area_fogao" />
          <FormField v-model="form.testada_disp_parte_fogao" label="Testada disponivel nessa parte (m)" inputmode="decimal" :error="erros.testada_disp_parte_fogao" />
        </div>
      </div>
      </div>
    </CollapsibleSection>

    <!-- 6. Pipa -->
    <CollapsibleSection
      namespace="cisterna"
      section-id="pipa"
      title="Atendimento por carro-pipa"
      subtitle="Se a familia ja recebe agua e quem atende"
      :icon="TruckIcon"
      tom="warning"
    >
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <div class="sm:col-span-2 lg:col-span-3">
        <AtendimentoPipaFieldset
          v-model:atendido="form.atendido_por_pipa"
          v-model:responsaveis="form.responsaveis_pipa"
          v-model:descricao-outro="form.atendimento_pipa_outro"
          :opcoes="opcoes.responsaveis_pipa ?? []"
          :error="erros"
        />
      </div>
      </div>
    </CollapsibleSection>

    <!-- 7. Responsaveis tecnicos -->
    <CollapsibleSection
      namespace="cisterna"
      section-id="responsaveis"
      title="Responsaveis tecnicos"
      subtitle="Agente e engenheiro responsaveis pelo cadastro"
      :icon="WrenchScrewdriverIcon"
      tom="neutro"
    >
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <FormField v-model="form.agente_nome" label="Agente" maxlength="70" required :error="erros.agente_nome" />
      <FormField v-model="form.agente_cpf" label="CPF do agente" maxlength="14" required :error="erros.agente_cpf" />
      <FormField v-model="form.engenheiro_nome" label="Engenheiro" maxlength="150" required :error="erros.engenheiro_nome" />
      <FormField v-model="form.engenheiro_crea" label="CREA" maxlength="20" required :error="erros.engenheiro_crea" />
      </div>
    </CollapsibleSection>

    <!-- 8. Observacoes -->
    <CollapsibleSection
      namespace="cisterna"
      section-id="observacoes"
      title="Observacoes"
      subtitle="Anotacoes livres sobre o cadastro"
      :icon="ChatBubbleLeftEllipsisIcon"
      tom="neutro"
    >
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <div class="sm:col-span-2 lg:col-span-3">
        <FormTextarea v-model="form.observacoes" label="Observacoes" :rows="4" maxlength="1000" :error="erros.observacoes" />
      </div>
      </div>
    </CollapsibleSection>

    <FormActions
      :loading="processando"
      :submit-label="rotuloEnvio"
      @cancel="$emit('cancel')"
    />
  </form>
</template>

<script setup>
import { computed, watch } from 'vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';
import FormDateField from '@/Components/Molecules/Form/FormDateField.vue';
import ToggleField from '@/Components/Molecules/Form/ToggleField.vue';
import FormActions from '@/Components/Molecules/Form/FormActions.vue';
import CoordenadaField from '@/Components/Molecules/Cisterna/CoordenadaField.vue';
import AtendimentoPipaFieldset from '@/Components/Molecules/Cisterna/AtendimentoPipaFieldset.vue';
import ArquivoField from '@/Components/Molecules/Cisterna/ArquivoField.vue';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import {
  IdentificationIcon,
  MapPinIcon,
  FlagIcon,
  UsersIcon,
  HomeModernIcon,
  TruckIcon,
  WrenchScrewdriverIcon,
  ChatBubbleLeftEllipsisIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  /** useForm() do Inertia, vindo da pagina. */
  form: { type: Object, required: true },
  opcoes: { type: Object, default: () => ({}) },
  comunidades: { type: Array, default: () => [] },
  carregandoComunidades: { type: Boolean, default: false },
  comprovantes: { type: Array, default: () => [] },
  processando: { type: Boolean, default: false },
  modo: { type: String, default: 'criar' },
});

const emit = defineEmits(['submit', 'cancel', 'arquivo', 'municipio']);

const erros = computed(() => props.form?.errors ?? {});

const rotuloEnvio = computed(() => (props.modo === 'editar' ? 'Salvar alteracoes' : 'Cadastrar'));

/**
 * `municipios` vem do backend como {id, nome, uf}, e o SelectInput reconhece
 * `label`, `name` ou `text` -- nao `nome`. Sem mapear, cada option renderizaria
 * o objeto inteiro.
 */
const municipiosOpcoes = computed(
  () => (props.opcoes.municipios ?? []).map((m) => ({
    value: m.id,
    label: m.uf ? `${m.nome} / ${m.uf}` : m.nome,
  })),
);

const comunidadesOpcoes = computed(
  () => (props.comunidades ?? []).map((c) => ({ value: c.id, label: c.nome })),
);

/**
 * A area e derivada, mas editavel: o legado deixava o usuario corrigir quando o
 * telhado nao e retangular, e sobrescrever o calculo e legitimo.
 */
const areaCalculada = computed({
  get: () => props.form.area_telhado,
  set: (valor) => { props.form.area_telhado = valor; },
});

watch(
  () => [props.form.comprimento_telhado, props.form.largura_telhado],
  ([comprimento, largura]) => {
    const c = Number(String(comprimento ?? '').replace(',', '.'));
    const l = Number(String(largura ?? '').replace(',', '.'));

    if (!Number.isFinite(c) || !Number.isFinite(l) || c <= 0 || l <= 0) return;

    props.form.area_telhado = Number((c * l).toFixed(2));
  },
);

/** Trocar de municipio invalida a comunidade escolhida: ela pertence ao antigo. */
function aoTrocarMunicipio(municipioId) {
  props.form.comunidade_id = '';
  emit('municipio', municipioId);
}

function comprovante(tipo) {
  return (props.comprovantes ?? []).find((c) => c.tipo === tipo) ?? null;
}

function temComprovante(tipo) {
  return comprovante(tipo) !== null;
}

function nomeComprovante(tipo) {
  return comprovante(tipo)?.nome ?? '';
}
</script>
