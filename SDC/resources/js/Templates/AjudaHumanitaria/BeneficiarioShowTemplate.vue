<template>
  <div class="space-y-4 sm:space-y-6">
    <PageHeader
      :title="beneficiario.nome_responsavel || 'Beneficiário'"
      :description="descricao"
      :icon="UsersIcon"
      :icon-image="moduleIcon('ajuda-humanitaria')"
      variant="gradient"
    >
      <template #actions>
        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
          <Badge :variant="corDoStatus" size="md">{{ rotuloDoStatus }}</Badge>
          <Badge v-if="beneficiario.esta_em_abrigo" variant="info" size="md">Em abrigo</Badge>
        </div>
      </template>
    </PageHeader>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 sm:gap-6 items-start">
      <div class="xl:col-span-2 space-y-4 sm:space-y-6">
        <ListContainer title="Identificação" :icon="UsersIcon" icon-class="text-blue-500">
          <dl class="p-4 sm:p-6 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
            <Campo rotulo="Nome do responsável" :valor="beneficiario.nome_responsavel" />
            <Campo rotulo="CPF" :valor="cpfFormatado" mono />
            <Campo rotulo="RG" :valor="beneficiario.rg" mono />
            <Campo rotulo="Data de nascimento" :valor="nascimento" />
            <Campo rotulo="Telefone" :valor="beneficiario.telefone" />
            <Campo rotulo="E-mail" :valor="beneficiario.email" />
            <Campo rotulo="Tipo de cadastro" :valor="rotuloTipoCadastro" />
            <Campo rotulo="Situação de vulnerabilidade" :valor="rotuloVulnerabilidade" />
          </dl>
        </ListContainer>

        <ListContainer title="Endereço" :icon="MapIcon" icon-class="text-emerald-500">
          <dl class="p-4 sm:p-6 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
            <Campo class="sm:col-span-2" rotulo="Endereço" :valor="beneficiario.endereco_completo" />
            <Campo rotulo="Bairro" :valor="beneficiario.bairro" />
            <Campo rotulo="CEP" :valor="beneficiario.cep" mono />
            <Campo rotulo="Município" :valor="municipio" />
          </dl>
        </ListContainer>

        <ListContainer title="Cadastro" :icon="ClipboardDocumentListIcon" icon-class="text-cyan-500">
          <dl class="p-4 sm:p-6 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
            <Campo rotulo="Cadastrado em" :valor="cadastro" />
            <Campo rotulo="Membros da família" :valor="totalMembros" />
            <Campo
              v-if="beneficiario.observacoes"
              class="sm:col-span-2"
              rotulo="Observações"
              :valor="beneficiario.observacoes"
            />
          </dl>
        </ListContainer>
      </div>

      <div class="xl:col-span-1 space-y-4 sm:space-y-6">
        <ListContainer
          title="Abrigo atual"
          :icon="HomeIcon"
          icon-class="text-amber-500"
        >
          <ListEmptyState
            v-if="!beneficiario.abrigo_atual"
            :icon="HomeIcon"
            title="Fora de abrigo"
            helper="Este beneficiário não está alojado no momento."
          />

          <dl v-else class="p-4 space-y-3 text-sm">
            <Campo rotulo="Abrigo" :valor="beneficiario.abrigo_atual.nome" />
            <Campo rotulo="Entrada" :valor="entradaNoAbrigo" />
          </dl>
        </ListContainer>

        <ListContainer
          title="Família"
          :icon="UsersIcon"
          :count="membros.length"
          icon-class="text-indigo-500"
        >
          <ListEmptyState
            v-if="!membros.length"
            :icon="UsersIcon"
            title="Nenhum membro cadastrado"
            helper="A composição familiar ainda não foi informada."
          />

          <ul v-else class="divide-y divide-slate-200 dark:divide-slate-700">
            <li v-for="membro in membros" :key="membro.id" class="p-4 space-y-1">
              <p class="text-sm text-slate-800 dark:text-slate-100">{{ membro.nome }}</p>
              <p class="text-xs text-slate-500 dark:text-slate-400">
                {{ membro.parentesco || 'Parentesco não informado' }}
              </p>
              <p v-if="membro.possui_deficiencia" class="text-xs text-amber-600 dark:text-amber-400">
                {{ membro.tipo_deficiencia || 'Possui deficiência' }}
              </p>
            </li>
          </ul>
        </ListContainer>

        <ListContainer
          title="Auxílios recebidos"
          :icon="ArchiveBoxIcon"
          :count="auxilios.length"
          icon-class="text-emerald-500"
        >
          <ListEmptyState
            v-if="!auxilios.length"
            :icon="ArchiveBoxIcon"
            title="Nenhum auxílio registrado"
            helper="Nada foi entregue a este beneficiário até agora."
          />

          <ul v-else class="divide-y divide-slate-200 dark:divide-slate-700">
            <li v-for="auxilio in auxilios" :key="auxilio.id" class="p-4 space-y-1">
              <p class="text-sm text-slate-800 dark:text-slate-100">
                {{ auxilio.tipo_auxilio || auxilio.descricao || 'Auxílio' }}
              </p>
              <p class="text-xs text-slate-500 dark:text-slate-400">
                {{ formatarData(auxilio.data_distribuicao) || 'sem data' }}
              </p>
              <p
                v-if="auxilio.valor_monetario"
                class="text-sm font-semibold tabular-nums text-slate-900 dark:text-white"
              >
                {{ moeda.format(auxilio.valor_monetario) }}
              </p>
            </li>
          </ul>
        </ListContainer>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, h } from 'vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import ArchiveBoxIcon from '@/Components/Icons/ArchiveBoxIcon.vue';
import ClipboardDocumentListIcon from '@/Components/Icons/ClipboardDocumentListIcon.vue';
import HomeIcon from '@/Components/Icons/HomeIcon.vue';
import MapIcon from '@/Components/Icons/MapIcon.vue';
import UsersIcon from '@/Components/Icons/UsersIcon.vue';
import ListContainer from '@/Components/Organisms/ListContainer.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import { applyCpfMask } from '@/utils/cpfMask';
import { moduleIcon } from '@/Support/moduleIcons';

const props = defineProps({
  beneficiario: { type: Object, required: true },
});

const moeda = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });

// O model devolve enum como objeto {value, label} quando serializado, e string
// crua quando o cast nao se aplica. Ler os dois evita "[object Object]".
function rotuloDoEnum(valor) {
  if (!valor) return null;

  const texto = typeof valor === 'object' ? (valor.label ?? valor.value ?? '') : String(valor);

  if (texto === '') return null;

  return texto.replaceAll('_', ' ').replace(/^./, (letra) => letra.toUpperCase());
}

const rotuloDoStatus = computed(() => rotuloDoEnum(props.beneficiario.status) ?? 'Sem status');
const rotuloTipoCadastro = computed(() => rotuloDoEnum(props.beneficiario.tipo_cadastro));
const rotuloVulnerabilidade = computed(() => rotuloDoEnum(props.beneficiario.situacao_vulnerabilidade));

const corDoStatus = computed(() => {
  const status = String(rotuloDoStatus.value).toLowerCase();

  if (status.includes('ativo')) return 'success';
  if (status.includes('falecid')) return 'danger';

  return 'neutral';
});

const membros = computed(() => props.beneficiario.membros_familia ?? []);
const auxilios = computed(() => props.beneficiario.auxilios ?? []);

const cpfFormatado = computed(() => (props.beneficiario.cpf ? applyCpfMask(props.beneficiario.cpf) : null));

const nascimento = computed(() => {
  const data = formatarData(props.beneficiario.data_nascimento);

  if (!data) return null;

  return props.beneficiario.idade ? data + ' (' + props.beneficiario.idade + ' anos)' : data;
});

const cadastro = computed(() => formatarData(props.beneficiario.data_cadastro));
const entradaNoAbrigo = computed(() => formatarData(props.beneficiario.data_entrada_abrigo));

// Mesma leitura da listagem. Nao ha FK declarada em beneficiarios.municipio_id,
// entao o nome so aparece se quem monta o payload trouxer a relacao.
const municipio = computed(
  () => props.beneficiario.municipio?.nome ?? props.beneficiario.municipio ?? null,
);

// numero_membros_familia e declarado no cadastro; a lista pode estar
// incompleta. Mostrar os dois evita a leitura de que faltam pessoas.
const totalMembros = computed(() => {
  const declarado = props.beneficiario.numero_membros_familia;

  if (!declarado) return membros.value.length || null;

  return membros.value.length === declarado
    ? String(declarado)
    : declarado + ' declarados, ' + membros.value.length + ' cadastrados';
});

const descricao = computed(() => {
  const partes = [rotuloTipoCadastro.value, cpfFormatado.value].filter(Boolean);

  return partes.length ? partes.join(' · ') : 'Beneficiário da ajuda humanitária';
});

/**
 * Monta a data a partir das partes: new Date('2022-03-08') e interpretado como
 * UTC e volta um dia atras em fuso negativo.
 */
function formatarData(iso) {
  if (!iso) return null;

  const [ano, mes, dia] = String(iso).slice(0, 10).split('-');

  return dia + '/' + mes + '/' + ano;
}

/** Par rotulo/valor da ficha, com travessao quando nao ha dado. */
const Campo = (propriedades) => h('div', { class: propriedades.class }, [
  h('dt', { class: 'text-xs font-medium text-slate-500 dark:text-slate-400' }, propriedades.rotulo),
  h('dd', {
    class: [propriedades.mono ? 'font-mono' : '', 'text-sm text-slate-800 dark:text-slate-100 break-words'],
  }, propriedades.valor || '—'),
]);
Campo.props = ['rotulo', 'valor', 'mono', 'class'];
</script>
